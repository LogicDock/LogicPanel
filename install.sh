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

# Detect cloud provider
detect_cloud_provider() {
    # Check for AWS
    if [ -f /sys/devices/virtual/dmi/id/product_uuid ] && grep -qi "ec2" /sys/devices/virtual/dmi/id/product_uuid 2>/dev/null; then
        echo "aws"
        return
    fi
    if curl -s --connect-timeout 1 http://169.254.169.254/latest/meta-data/ &>/dev/null; then
        echo "aws"
        return
    fi
    
    # Check for Google Cloud
    if curl -s --connect-timeout 1 -H "Metadata-Flavor: Google" http://169.254.169.254/computeMetadata/v1/ &>/dev/null; then
        echo "gcp"
        return
    fi
    
    # Check for Azure
    if curl -s --connect-timeout 1 -H "Metadata: true" "http://169.254.169.254/metadata/instance?api-version=2021-02-01" &>/dev/null; then
        echo "azure"
        return
    fi
    
    # Check for DigitalOcean
    if curl -s --connect-timeout 1 http://169.254.169.254/metadata/v1/ &>/dev/null; then
        echo "digitalocean"
        return
    fi
    
    echo "none"
}

# Check if port is open using various methods
check_port_open() {
    local port=$1
    
    # Check with ss/netstat if port is listening
    if command -v ss &>/dev/null; then
        ss -tlnp | grep -q ":${port} " && return 0
    fi
    
    return 1
}

# Detect firewall type
detect_firewall() {
    if command -v ufw &>/dev/null && ufw status 2>/dev/null | grep -q "Status: active"; then
        echo "ufw"
    elif command -v firewall-cmd &>/dev/null && systemctl is-active firewalld &>/dev/null; then
        echo "firewalld"
    elif command -v iptables &>/dev/null && iptables -L &>/dev/null 2>&1; then
        echo "iptables"
    else
        echo "none"
    fi
}

# Open port based on firewall type
open_port() {
    local port=$1
    local protocol=${2:-tcp}
    local firewall=$(detect_firewall)
    
    case $firewall in
        ufw)
            # Check if rule already exists
            if ! ufw status | grep -q "${port}/${protocol}"; then
                ufw allow ${port}/${protocol} &>/dev/null
                log_info "Opened port ${port}/${protocol} (ufw)"
            fi
            ;;
        firewalld)
            # Check if already open
            if ! firewall-cmd --query-port=${port}/${protocol} &>/dev/null; then
                firewall-cmd --permanent --add-port=${port}/${protocol} &>/dev/null
                firewall-cmd --reload &>/dev/null
                log_info "Opened port ${port}/${protocol} (firewalld)"
            fi
            ;;
        iptables)
            # Check if rule exists
            if ! iptables -C INPUT -p ${protocol} --dport ${port} -j ACCEPT &>/dev/null 2>&1; then
                iptables -I INPUT -p ${protocol} --dport ${port} -j ACCEPT &>/dev/null
                # Try to save iptables rules
                if command -v iptables-save &>/dev/null; then
                    iptables-save > /etc/iptables.rules 2>/dev/null || true
                fi
                log_info "Opened port ${port}/${protocol} (iptables)"
            fi
            ;;
        none)
            # No firewall detected, assume open
            ;;
    esac
}

# Configure all required ports
configure_firewall() {
    log_step "Step 1/8: Firewall Configuration"
    
    local cloud=$(detect_cloud_provider)
    local firewall=$(detect_firewall)
    
    # Required ports
    local REQUIRED_PORTS=(
        "22:SSH"
        "80:HTTP"
        "443:HTTPS"
    )
    
    # Optional ports for external database access
    local OPTIONAL_PORTS=(
        "3306:MySQL"
        "5432:PostgreSQL"
        "27017:MongoDB"
    )
    
    log_info "Detected firewall: ${firewall:-none}"
    log_info "Cloud provider: ${cloud:-none}"
    
    # Handle cloud providers
    if [ "$cloud" != "none" ]; then
        echo ""
        echo -e "${YELLOW}╔════════════════════════════════════════════════════════════════╗${NC}"
        echo -e "${YELLOW}║  CLOUD PROVIDER DETECTED: ${cloud^^}                              ║${NC}"
        echo -e "${YELLOW}╠════════════════════════════════════════════════════════════════╣${NC}"
        echo -e "${YELLOW}║  Please ensure these ports are open in your cloud firewall:    ║${NC}"
        echo -e "${YELLOW}║                                                                ║${NC}"
        echo -e "${YELLOW}║  REQUIRED:                                                     ║${NC}"
        echo -e "${YELLOW}║    • Port 22   (SSH)                                           ║${NC}"
        echo -e "${YELLOW}║    • Port 80   (HTTP)                                          ║${NC}"
        echo -e "${YELLOW}║    • Port 443  (HTTPS)                                         ║${NC}"
        echo -e "${YELLOW}║                                                                ║${NC}"
        echo -e "${YELLOW}║  OPTIONAL (for external database access):                      ║${NC}"
        echo -e "${YELLOW}║    • Port 3306  (MySQL/MariaDB)                                ║${NC}"
        echo -e "${YELLOW}║    • Port 5432  (PostgreSQL)                                   ║${NC}"
        echo -e "${YELLOW}║    • Port 27017 (MongoDB)                                      ║${NC}"
        echo -e "${YELLOW}╠════════════════════════════════════════════════════════════════╣${NC}"
        
        case $cloud in
            aws)
                echo -e "${YELLOW}║  AWS: EC2 Console → Security Groups → Inbound Rules          ║${NC}"
                ;;
            gcp)
                echo -e "${YELLOW}║  GCP: VPC Network → Firewall Rules → Create Rule             ║${NC}"
                ;;
            azure)
                echo -e "${YELLOW}║  Azure: Network Security Group → Inbound Rules               ║${NC}"
                ;;
            digitalocean)
                echo -e "${YELLOW}║  DO: Networking → Firewalls → Add Rule                       ║${NC}"
                ;;
        esac
        
        echo -e "${YELLOW}╚════════════════════════════════════════════════════════════════╝${NC}"
        echo ""
        read -p "Press ENTER after configuring cloud firewall (or skip with 's'): " CLOUD_CONFIRM
        [ "$CLOUD_CONFIRM" = "s" ] && log_info "Skipped cloud firewall confirmation"
    fi
    
    # Open required ports on local firewall
    if [ "$firewall" != "none" ]; then
        log_info "Configuring local firewall..."
        
        for port_info in "${REQUIRED_PORTS[@]}"; do
            local port="${port_info%%:*}"
            local name="${port_info##*:}"
            open_port $port tcp
        done
        
        log_success "Required ports configured (22, 80, 443)"
        
        # Ask about optional ports
        echo ""
        read -p "Open database ports for external access (3306, 5432, 27017)? [y/N]: " OPEN_DB_PORTS
        if [[ "$OPEN_DB_PORTS" =~ ^[Yy]$ ]]; then
            for port_info in "${OPTIONAL_PORTS[@]}"; do
                local port="${port_info%%:*}"
                local name="${port_info##*:}"
                open_port $port tcp
            done
            log_success "Database ports opened"
        else
            log_info "Database ports skipped (use internal Docker network)"
        fi
    else
        log_success "No firewall detected - ports should be open"
    fi
}
install_docker() {
    log_step "Step 2/8: Docker Installation"
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
    log_step "Step 3/8: Nginx Proxy Setup"
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
    log_step "Step 4/8: Panel Configuration"
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
    log_step "Step 5/8: Deploying LogicPanel"
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

setup_shared_databases() {
    log_step "Step 6/8: Shared Database Services"
    
    # Create shared Docker Compose file
    mkdir -p $INSTALL_DIR/shared-db
    cd $INSTALL_DIR/shared-db
    
    SHARED_ROOT_PASS=$(generate_password 24)
    
    cat > docker-compose.yml << EOF
version: '3.8'
services:
  # Shared MongoDB
  mongo-shared:
    image: mongo:6.0
    container_name: logicpanel-mongo-shared
    restart: always
    environment:
      MONGO_INITDB_ROOT_USERNAME: root
      MONGO_INITDB_ROOT_PASSWORD: ${SHARED_ROOT_PASS}
    ports:
      - "27017:27017" # Exposed for Compass
    volumes:
      - mongo_data:/data/db
    networks:
      - internal

  # Shared PostgreSQL
  postgres-shared:
    image: postgres:15
    container_name: logicpanel-postgres-shared
    restart: always
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: ${SHARED_ROOT_PASS}
    ports:
      - "5432:5432" # Exposed for pgAdmin/DBeaver
    volumes:
      - postgres_data:/var/lib/postgresql/data
    networks:
      - internal

  # Shared MariaDB
  mariadb-shared:
    image: mariadb:10.11
    container_name: logicpanel-mariadb-shared
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: ${SHARED_ROOT_PASS}
    ports:
      - "3306:3306" # Exposed for Workbench/HeidiSQL
    volumes:
      - mariadb_data:/var/lib/mysql
    networks:
      - internal

networks:
  internal:
    external: true

volumes:
  mongo_data:
  postgres_data:
  mariadb_data:
EOF

    # Add shared password to main .env
    echo "SHARED_DB_ROOT_PASSWORD=${SHARED_ROOT_PASS}" >> $INSTALL_DIR/.env
    echo "SHARED_MONGO_CONTAINER=logicpanel-mongo-shared" >> $INSTALL_DIR/.env
    echo "SHARED_POSTGRES_CONTAINER=logicpanel-postgres-shared" >> $INSTALL_DIR/.env
    echo "SHARED_MARIADB_CONTAINER=logicpanel-mariadb-shared" >> $INSTALL_DIR/.env

    # Start shared services
    docker compose up -d
    log_success "Shared databases deployed (Mongo, Postgres, MariaDB)"
}
create_cli() {
    log_step "Step 7/8: CLI Commands"
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
    log_step "Step 8/8: Complete!"
    
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
    echo -e "${YELLOW}Database Ports (External Access):${NC}"
    echo -e "  MySQL: 3306"
    echo -e "  PostgreSQL: 5432"
    echo -e "  MongoDB: 27017"
    echo ""
}
main() {
    show_banner
    check_root
    detect_os
    configure_firewall
    install_docker
    setup_nginx_proxy
    get_configuration
    deploy_logicpanel
    setup_shared_databases
    create_cli
    show_summary
}
main
