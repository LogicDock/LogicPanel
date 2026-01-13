#!/bin/bash
#
# ============================================================================
#                      LogicPanel - Smart Installer
#                   Node.js Application Hosting Panel
#
#  Usage: curl -sL https://raw.githubusercontent.com/logicdock/
#                 logicpanel/main/install.sh | sudo bash
#
#  Copyright (c) 2024 LogicDock
# ============================================================================
#

set -e

# ============================================
# Configuration
# ============================================
GITHUB_REPO="logicdock/logicpanel"
INSTALL_DIR="/opt/logicpanel"
NGINX_PROXY_DIR="/opt/nginx-proxy"
VERSION="1.0.0"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
WHITE='\033[1;37m'
NC='\033[0m'

# ============================================
# Helper Functions
# ============================================
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

# ============================================
# Banner
# ============================================
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

# ============================================
# System Checks
# ============================================
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
    PKG_UPDATE=""
    PKG_INSTALL=""
    
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$ID
        OS_VERSION=$VERSION_ID
        OS_NAME=$PRETTY_NAME
    elif [ -f /etc/redhat-release ]; then
        OS="rhel"
        OS_NAME=$(cat /etc/redhat-release)
    elif [ -f /etc/arch-release ]; then
        OS="arch"
        OS_NAME="Arch Linux"
    else
        log_error "Cannot detect OS!"
        exit 1
    fi
    
    # Detect package manager and set commands
    case $OS in
        ubuntu|debian|linuxmint|pop|elementary|zorin)
            PKG_MANAGER="apt"
            PKG_UPDATE="apt-get update -qq"
            PKG_INSTALL="apt-get install -y -qq"
            log_success "Detected: $OS_NAME (apt)"
            ;;
        centos|rhel|rocky|almalinux|ol|oracle)
            if command -v dnf &> /dev/null; then
                PKG_MANAGER="dnf"
                PKG_UPDATE="dnf check-update -q || true"
                PKG_INSTALL="dnf install -y -q"
            else
                PKG_MANAGER="yum"
                PKG_UPDATE="yum check-update -q || true"
                PKG_INSTALL="yum install -y -q"
            fi
            log_success "Detected: $OS_NAME ($PKG_MANAGER)"
            ;;
        fedora)
            PKG_MANAGER="dnf"
            PKG_UPDATE="dnf check-update -q || true"
            PKG_INSTALL="dnf install -y -q"
            log_success "Detected: $OS_NAME (dnf)"
            ;;
        opensuse*|sles|suse)
            PKG_MANAGER="zypper"
            PKG_UPDATE="zypper refresh -q"
            PKG_INSTALL="zypper install -y -q"
            log_success "Detected: $OS_NAME (zypper)"
            ;;
        arch|manjaro|endeavouros)
            PKG_MANAGER="pacman"
            PKG_UPDATE="pacman -Sy --noconfirm"
            PKG_INSTALL="pacman -S --noconfirm"
            log_success "Detected: $OS_NAME (pacman)"
            ;;
        alpine)
            PKG_MANAGER="apk"
            PKG_UPDATE="apk update -q"
            PKG_INSTALL="apk add -q"
            log_success "Detected: $OS_NAME (apk)"
            ;;
        *)
            log_warning "Detected: $OS_NAME (unknown package manager)"
            log_info "Attempting to auto-detect package manager..."
            
            if command -v apt-get &> /dev/null; then
                PKG_MANAGER="apt"
                PKG_UPDATE="apt-get update -qq"
                PKG_INSTALL="apt-get install -y -qq"
            elif command -v dnf &> /dev/null; then
                PKG_MANAGER="dnf"
                PKG_UPDATE="dnf check-update -q || true"
                PKG_INSTALL="dnf install -y -q"
            elif command -v yum &> /dev/null; then
                PKG_MANAGER="yum"
                PKG_UPDATE="yum check-update -q || true"
                PKG_INSTALL="yum install -y -q"
            elif command -v zypper &> /dev/null; then
                PKG_MANAGER="zypper"
                PKG_UPDATE="zypper refresh -q"
                PKG_INSTALL="zypper install -y -q"
            elif command -v pacman &> /dev/null; then
                PKG_MANAGER="pacman"
                PKG_UPDATE="pacman -Sy --noconfirm"
                PKG_INSTALL="pacman -S --noconfirm"
            elif command -v apk &> /dev/null; then
                PKG_MANAGER="apk"
                PKG_UPDATE="apk update -q"
                PKG_INSTALL="apk add -q"
            else
                log_error "No supported package manager found!"
                exit 1
            fi
            log_success "Auto-detected: $PKG_MANAGER"
            ;;
    esac
}

install_dependencies() {
    log_info "Installing dependencies..."
    
    case $PKG_MANAGER in
        apt)
            $PKG_UPDATE
            $PKG_INSTALL ca-certificates curl gnupg lsb-release openssl
            ;;
        dnf|yum)
            $PKG_UPDATE
            $PKG_INSTALL ca-certificates curl openssl
            ;;
        zypper)
            $PKG_UPDATE
            $PKG_INSTALL ca-certificates curl openssl
            ;;
        pacman)
            $PKG_UPDATE
            $PKG_INSTALL ca-certificates curl openssl
            ;;
        apk)
            $PKG_UPDATE
            $PKG_INSTALL ca-certificates curl openssl bash
            ;;
    esac
    
    log_success "Dependencies installed"
}

# ============================================
# Docker Installation
# ============================================
install_docker() {
    log_step "Step 1/6: Docker Installation"
    
    if command -v docker &> /dev/null; then
        DOCKER_VERSION=$(docker --version | cut -d ' ' -f3 | tr -d ',')
        log_success "Docker already installed (v$DOCKER_VERSION)"
    else
        log_info "Installing Docker..."
        
        # Remove old versions based on package manager
        case $PKG_MANAGER in
            apt)
                apt-get remove -y docker docker-engine docker.io containerd runc 2>/dev/null || true
                ;;
            dnf|yum)
                $PKG_MANAGER remove -y docker docker-client docker-client-latest docker-common docker-latest docker-latest-logrotate docker-logrotate docker-engine 2>/dev/null || true
                ;;
        esac
        
        # Install dependencies
        install_dependencies
        
        # Install Docker using official script (works on most distros)
        curl -fsSL https://get.docker.com | bash -s -- --quiet
        
        # Enable and start Docker
        if command -v systemctl &> /dev/null; then
            systemctl enable docker
            systemctl start docker
        elif command -v rc-update &> /dev/null; then
            # Alpine/OpenRC
            rc-update add docker
            service docker start
        fi
        
        log_success "Docker installed successfully"
    fi
    
    # Check Docker Compose
    if docker compose version &> /dev/null; then
        log_success "Docker Compose available"
    else
        log_info "Installing Docker Compose plugin..."
        
        case $PKG_MANAGER in
            apt)
                $PKG_INSTALL docker-compose-plugin 2>/dev/null || true
                ;;
            dnf|yum)
                $PKG_INSTALL docker-compose-plugin 2>/dev/null || true
                ;;
            *)
                # Fallback: Download compose binary
                COMPOSE_VERSION=$(curl -s https://api.github.com/repos/docker/compose/releases/latest | grep '"tag_name"' | cut -d'"' -f4)
                curl -sL "https://github.com/docker/compose/releases/download/${COMPOSE_VERSION}/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
                chmod +x /usr/local/bin/docker-compose
                ln -sf /usr/local/bin/docker-compose /usr/bin/docker-compose
                ;;
        esac
        
        # Verify installation
        if docker compose version &> /dev/null || docker-compose version &> /dev/null; then
            log_success "Docker Compose installed"
        else
            log_warning "Docker Compose installation may have issues"
        fi
    fi
}

# ============================================
# Nginx Proxy Setup
# ============================================
setup_nginx_proxy() {
    log_step "Step 2/6: Nginx Proxy & SSL Setup"
    
    # Check/Create network
    if docker network inspect nginx-proxy_web &> /dev/null 2>&1; then
        log_success "nginx-proxy_web network exists"
    else
        docker network create nginx-proxy_web
        log_success "Created nginx-proxy_web network"
    fi
    
    # Check if nginx-proxy running
    if docker ps --format '{{.Names}}' | grep -q "^nginx-proxy$"; then
        log_success "Nginx Proxy already running"
        return
    fi
    
    log_info "Deploying Nginx Proxy stack..."
    
    mkdir -p $NGINX_PROXY_DIR
    
    cat > $NGINX_PROXY_DIR/docker-compose.yml << 'EOF'
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
EOF

    cd $NGINX_PROXY_DIR
    docker compose up -d --quiet-pull
    log_success "Nginx Proxy deployed"
}

# License validation removed - Panel is FREE
# License is only required for WHMCS module (via addon)

# ============================================
# Configuration Input
# ============================================
get_configuration() {
    log_step "Step 3/6: Panel Configuration"
    
    echo ""
    
    # Domain
    echo -e "${YELLOW}Panel Domain${NC} (e.g., panel.yourdomain.com):"
    read -p "> " PANEL_DOMAIN
    if [ -z "$PANEL_DOMAIN" ]; then
        log_error "Domain is required!"
        exit 1
    fi
    
    # Admin Email
    echo ""
    echo -e "${YELLOW}Admin Email${NC} (for SSL & login):"
    read -p "> " ADMIN_EMAIL
    if [ -z "$ADMIN_EMAIL" ]; then
        log_error "Email is required!"
        exit 1
    fi
    
    # Admin Name
    echo ""
    echo -e "${YELLOW}Admin Name${NC}:"
    read -p "> " ADMIN_NAME
    ADMIN_NAME=${ADMIN_NAME:-"Administrator"}
    
    # Admin Password
    echo ""
    echo -e "${YELLOW}Admin Password${NC} (min 8 chars, leave empty for auto-generate):"
    read -s -p "> " ADMIN_PASSWORD
    echo ""
    
    if [ -z "$ADMIN_PASSWORD" ] || [ ${#ADMIN_PASSWORD} -lt 8 ]; then
        ADMIN_PASSWORD=$(generate_password 16)
        echo -e "${GREEN}Generated password: ${WHITE}$ADMIN_PASSWORD${NC}"
    fi
    
    # Generate all credentials
    DB_PASSWORD=$(generate_password 24)
    APP_SECRET=$(generate_password 64)
    API_KEY=$(generate_api_key)
    API_SECRET=$(generate_api_secret)
    
    log_success "Configuration complete"
}

# ============================================
# Deploy LogicPanel
# ============================================
deploy_logicpanel() {
    log_step "Step 4/6: Deploying LogicPanel"
    
    mkdir -p $INSTALL_DIR
    cd $INSTALL_DIR
    
    # Create docker-compose.yml
    cat > docker-compose.yml << EOF
version: '3.8'

services:
  logicpanel:
    image: ghcr.io/${GITHUB_REPO}:latest
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
EOF

    # Create .env file
    cat > .env << EOF
# LogicPanel Configuration
# Generated: $(date)

PANEL_DOMAIN=${PANEL_DOMAIN}
ADMIN_EMAIL=${ADMIN_EMAIL}
ADMIN_NAME=${ADMIN_NAME}

# Database
DB_PASSWORD=${DB_PASSWORD}

# Security
APP_SECRET=${APP_SECRET}

# WHMCS Integration
API_KEY=${API_KEY}
API_SECRET=${API_SECRET}
EOF

    chmod 600 .env
    
    log_info "Pulling Docker images..."
    docker compose pull --quiet 2>/dev/null || true
    
    log_info "Starting containers..."
    docker compose up -d --quiet-pull 2>/dev/null || docker compose up -d
    
    log_success "LogicPanel deployed"
}

# ============================================
# Create CLI Command
# ============================================
create_cli_command() {
    log_step "Step 5/6: Creating CLI Commands"
    
    # Create logicpanel CLI
    cat > /usr/local/bin/logicpanel << 'CLIPANEL'
#!/bin/bash

INSTALL_DIR="/opt/logicpanel"
cd $INSTALL_DIR

case "$1" in
    start)
        docker compose up -d
        echo "LogicPanel started"
        ;;
    stop)
        docker compose down
        echo "LogicPanel stopped"
        ;;
    restart)
        docker compose restart
        echo "LogicPanel restarted"
        ;;
    logs)
        docker compose logs -f ${2:-logicpanel}
        ;;
    status)
        docker compose ps
        ;;
    update)
        docker compose pull
        docker compose up -d
        echo "LogicPanel updated"
        ;;
    credentials)
        cat $INSTALL_DIR/.env | grep -E "^(API_KEY|API_SECRET|ADMIN_)" | sed 's/=/ = /'
        ;;
    *)
        echo "LogicPanel CLI"
        echo ""
        echo "Usage: logicpanel <command>"
        echo ""
        echo "Commands:"
        echo "  start       Start LogicPanel"
        echo "  stop        Stop LogicPanel"
        echo "  restart     Restart LogicPanel"
        echo "  logs        View logs (logicpanel logs [service])"
        echo "  status      Show container status"
        echo "  update      Pull latest images and restart"
        echo "  credentials Show current API credentials"
        echo ""
        ;;
esac
CLIPANEL

    chmod +x /usr/local/bin/logicpanel
    log_success "Created 'logicpanel' command"
    
    # Create whmcs CLI for credential management
    cat > /usr/local/bin/whmcs << 'CLIWHMCS'
#!/bin/bash

INSTALL_DIR="/opt/logicpanel"
ENV_FILE="$INSTALL_DIR/.env"

generate_api_key() {
    echo "lp_$(openssl rand -hex 16)"
}

generate_api_secret() {
    openssl rand -hex 32
}

case "$1" in
    generate)
        if [ "$2" == "new" ]; then
            echo ""
            echo "WARNING: This will invalidate the current API credentials!"
            echo "Any WHMCS servers using these credentials will stop working."
            echo ""
            read -p "Are you sure? (yes/no): " CONFIRM
            
            if [ "$CONFIRM" != "yes" ]; then
                echo "Cancelled."
                exit 0
            fi
            
            NEW_API_KEY=$(generate_api_key)
            NEW_API_SECRET=$(generate_api_secret)
            
            # Update .env file
            sed -i "s/^API_KEY=.*/API_KEY=$NEW_API_KEY/" $ENV_FILE
            sed -i "s/^API_SECRET=.*/API_SECRET=$NEW_API_SECRET/" $ENV_FILE
            
            # Restart to apply
            cd $INSTALL_DIR && docker compose restart logicpanel
            
            echo ""
            echo "============================================================"
            echo "  NEW WHMCS API CREDENTIALS GENERATED"
            echo "============================================================"
            echo ""
            echo "  API Key:    $NEW_API_KEY"
            echo "  API Secret: $NEW_API_SECRET"
            echo ""
            echo "============================================================"
            echo "  Update these in WHMCS -> Setup -> Servers -> LogicPanel"
            echo "============================================================"
            echo ""
        else
            echo "Usage: whmcs generate new"
        fi
        ;;
    show)
        echo ""
        echo "============================================================"
        echo "  CURRENT WHMCS API CREDENTIALS"
        echo "============================================================"
        echo ""
        API_KEY=$(grep "^API_KEY=" $ENV_FILE | cut -d'=' -f2)
        API_SECRET=$(grep "^API_SECRET=" $ENV_FILE | cut -d'=' -f2)
        echo "  API Key:    $API_KEY"
        echo "  API Secret: $API_SECRET"
        echo ""
        echo "============================================================"
        echo ""
        ;;
    *)
        echo ""
        echo "WHMCS Credential Manager for LogicPanel"
        echo ""
        echo "Usage: whmcs <command>"
        echo ""
        echo "Commands:"
        echo "  show            Show current API credentials"
        echo "  generate new    Generate new API credentials"
        echo ""
        ;;
esac
CLIWHMCS

    chmod +x /usr/local/bin/whmcs
    log_success "Created 'whmcs' command"
}

# ============================================
# Show Summary
# ============================================
show_summary() {
    log_step "Step 6/6: Installation Complete!"
    
    # Wait for containers to be healthy
    log_info "Waiting for services to start..."
    sleep 10
    
    # Get panel URL
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
    echo -e "  ------------------------------------------------------------"
    echo -e "  Hostname:    ${CYAN}${PANEL_DOMAIN}${NC}"
    echo -e "  Secure:      ${CYAN}Yes (SSL)${NC}"
    echo -e "  API Key:     ${YELLOW}${API_KEY}${NC}"
    echo -e "  API Secret:  ${YELLOW}${API_SECRET}${NC}"
    echo -e "  ------------------------------------------------------------"
    echo ""
    echo -e "${WHITE}CLI Commands${NC}"
    echo -e "  ${CYAN}logicpanel start${NC}     - Start panel"
    echo -e "  ${CYAN}logicpanel stop${NC}      - Stop panel"
    echo -e "  ${CYAN}logicpanel logs${NC}      - View logs"
    echo -e "  ${CYAN}logicpanel update${NC}    - Update to latest"
    echo -e "  ${CYAN}whmcs show${NC}           - Show API credentials"
    echo -e "  ${CYAN}whmcs generate new${NC}   - Generate new credentials"
    echo ""
    echo -e "${WHITE}Installation Directory${NC}"
    echo -e "  ${INSTALL_DIR}"
    echo ""
    echo -e "${YELLOW}------------------------------------------------------------${NC}"
    echo -e "${WHITE}  IMPORTANT: Save these credentials! They are stored in:${NC}"
    echo -e "${WHITE}  ${INSTALL_DIR}/.env${NC}"
    echo -e "${YELLOW}------------------------------------------------------------${NC}"
    echo ""
    echo -e "${GREEN}SSL certificate will be issued automatically within 2-5 minutes.${NC}"
    echo ""
}

# ============================================
# Main Installation
# ============================================
main() {
    show_banner
    
    log_info "Starting installation..."
    echo ""
    
    # Pre-checks
    check_root
    detect_os
    
    # Create install directory
    mkdir -p $INSTALL_DIR
    
    # Run installation steps (6 steps)
    install_docker
    setup_nginx_proxy
    get_configuration
    deploy_logicpanel
    create_cli_command
    show_summary
}

# Run main
main "$@"
