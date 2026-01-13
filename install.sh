#!/usr/bin/env bash
set -e
GITHUB_REPO="LogicDock/LogicPanel"
INSTALL_DIR="/opt/logicpanel"
NGINX_PROXY_DIR="/opt/nginx-proxy"
VERSION="1.0.0"
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m'
log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_error() { echo -e "${RED}[X]${NC} $1"; }
log_step() { echo -e "\n${CYAN}============================================${NC}"; echo -e "${WHITE}  $1${NC}"; echo -e "${CYAN}============================================${NC}"; }
generate_password() { openssl rand -base64 32 | tr -dc 'a-zA-Z0-9@#' | head -c "$1"; }
generate_api_key() { echo "lp_$(openssl rand -hex 16)"; }
generate_api_secret() { openssl rand -hex 32; }
show_banner() {
    clear
    echo -e "${CYAN}============================================================================${NC}"
    echo -e "${CYAN}  LogicPanel - Node.js Application Hosting Panel - Installer v${VERSION}${NC}"
    echo -e "${CYAN}============================================================================${NC}"
}
check_root() {
    if [ "$EUID" -ne 0 ]; then
        log_error "Run as root: sudo bash install.sh"
        exit 1
    fi
    log_success "Running as root"
}
detect_os() {
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$ID
        log_success "Detected: $PRETTY_NAME"
    else
        log_error "Cannot detect OS"
        exit 1
    fi
}
install_docker() {
    log_step "Step 1/6: Docker Installation"
    if command -v docker &> /dev/null; then
        log_success "Docker already installed"
    else
        log_info "Installing Docker..."
        curl -fsSL https://get.docker.com | bash -s -- --quiet
        systemctl enable docker && systemctl start docker
        log_success "Docker installed"
    fi
    if docker compose version &> /dev/null; then
        log_success "Docker Compose available"
    fi
}
setup_nginx_proxy() {
    log_step "Step 2/6: Nginx Proxy Setup"
    docker network inspect nginx-proxy_web &>/dev/null || docker network create nginx-proxy_web
    log_success "Network ready"
    
    if docker ps --format '{{.Names}}' | grep -q "^nginx-proxy$"; then
        log_success "Nginx Proxy running"
        return
    fi
    
    mkdir -p $NGINX_PROXY_DIR
    cat > $NGINX_PROXY_DIR/docker-compose.yml << 'EOF'
version: '3.8'
services:
  nginx-proxy:
    image: jwilder/nginx-proxy:latest
    container_name: nginx-proxy
    restart: always
    ports: ["80:80", "443:443"]
    volumes:
      - /var/run/docker.sock:/tmp/docker.sock:ro
      - certs:/etc/nginx/certs:ro
      - vhost:/etc/nginx/vhost.d
      - html:/usr/share/nginx/html
    networks: [nginx-proxy_web]
    labels: ["com.github.jrcs.letsencrypt_nginx_proxy_companion.nginx_proxy=true"]
  letsencrypt:
    image: jrcs/letsencrypt-nginx-proxy-companion:latest
    container_name: nginx-letsencrypt
    restart: always
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - certs:/etc/nginx/certs:rw
      - vhost:/etc/nginx/vhost.d
      - html:/usr/share/nginx/html
      - acme:/etc/acme.sh
    networks: [nginx-proxy_web]
volumes: {certs: {}, vhost: {}, html: {}, acme: {}}
networks:
  nginx-proxy_web:
    external: true
EOF
    cd $NGINX_PROXY_DIR && docker compose up -d
    log_success "Nginx Proxy deployed"
}
get_configuration() {
    log_step "Step 3/6: Panel Configuration"
    echo ""
    read -p "Panel Domain (e.g., panel.example.com): " PANEL_DOMAIN
    [ -z "$PANEL_DOMAIN" ] && log_error "Domain required" && exit 1
    
    read -p "Admin Username (no spaces): " ADMIN_USERNAME
    [ -z "$ADMIN_USERNAME" ] && log_error "Username required" && exit 1
    
    read -p "Admin Email: " ADMIN_EMAIL
    [ -z "$ADMIN_EMAIL" ] && log_error "Email required" && exit 1
    
    read -s -p "Admin Password (empty=auto): " ADMIN_PASSWORD
    echo ""
    [ -z "$ADMIN_PASSWORD" ] && ADMIN_PASSWORD=$(generate_password 16) && echo "Generated: $ADMIN_PASSWORD"
    
    DB_PASSWORD=$(generate_password 24)
    APP_SECRET=$(generate_password 64)
    API_KEY=$(generate_api_key)
    API_SECRET=$(generate_api_secret)
    log_success "Configuration complete"
}
deploy_logicpanel() {
    log_step "Step 4/6: Deploying LogicPanel"
    mkdir -p $INSTALL_DIR && cd $INSTALL_DIR
    
    cat > docker-compose.yml << EOF
version: '3.8'
services:
  logicpanel:
    image: ghcr.io/logicdock/logicpanel:latest
    container_name: logicpanel
    restart: always
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:rw
      - storage:/var/www/html/storage
      - apps:/var/www/apps
    environment:
      VIRTUAL_HOST: ${PANEL_DOMAIN}
      LETSENCRYPT_HOST: ${PANEL_DOMAIN}
      LETSENCRYPT_EMAIL: ${ADMIN_EMAIL}
      DB_HOST: logicpanel-db
      DB_DATABASE: logicpanel
      DB_USERNAME: logicpanel
      DB_PASSWORD: ${DB_PASSWORD}
      APP_SECRET: ${APP_SECRET}
      APP_URL: https://${PANEL_DOMAIN}
      ADMIN_USERNAME: ${ADMIN_USERNAME}
      ADMIN_EMAIL: ${ADMIN_EMAIL}
      ADMIN_PASSWORD: ${ADMIN_PASSWORD}
      API_KEY: ${API_KEY}
      API_SECRET: ${API_SECRET}
    networks: [nginx-proxy_web, internal]
    depends_on: [logicpanel-db]
  logicpanel-db:
    image: mysql:8.0
    container_name: logicpanel-db
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_DATABASE: logicpanel
      MYSQL_USER: logicpanel
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes: [mysql:/var/lib/mysql]
    networks: [internal]
volumes: {storage: {}, apps: {}, mysql: {}}
networks:
  nginx-proxy_web: {external: true}
  internal: {}
EOF
    cat > .env << EOF
PANEL_DOMAIN=${PANEL_DOMAIN}
ADMIN_USERNAME=${ADMIN_USERNAME}
ADMIN_EMAIL=${ADMIN_EMAIL}
ADMIN_PASSWORD=${ADMIN_PASSWORD}
API_KEY=${API_KEY}
API_SECRET=${API_SECRET}
EOF
    chmod 600 .env
    
    docker compose pull && docker compose up -d
    log_success "LogicPanel deployed"
}
create_cli() {
    log_step "Step 5/6: CLI Commands"
    echo '#!/bin/bash
cd /opt/logicpanel
case "$1" in
  start) docker compose up -d;;
  stop) docker compose down;;
  restart) docker compose restart;;
  logs) docker compose logs -f;;
  update) docker compose pull && docker compose up -d;;
  *) echo "Usage: logicpanel {start|stop|restart|logs|update}";;
esac' > /usr/local/bin/logicpanel
    chmod +x /usr/local/bin/logicpanel
    log_success "CLI created"
}
show_summary() {
    log_step "Step 6/6: Complete!"
    
    echo ""
    echo -e "${YELLOW}Waiting for SSL certificate and services to start...${NC}"
    echo -e "${YELLOW}This may take 30-60 seconds...${NC}"
    echo ""
    
    for i in {1..12}; do
        echo -ne "\r${CYAN}[${NC}"
        for j in $(seq 1 $i); do echo -ne "="; done
        for j in $(seq $i 11); do echo -ne " "; done
        echo -ne "${CYAN}]${NC} $((i*5))s"
        sleep 5
    done
    echo ""
    echo ""
    
    echo -e "${GREEN}=== LogicPanel Installed ===${NC}"
    echo -e "URL: https://${PANEL_DOMAIN}"
    echo -e "Username: ${ADMIN_USERNAME}"
    echo -e "Email: ${ADMIN_EMAIL}"
    echo -e "Password: ${ADMIN_PASSWORD}"
    echo ""
    echo -e "${YELLOW}WHMCS Server Configuration:${NC}"
    echo -e "  Hostname: ${PANEL_DOMAIN}"
    echo -e "  Username: ${API_KEY}"
    echo -e "  Password: ${API_SECRET}"
    echo -e "  Secure: Yes (check SSL)"
    echo ""
}
main() {
    show_banner
    check_root
    detect_os
    install_docker
    setup_nginx_proxy
    get_configuration
    deploy_logicpanel
    create_cli
    show_summary
}
main
