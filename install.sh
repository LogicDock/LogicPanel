#!/usr/bin/env bash

# LogicPanel - One-Line Installer v2.0
# Author: LogicDock
# Description: Automated, zero-hassle installer for LogicPanel with Docker, Nginx Proxy, and SSL.
# Supports: Debian/Ubuntu (apt), RHEL/CentOS/Fedora (dnf/yum), Arch (pacman)
# License: Proprietary

set -e

# --- Configuration ---
INSTALL_DIR="/opt/logicpanel"
NGINX_PROXY_DIR="/opt/nginx-proxy"
VERSION="2.0.0"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m'

# Helpers
log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }
generate_random() { cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w "$1" | head -n 1; }

# Detect Package Manager
detect_package_manager() {
    if command -v apt-get &> /dev/null; then
        PKG_MANAGER="apt"
        PKG_UPDATE="apt-get update -qq"
        PKG_INSTALL="apt-get install -y -qq"
    elif command -v dnf &> /dev/null; then
        PKG_MANAGER="dnf"
        PKG_UPDATE="dnf check-update || true"
        PKG_INSTALL="dnf install -y -q"
    elif command -v yum &> /dev/null; then
        PKG_MANAGER="yum"
        PKG_UPDATE="yum check-update || true"
        PKG_INSTALL="yum install -y -q"
    elif command -v pacman &> /dev/null; then
        PKG_MANAGER="pacman"
        PKG_UPDATE="pacman -Sy --noconfirm"
        PKG_INSTALL="pacman -S --noconfirm"
    else
        log_error "Unsupported package manager. Please install Docker manually."
        exit 1
    fi
    log_success "Detected package manager: $PKG_MANAGER"
}

# Spinner for long-running tasks
spinner() {
    local pid=$1
    local delay=0.15
    local spinstr='⠋⠙⠹⠸⠼⠴⠦⠧⠇⠏'
    while ps -p $pid > /dev/null 2>&1; do
        for i in $(seq 0 9); do
            printf "\r  ${CYAN}%s${NC} %s" "${spinstr:$i:1}" "$2"
            sleep $delay
        done
    done
    printf "\r  ${GREEN}✓${NC} %s\n" "$2"
}

# Progress bar countdown
countdown_progress() {
    local seconds=$1
    local message=$2
    local width=40
    for ((i=0; i<=seconds; i++)); do
        local pct=$((i * 100 / seconds))
        local filled=$((i * width / seconds))
        local empty=$((width - filled))
        local bar=$(printf "%${filled}s" | tr ' ' '█')$(printf "%${empty}s" | tr ' ' '░')
        local remaining=$((seconds - i))
        printf "\r  ${CYAN}[${bar}]${NC} ${pct}%% - ${message} (${remaining}s remaining)"
        sleep 1
    done
    printf "\r  ${GREEN}[$(printf "%${width}s" | tr ' ' '█')]${NC} 100%% - ${message}            \n"
}

# Check port availability
check_port() {
    local port=$1
    if ss -tuln | grep -q ":$port "; then
        return 1
    fi
    return 0
}

# Wait for container to be healthy
wait_for_container() {
    local container=$1
    local max_wait=${2:-60}
    local waited=0
    
    while [ $waited -lt $max_wait ]; do
        if docker inspect --format='{{.State.Running}}' "$container" 2>/dev/null | grep -q "true"; then
            return 0
        fi
        sleep 2
        waited=$((waited + 2))
    done
    return 1
}

# --- 1. Root Check ---
if [[ $EUID -ne 0 ]]; then
   log_error "This script must be run as root. Try: sudo bash <(curl -sSL https://raw.githubusercontent.com/LogicDock/LogicPanel/main/install.sh)"
   exit 1
fi

clear
echo -e "${CYAN}"
echo "██╗      ██████╗  ██████╗ ██╗ ██████╗██████╗  █████╗ ███╗   ██╗███████╗██╗     "
echo "██║     ██╔═══██╗██╔════╝ ██║██╔════╝██╔══██╗██╔══██╗████╗  ██║██╔════╝██║     "
echo "██║     ██║   ██║██║  ███╗██║██║     ██████╔╝███████║██╔██╗ ██║█████╗  ██║     "
echo "██║     ██║   ██║██║   ██║██║██║     ██╔═══╝ ██╔══██║██║╚██╗██║██╔══╝  ██║     "
echo "███████╗╚██████╔╝╚██████╔╝██║╚██████╗██║     ██║  ██║██║ ╚████║███████╗███████╗"
echo "╚══════╝ ╚═════╝  ╚═════╝ ╚═╝ ╚═════╝╚═╝     ╚═╝  ╚═╝╚═╝  ╚═══╝╚══════╝╚══════╝"
echo -e "${NC}"
echo -e "--- ${YELLOW}LogicPanel Automated Installation v${VERSION}${NC} ---\n"

# --- 2. System Preparation ---
log_info "Step 1: Preparing System Environment..."
detect_package_manager

# Check required ports
REQUIRED_PORTS=(80 443 999 777 3306 5432 27017)
for port in "${REQUIRED_PORTS[@]}"; do
    if ! check_port $port; then
        log_warn "Port $port is already in use. Installation may have conflicts."
    fi
done

# Install Docker
if command -v docker &> /dev/null; then
    log_success "Docker is already installed."
else
    log_info "Installing Docker..."
    curl -fsSL https://get.docker.com | sh
    systemctl enable --now docker
    log_success "Docker installed."
fi

# Install Git and other dependencies
if ! command -v git &> /dev/null; then
    log_info "Installing Git..."
    $PKG_INSTALL git
    log_success "Git installed."
fi

# Install curl if missing (rare but possible)
if ! command -v curl &> /dev/null; then
    log_info "Installing curl..."
    $PKG_INSTALL curl
fi

# Docker Compose Plugin
if ! docker compose version &> /dev/null 2>&1; then
    log_info "Installing Docker Compose Plugin..."
    mkdir -p /usr/libexec/docker/cli-plugins
    COMPOSE_VERSION="v2.24.5"
    ARCH=$(uname -m)
    if [ "$ARCH" = "aarch64" ]; then
        ARCH="aarch64"
    else
        ARCH="x86_64"
    fi
    curl -SL "https://github.com/docker/compose/releases/download/${COMPOSE_VERSION}/docker-compose-linux-${ARCH}" -o /usr/libexec/docker/cli-plugins/docker-compose
    chmod +x /usr/libexec/docker/cli-plugins/docker-compose
    
    # Fallback for some systems
    mkdir -p ~/.docker/cli-plugins
    cp /usr/libexec/docker/cli-plugins/docker-compose ~/.docker/cli-plugins/docker-compose
    log_success "Docker Compose installed."
fi

# --- 3. Step 2: Nginx Reverse Proxy ---
log_info "Step 2: Configuring Reverse Proxy (Nginx + Let's Encrypt)..."

# Create networks
docker network inspect nginx-proxy_web &>/dev/null || docker network create nginx-proxy_web
docker network inspect logicpanel_internal &>/dev/null || docker network create logicpanel_internal
log_success "Docker networks configured."

if docker ps -a --format '{{.Names}}' | grep -q "^nginx-proxy$"; then
    log_warn "Nginx Proxy is already active. Using existing one."
else
    log_info "Deploying Nginx Proxy Manager..."
    mkdir -p $NGINX_PROXY_DIR
    cat > $NGINX_PROXY_DIR/docker-compose.yml << 'EOF'
version: '3'
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
      - certs:/etc/nginx/certs
      - vhost:/etc/nginx/vhost.d
      - html:/usr/share/nginx/html
    networks:
      - nginx-proxy_web
    labels:
      - "com.github.jrcs.letsencrypt_nginx_proxy_companion.nginx_proxy=true"

  letsencrypt:
    image: jrcs/letsencrypt-nginx-proxy-companion:latest
    container_name: nginx-letsencrypt
    restart: always
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - certs:/etc/nginx/certs
      - vhost:/etc/nginx/vhost.d
      - html:/usr/share/nginx/html
      - acme:/etc/acme.sh
    networks:
      - nginx-proxy_web
    depends_on:
      - nginx-proxy

volumes:
  certs:
  vhost:
  html:
  acme:

networks:
  nginx-proxy_web:
    external: true
EOF
    (cd $NGINX_PROXY_DIR && docker compose up -d)
    log_success "Proxy deployed."
fi

# --- 4. Step 3: User Input ---
log_info "Step 3: Panel Setup (Interactive)"

echo ""
# Use standard read -p with direct /dev/tty redirection
read -p "--- Enter Hostname (e.g., panel.example.cloud): " PANEL_DOMAIN < /dev/tty
while [[ -z "$PANEL_DOMAIN" ]]; do
    read -p "--- ! Hostname required: " PANEL_DOMAIN < /dev/tty
done

RANDOM_ADMIN="admin_$(generate_random 5)"
read -p "--- Enter Admin Username (default: $RANDOM_ADMIN): " ADMIN_USER < /dev/tty
ADMIN_USER=${ADMIN_USER:-$RANDOM_ADMIN}

read -p "--- Enter Admin Email: " ADMIN_EMAIL < /dev/tty
while [[ -z "$ADMIN_EMAIL" ]]; do
    read -p "--- ! Email required: " ADMIN_EMAIL < /dev/tty
done

while true; do
    read -s -p "--- Enter Admin Password (min 8 characters): " ADMIN_PASS < /dev/tty
    echo ""
    if [[ ${#ADMIN_PASS} -lt 8 ]]; then
        echo -e "${RED}--- ! Password too short. Min 8 characters.${NC}"
        continue
    fi
    read -s -p "--- Enter Admin Password Again: " ADMIN_PASS_CONFIRM < /dev/tty
    echo ""
    if [[ "$ADMIN_PASS" == "$ADMIN_PASS_CONFIRM" ]]; then
        break
    else
        echo -e "${RED}--- ! Passwords do not match. Try again.${NC}"
    fi
done

# Random Secrets for Security
DB_NAME="lp_db_$(generate_random 8)"
DB_USER="lp_user_$(generate_random 8)"
DB_PASS=$(generate_random 32)
ROOT_PASS=$(generate_random 32)
JWT_SECRET=$(generate_random 64)
# Generate proper 32-byte key for libsodium, base64 encoded
ENC_KEY=$(head -c 32 /dev/urandom | base64 -w 0)
DB_PROVISIONER_SECRET=$(generate_random 64)

# Detect existing nginx-proxy certificate volume
CERT_VOLUME="nginx-proxy_certs"
if docker ps --format '{{.Names}}' | grep -q "^nginx-proxy$"; then
    log_info "Detecting existing nginx-proxy volumes..."
    DETECTED_VOLUME=$(docker inspect nginx-proxy --format '{{ range .Mounts }}{{ if eq .Destination "/etc/nginx/certs" }}{{ .Name }}{{ end }}{{ end }}')
    if [ ! -z "$DETECTED_VOLUME" ]; then
        CERT_VOLUME="$DETECTED_VOLUME"
        log_success "Using existing certificate volume: $CERT_VOLUME"
    fi
fi

log_info "Step 4: Deploying LogicPanel Services..."
mkdir -p $INSTALL_DIR
cd $INSTALL_DIR

# Fetch source code
log_info "Fetching latest source code..."
curl -sSL https://github.com/LogicDock/LogicPanel/archive/refs/heads/main.tar.gz | tar xz --strip-components=1

      - internal

  db-provisioner:
    build: ./docker/db-provisioner
    container_name: logicpanel_db_provisioner
    restart: always
    environment:
      - DB_PROVISIONER_SECRET=${DB_PROVISIONER_SECRET}
      - MYSQL_ROOT_PASSWORD=${ROOT_PASS}
      - POSTGRES_ROOT_PASSWORD=${ROOT_PASS}
      - MONGO_ROOT_PASSWORD=${ROOT_PASS}
    networks:
      - internal
    depends_on:
      - mysql
      - postgres
      - mongo

volumes:
  certs:
    external: true
    name: ${CERT_VOLUME}

networks:
  nginx-proxy_web:
    external: true
  internal:
    name: logicpanel_internal
    external: true

EOF

# Create storage layout
mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views storage/user-apps
chmod -R 777 storage

# Create config directory and settings.json
mkdir -p config
cat > config/settings.json << EOF
{
    "hostname": "${PANEL_DOMAIN}",
    "master_port": "999",
    "user_port": "777",
    "company_name": "LogicPanel",
    "contact_email": "${ADMIN_EMAIL}",
    "enable_ssl": "1",
    "letsencrypt_email": "${ADMIN_EMAIL}",
    "timezone": "UTC",
    "allow_registration": "1"
}
EOF

# Build and start containers
echo ""
log_info "Building LogicPanel (this may take 3-5 minutes)..."
echo -e "  ${YELLOW}Build logs saved to: /tmp/logicpanel_build.log${NC}"
docker compose build --no-cache > /tmp/logicpanel_build.log 2>&1 &
spinner $! "Compiling LogicPanel Application..."

log_info "Starting Services..."
docker compose up -d > /dev/null 2>&1 &
spinner $! "Launching Docker Containers..."

# Wait for services to initialize
echo ""
log_info "Waiting for services to initialize..."
countdown_progress 90 "Services warming up"

# Verify containers are running
log_info "Verifying container status..."
REQUIRED_CONTAINERS=("logicpanel_app" "logicpanel_db" "logicpanel_gateway" "lp-mysql-mother" "lp-postgres-mother" "lp-mongo-mother" "logicpanel_redis")
ALL_RUNNING=true

for container in "${REQUIRED_CONTAINERS[@]}"; do
    if docker ps --format '{{.Names}}' | grep -q "^${container}$"; then
        log_success "$container is running"
    else
        log_error "$container failed to start"
        ALL_RUNNING=false
    fi
done

if [ "$ALL_RUNNING" = false ]; then
    log_error "Some containers failed to start. Check logs with: docker compose logs"
    exit 1
fi

# Download and Inject Admin Setup Script
curl -sSL "https://raw.githubusercontent.com/LogicDock/LogicPanel/main/create_admin.php" -o create_admin.php
docker exec logicpanel_app mkdir -p /var/www/html/database
docker cp create_admin.php logicpanel_app:/var/www/html/create_admin.php
docker cp config/settings.json logicpanel_app:/var/www/html/config/settings.json
rm -f create_admin.php

# Execute Admin Creation
log_info "Creating administrator account..."
docker exec logicpanel_app php /var/www/html/create_admin.php --user="${ADMIN_USER}" --email="${ADMIN_EMAIL}" --pass="${ADMIN_PASS}" > /dev/null 2>&1
docker exec logicpanel_app rm -f /var/www/html/create_admin.php

# Final setup - ensure gateway is on both networks
docker network connect nginx-proxy_web logicpanel_gateway 2>/dev/null || true
docker network connect logicpanel_internal logicpanel_gateway 2>/dev/null || true

# Success Message
clear
echo -e "${CYAN}"
echo "██╗      ██████╗  ██████╗ ██╗ ██████╗██████╗  █████╗ ███╗   ██╗███████╗██╗     "
echo "██║     ██╔═══██╗██╔════╝ ██║██╔════╝██╔══██╗██╔══██╗████╗  ██║██╔════╝██║     "
echo "██║     ██║   ██║██║  ███╗██║██║     ██████╔╝███████║██╔██╗ ██║█████╗  ██║     "
echo "██║     ██║   ██║██║   ██║██║██║     ██╔═══╝ ██╔══██║██║╚██╗██║██╔══╝  ██║     "
echo "███████╗╚██████╔╝╚██████╔╝██║╚██████╗██║     ██║  ██║██║ ╚████║███████╗███████╗"
echo "╚══════╝ ╚═════╝  ╚═════╝ ╚═╝ ╚═════╝╚═╝     ╚═╝  ╚═╝╚═╝  ╚═══╝╚══════╝╚══════╝"
echo -e "${NC}"

echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}           ${CYAN}✨ INSTALLATION SUCCESSFUL! ✨${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}╠════════════════════════════════════════════════════════════════╣${NC}"
echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}  ${YELLOW}🌐 PANEL ACCESS LINKS${NC}                                        ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     Master Panel:  ${CYAN}https://${PANEL_DOMAIN}:999${NC}"
echo -e "${GREEN}║${NC}     User Panel:    ${CYAN}https://${PANEL_DOMAIN}:777${NC}"
echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
echo -e "${GREEN}╠════════════════════════════════════════════════════════════════╣${NC}"
echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}  ${YELLOW}🔐 ADMIN CREDENTIALS${NC}                                         ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     Username:    ${CYAN}${ADMIN_USER}${NC}"
echo -e "${GREEN}║${NC}     Email:       ${CYAN}${ADMIN_EMAIL}${NC}"
echo -e "${GREEN}║${NC}     Password:    ${CYAN}(the password you entered)${NC}"
echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
echo -e "${GREEN}╠════════════════════════════════════════════════════════════════╣${NC}"
echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}  ${YELLOW}📦 INSTALLED SERVICES${NC}                                        ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     • LogicPanel Application                                  ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     • Terminal Gateway (WebSocket)                            ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     • MariaDB (MySQL)    - Port 3306                          ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     • PostgreSQL         - Port 5432                          ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     • MongoDB            - Port 27017                         ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     • Redis Cache                                             ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     • Database Provisioner                                    ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
echo -e "${GREEN}╠════════════════════════════════════════════════════════════════╣${NC}"
echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}  ${YELLOW}ℹ️  IMPORTANT NOTES${NC}                                          ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     • SSL certificate will be auto-generated                  ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     • First access may take 1-2 minutes for SSL               ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     • Ensure DNS A record points to this server               ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${CYAN}Thank you for choosing LogicPanel by LogicDock.cloud${NC} 💙"
echo ""
echo -e "  ${MAGENTA}📚 Documentation: https://docs.logicdock.cloud${NC}"
echo -e "  ${MAGENTA}🐛 Report Issues: https://github.com/LogicDock/LogicPanel/issues${NC}"
echo ""
