#!/usr/bin/env bash
# LogicPanel Installer - Self-healing version
# Automatically fixes line endings and re-runs if needed

# Self-healing: Fix line endings and re-run if we detect CRLF
if grep -q $'\r' "$0" 2>/dev/null; then
    TMP_FIXED="/tmp/logicpanel-install-fixed.sh"
    sed 's/\r$//' "$0" > "$TMP_FIXED"
    chmod +x "$TMP_FIXED"
    exec bash "$TMP_FIXED" "$@"
fi

# If running from pipe, download and run properly
if [ ! -t 0 ]; then
    TMP_SCRIPT="/tmp/logicpanel-install.sh"
    cat > "$TMP_SCRIPT"
    sed -i 's/\r$//' "$TMP_SCRIPT" 2>/dev/null || true
    chmod +x "$TMP_SCRIPT"
    exec bash "$TMP_SCRIPT" "$@"
fi

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
log_warning() { echo -e "${YELLOW}[!]${NC} $1"; }
log_error() { echo -e "${RED}[X]${NC} $1"; }
log_step() { echo -e "\n${CYAN}============================================${NC}"; echo -e "${WHITE}  $1${NC}"; echo -e "${CYAN}============================================${NC}"; }

generate_password() {
    openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c "$1"
}

generate_api_key() {
    echo "lp_$(openssl rand -hex 16)"
}

generate_api_secret() {
    openssl rand -hex 32
}

show_banner() {
    clear
    echo -e "${CYAN}"
    echo "============================================================================"
    echo ""
    echo "  _                 _      ____                  _"
    echo " | |    ___   __ _(_) ___|  _ \ __ _ _ __   ___| |"
    echo " | |   / _ \ / _  | |/ __| |_) / _  |  _ \ / _ \ |"
    echo " | |__| (_) | (_| | | (__|  __/ (_| | | | |  __/ |"
    echo " |_____\___/ \__, |_|\___|_|   \__,_|_| |_|\___|_|"
    echo "             |___/"
    echo ""
    echo "           Node.js Application Hosting Panel"
    echo "               Smart Installer v${VERSION}"
    echo ""
    echo "============================================================================"
    echo -e "${NC}"
}

check_root() {
    if [ "$EUID" -ne 0 ]; then
        log_error "This installer must be run as root!"
        echo -e "Please run: ${YELLOW}sudo bash install.sh${NC}"
        exit 1
    fi
    log_success "Running as root"
}

detect_os() {
    OS=""
    PKG_MANAGER=""
    
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$ID
        OS_NAME=$PRETTY_NAME
    elif [ -f /etc/redhat-release ]; then
        OS="rhel"
        OS_NAME=$(cat /etc/redhat-release)
    else
        log_error "Cannot detect OS!"
        exit 1
    fi
    
    case $OS in
        ubuntu|debian|linuxmint|pop)
            PKG_MANAGER="apt"
            log_success "Detected: $OS_NAME (apt)"
            ;;
        centos|rhel|rocky|almalinux|fedora)
            if command -v dnf &> /dev/null; then
                PKG_MANAGER="dnf"
            else
                PKG_MANAGER="yum"
            fi
            log_success "Detected: $OS_NAME ($PKG_MANAGER)"
            ;;
        *)
            if command -v apt-get &> /dev/null; then
                PKG_MANAGER="apt"
            elif command -v dnf &> /dev/null; then
                PKG_MANAGER="dnf"
            elif command -v yum &> /dev/null; then
                PKG_MANAGER="yum"
            fi
            log_success "Detected: $OS_NAME ($PKG_MANAGER)"
            ;;
    esac
}

install_docker() {
    log_step "Step 1/6: Docker Installation"
    
    if command -v docker &> /dev/null; then
        DOCKER_VERSION=$(docker --version | cut -d ' ' -f3 | tr -d ',')
        log_success "Docker already installed (v$DOCKER_VERSION)"
    else
        log_info "Installing Docker..."
        curl -fsSL https://get.docker.com | bash -s -- --quiet
        systemctl enable docker
        systemctl start docker
        log_success "Docker installed successfully"
    fi
    
    if docker compose version &> /dev/null; then
        log_success "Docker Compose available"
    else
        log_info "Installing Docker Compose..."
        case $PKG_MANAGER in
            apt) apt-get install -y -qq docker-compose-plugin 2>/dev/null || true ;;
            dnf|yum) $PKG_MANAGER install -y -q docker-compose-plugin 2>/dev/null || true ;;
        esac
        log_success "Docker Compose installed"
    fi
}

setup_nginx_proxy() {
    log_step "Step 2/6: Nginx Proxy & SSL Setup"
    
    if docker network inspect nginx-proxy_web &> /dev/null 2>&1; then
        log_success "nginx-proxy_web network exists"
    else
        docker network create nginx-proxy_web
        log_success "Created nginx-proxy_web network"
    fi
    
    if docker ps --format '{{.Names}}' | grep -q "^nginx-proxy$"; then
        log_success "Nginx Proxy already running"
        return
    fi
    
    log_info "Deploying Nginx Proxy stack..."
    mkdir -p $NGINX_PROXY_DIR
    
    cat > $NGINX_PROXY_DIR/docker-compose.yml << 'DCEOF'
version: '3.8'
services:
  nginx-proxy:
    image: jwilder/nginx-proxy:latest
    container_name: nginx-proxy
    restart: always
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/tmp/docker.sock:ro
      - nginx-certs:/etc/nginx/certs:ro
      - nginx-vhost:/etc/nginx/vhost.d
      - nginx-html:/usr/share/nginx/html
    networks:
      - nginx-proxy_web
    labels:
      - "com.github.jrcs.letsencrypt_nginx_proxy_companion.nginx_proxy=true"
  letsencrypt:
    image: jrcs/letsencrypt-nginx-proxy-companion:latest
    container_name: nginx-letsencrypt
    restart: always
    depends_on:
      - nginx-proxy
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - nginx-certs:/etc/nginx/certs:rw
      - nginx-vhost:/etc/nginx/vhost.d
      - nginx-html:/usr/share/nginx/html
      - acme:/etc/acme.sh
    networks:
      - nginx-proxy_web
volumes:
  nginx-certs:
  nginx-vhost:
  nginx-html:
  acme:
networks:
  nginx-proxy_web:
    external: true
DCEOF

    cd $NGINX_PROXY_DIR
    docker compose up -d --quiet-pull
    log_success "Nginx Proxy deployed"
}

get_configuration() {
    log_step "Step 3/6: Panel Configuration"
    
    echo ""
    echo -e "${YELLOW}Panel Domain${NC} (e.g., panel.yourdomain.com):"
    printf "> "
    read PANEL_DOMAIN
    if [ -z "$PANEL_DOMAIN" ]; then
        log_error "Domain is required!"
        exit 1
    fi
    
    echo ""
    echo -e "${YELLOW}Admin Email${NC} (for SSL & login):"
    printf "> "
    read ADMIN_EMAIL
    if [ -z "$ADMIN_EMAIL" ]; then
        log_error "Email is required!"
        exit 1
    fi
    
    echo ""
    echo -e "${YELLOW}Admin Name${NC}:"
    printf "> "
    read ADMIN_NAME
    ADMIN_NAME=${ADMIN_NAME:-"Administrator"}
    
    echo ""
    echo -e "${YELLOW}Admin Password${NC} (min 8 chars, leave empty for auto-generate):"
    printf "> "
    read -s ADMIN_PASSWORD
    echo ""
    
    if [ -z "$ADMIN_PASSWORD" ] || [ ${#ADMIN_PASSWORD} -lt 8 ]; then
        ADMIN_PASSWORD=$(generate_password 16)
        echo -e "${GREEN}Generated password: ${WHITE}$ADMIN_PASSWORD${NC}"
    fi
    
    DB_PASSWORD=$(generate_password 24)
    APP_SECRET=$(generate_password 64)
    API_KEY=$(generate_api_key)
    API_SECRET=$(generate_api_secret)
    
    log_success "Configuration complete"
}

deploy_logicpanel() {
    log_step "Step 4/6: Deploying LogicPanel"
    
    mkdir -p $INSTALL_DIR
    cd $INSTALL_DIR
    
    cat > docker-compose.yml << DCEOF
version: '3.8'
services:
  logicpanel:
    image: ghcr.io/${GITHUB_REPO,,}:latest
    container_name: logicpanel
    restart: always
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:rw
      - logicpanel-storage:/var/www/html/storage
      - logicpanel-apps:/var/www/apps
    environment:
      - VIRTUAL_HOST=${PANEL_DOMAIN}
      - LETSENCRYPT_HOST=${PANEL_DOMAIN}
      - LETSENCRYPT_EMAIL=${ADMIN_EMAIL}
      - DB_HOST=logicpanel-db
      - DB_DATABASE=logicpanel
      - DB_USERNAME=logicpanel
      - DB_PASSWORD=${DB_PASSWORD}
      - DB_PREFIX=lp_
      - APP_SECRET=${APP_SECRET}
      - APP_URL=https://${PANEL_DOMAIN}
      - APP_ENV=production
      - ADMIN_EMAIL=${ADMIN_EMAIL}
      - ADMIN_NAME=${ADMIN_NAME}
      - ADMIN_PASSWORD=${ADMIN_PASSWORD}
      - API_KEY=${API_KEY}
      - API_SECRET=${API_SECRET}
    networks:
      - nginx-proxy_web
      - logicpanel-internal
    depends_on:
      - logicpanel-db
  logicpanel-db:
    image: mysql:8.0
    container_name: logicpanel-db
    restart: always
    environment:
      - MYSQL_ROOT_PASSWORD=${DB_PASSWORD}
      - MYSQL_DATABASE=logicpanel
      - MYSQL_USER=logicpanel
      - MYSQL_PASSWORD=${DB_PASSWORD}
    volumes:
      - logicpanel-mysql:/var/lib/mysql
    networks:
      - logicpanel-internal
    command: --default-authentication-plugin=mysql_native_password
volumes:
  logicpanel-storage:
  logicpanel-apps:
  logicpanel-mysql:
networks:
  nginx-proxy_web:
    external: true
  logicpanel-internal:
    driver: bridge
DCEOF

    cat > .env << ENVEOF
# LogicPanel Configuration
PANEL_DOMAIN=${PANEL_DOMAIN}
ADMIN_EMAIL=${ADMIN_EMAIL}
ADMIN_NAME=${ADMIN_NAME}
DB_PASSWORD=${DB_PASSWORD}
APP_SECRET=${APP_SECRET}
API_KEY=${API_KEY}
API_SECRET=${API_SECRET}
ENVEOF

    chmod 600 .env
    
    log_info "Pulling Docker images..."
    docker compose pull --quiet 2>/dev/null || true
    
    log_info "Starting containers..."
    docker compose up -d --quiet-pull 2>/dev/null || docker compose up -d
    
    log_success "LogicPanel deployed"
}

create_cli_command() {
    log_step "Step 5/6: Creating CLI Commands"
    
    cat > /usr/local/bin/logicpanel << 'CLIPANEL'
#!/bin/bash
INSTALL_DIR="/opt/logicpanel"
cd $INSTALL_DIR
case "$1" in
    start) docker compose up -d; echo "LogicPanel started" ;;
    stop) docker compose down; echo "LogicPanel stopped" ;;
    restart) docker compose restart; echo "LogicPanel restarted" ;;
    logs) docker compose logs -f ${2:-logicpanel} ;;
    status) docker compose ps ;;
    update) docker compose pull; docker compose up -d; echo "LogicPanel updated" ;;
    credentials) cat $INSTALL_DIR/.env | grep -E "^(API_KEY|API_SECRET|ADMIN_)" ;;
    *) echo "Usage: logicpanel {start|stop|restart|logs|status|update|credentials}" ;;
esac
CLIPANEL
    chmod +x /usr/local/bin/logicpanel
    log_success "Created 'logicpanel' command"
    
    cat > /usr/local/bin/whmcs << 'CLIWHMCS'
#!/bin/bash
INSTALL_DIR="/opt/logicpanel"
ENV_FILE="$INSTALL_DIR/.env"
case "$1" in
    show)
        echo ""
        echo "WHMCS API Credentials:"
        echo "======================"
        grep "^API_KEY=" $ENV_FILE
        grep "^API_SECRET=" $ENV_FILE
        echo ""
        ;;
    generate)
        if [ "$2" == "new" ]; then
            NEW_KEY="lp_$(openssl rand -hex 16)"
            NEW_SECRET="$(openssl rand -hex 32)"
            sed -i "s/^API_KEY=.*/API_KEY=$NEW_KEY/" $ENV_FILE
            sed -i "s/^API_SECRET=.*/API_SECRET=$NEW_SECRET/" $ENV_FILE
            cd $INSTALL_DIR && docker compose restart logicpanel
            echo "New credentials generated:"
            echo "API_KEY=$NEW_KEY"
            echo "API_SECRET=$NEW_SECRET"
        fi
        ;;
    *) echo "Usage: whmcs {show|generate new}" ;;
esac
CLIWHMCS
    chmod +x /usr/local/bin/whmcs
    log_success "Created 'whmcs' command"
}

show_summary() {
    log_step "Step 6/6: Installation Complete!"
    
    log_info "Waiting for services to start..."
    sleep 10
    
    PANEL_URL="https://${PANEL_DOMAIN}"
    
    echo ""
    echo -e "${GREEN}============================================================${NC}"
    echo -e "${GREEN}       LogicPanel Installation Successful!                  ${NC}"
    echo -e "${GREEN}============================================================${NC}"
    echo ""
    echo -e "${WHITE}Panel Access${NC}"
    echo -e "  URL:      ${CYAN}${PANEL_URL}${NC}"
    echo -e "  Email:    ${CYAN}${ADMIN_EMAIL}${NC}"
    echo -e "  Password: ${CYAN}${ADMIN_PASSWORD}${NC}"
    echo ""
    echo -e "${WHITE}WHMCS Integration${NC}"
    echo -e "  Hostname:    ${CYAN}${PANEL_DOMAIN}${NC}"
    echo -e "  Secure:      ${CYAN}Yes (SSL)${NC}"
    echo -e "  API Key:     ${YELLOW}${API_KEY}${NC}"
    echo -e "  API Secret:  ${YELLOW}${API_SECRET}${NC}"
    echo ""
    echo -e "${WHITE}CLI Commands${NC}"
    echo -e "  logicpanel start/stop/restart/logs/update"
    echo -e "  whmcs show / whmcs generate new"
    echo ""
    echo -e "${YELLOW}Credentials saved to: ${INSTALL_DIR}/.env${NC}"
    echo ""
}

main() {
    show_banner
    log_info "Starting installation..."
    echo ""
    check_root
    detect_os
    mkdir -p $INSTALL_DIR
    install_docker
    setup_nginx_proxy
    get_configuration
    deploy_logicpanel
    create_cli_command
    show_summary
}

main "$@"
