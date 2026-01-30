#!/usr/bin/env bash

# LogicPanel - One-Line Installer
# Author: LogicDock
# Description: Automated, zero-hassle installer for LogicPanel with Docker, Nginx Proxy, and SSL.
# License: Proprietary

set -e

# --- Configuration ---
INSTALL_DIR="/opt/logicpanel"
NGINX_PROXY_DIR="/opt/nginx-proxy"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Helpers
log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }
generate_random() { cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w "$1" | head -n 1; }

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
echo -e "--- ${YELLOW}LogicPanel Automated Installation${NC} ---\n"

# --- 2. Step 1: Dependencies ---
log_info "Step 1: Preparing Docker Environment..."
if command -v docker &> /dev/null; then
    log_success "Docker is already installed."
else
    log_info "Installing Docker..."
    curl -fsSL https://get.docker.com | sh
    systemctl enable --now docker
    log_success "Docker installed."
fi

if ! command -v git &> /dev/null; then
    log_info "Installing Git..."
    if [ -f /etc/debian_version ]; then
        apt-get update && apt-get install -y git
    elif [ -f /etc/redhat-release ]; then
        yum install -y git
    fi
    log_success "Git installed."
fi

if ! docker compose version &> /dev/null; then
    log_info "Installing Docker Compose Plugin..."
    mkdir -p /usr/libexec/docker/cli-plugins
    curl -SL https://github.com/docker/compose/releases/download/v2.24.5/docker-compose-linux-x86_64 -o /usr/libexec/docker/cli-plugins/docker-compose
    chmod +x /usr/libexec/docker/cli-plugins/docker-compose
    
    # Fallback for some systems
    mkdir -p ~/.docker/cli-plugins
    cp /usr/libexec/docker/cli-plugins/docker-compose ~/.docker/cli-plugins/docker-compose
fi

# --- 3. Step 2: Nginx Reverse Proxy ---
log_info "Step 2: Configuring Reverse Proxy (Nginx + Let's Encrypt)..."
docker network inspect nginx-proxy_web &>/dev/null || docker network create nginx-proxy_web

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

# --- 4. Step 3: Interaction ---
log_info "Step 3: Panel Setup (Interactive)"

# Redirect stdin from tty to allow interactive input when piped
exec 3<&0
exec < /dev/tty

echo ""
read -p "--- Enter Hostname (e.g., panel.example.cloud): " PANEL_DOMAIN
while [[ -z "$PANEL_DOMAIN" ]]; do
    read -p "--- ! Hostname required: " PANEL_DOMAIN
done

RANDOM_ADMIN="admin_$(generate_random 5)"
read -p "--- Enter Admin Username (default: $RANDOM_ADMIN): " ADMIN_USER
ADMIN_USER=${ADMIN_USER:-$RANDOM_ADMIN}

read -p "--- Enter Admin Email: " ADMIN_EMAIL
while [[ -z "$ADMIN_EMAIL" ]]; do
    read -p "--- ! Email required: " ADMIN_EMAIL
done

while true; do
    read -s -p "--- Enter Admin Password (min 8 characters): " ADMIN_PASS
    echo ""
    if [[ ${#ADMIN_PASS} -lt 8 ]]; then
        echo -e "${RED}--- ! Password too short. Min 8 characters.${NC}"
        continue
    fi
    read -s -p "--- Enter Admin Password Again: " ADMIN_PASS_CONFIRM
    echo ""
    if [[ "$ADMIN_PASS" == "$ADMIN_PASS_CONFIRM" ]]; then
        break
    else
        echo -e "${RED}--- ! Passwords do not match. Try again.${NC}"
    fi
done

# Restore original stdin
exec <&3
exec 3<&-

# Random Secrets for Security
DB_NAME="lp_db_$(generate_random 8)"
DB_USER="lp_user_$(generate_random 8)"
DB_PASS=$(generate_random 32)
ROOT_PASS=$(generate_random 32)
JWT_SECRET=$(generate_random 64)
ENC_KEY=$(generate_random 32)

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

# Fetch source code to avoid Git dependencies for builds
log_info "Fetching latest source code..."
curl -sSL https://github.com/LogicDock/LogicPanel/archive/refs/heads/main.tar.gz | tar xz --strip-components=1

# --- 5. Step 4: Deployment ---
cat > docker-compose.yml << EOF
version: '3.8'

services:
  app:
    build: .
    container_name: logicpanel_app
    restart: always
    ports:
      - "\${MASTER_PORT:-999}:\${MASTER_PORT:-999}"
      - "\${USER_PORT:-777}:\${USER_PORT:-777}"
    environment:
      VIRTUAL_HOST: ${PANEL_DOMAIN}
      VIRTUAL_PORT: 80
      LETSENCRYPT_HOST: ${PANEL_DOMAIN}
      LETSENCRYPT_EMAIL: ${ADMIN_EMAIL}
      DB_CONNECTION: mysql
      DB_HOST: logicpanel_db
      DB_PORT: 3306
      DB_DATABASE: ${DB_NAME}
      DB_USERNAME: ${DB_USER}
      DB_PASSWORD: ${DB_PASS}
      MYSQL_CONTAINER: lp-mysql-mother
      POSTGRES_CONTAINER: lp-postgres-mother
      MONGO_CONTAINER: lp-mongo-mother
      JWT_SECRET: ${JWT_SECRET}
      ENCRYPTION_KEY: ${ENC_KEY}
      APP_URL: https://${PANEL_DOMAIN}
      MASTER_PORT: 999
      USER_PORT: 777
      APP_ENV: production
    volumes:
      - ./storage:/var/www/html/storage
      - /var/run/docker.sock:/var/run/docker.sock:rw
      - certs:/etc/nginx/certs:ro
    networks:
      - nginx-proxy_web
      - internal
    depends_on:
      - logicpanel_db
      - mysql
      - postgres
      - mongo
      - redis

  terminal-gateway:
    build: ./services/gateway
    container_name: logicpanel_gateway
    restart: always
    ports:
      - "3002:3002"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock
    environment:
      JWT_SECRET: ${JWT_SECRET}
    networks:
      - nginx-proxy_web

  logicpanel_db:
    image: mariadb:10.11
    container_name: logicpanel_db
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: ${ROOT_PASS}
      MYSQL_DATABASE: ${DB_NAME}
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASS}
    volumes:
      - ./mysql_data_main:/var/lib/mysql
    networks:
      - internal

  mysql:
    image: mariadb:11.2
    container_name: lp-mysql-mother
    restart: always
    ports:
      - "3306:3306"
    environment:
      MYSQL_ROOT_PASSWORD: ${ROOT_PASS}
    volumes:
      - ./mysql_data_mother:/var/lib/mysql
    networks:
      - internal
      - nginx-proxy_web

  postgres:
    image: postgres:16-alpine
    container_name: lp-postgres-mother
    restart: always
    ports:
      - "5432:5432"
    environment:
      POSTGRES_PASSWORD: ${ROOT_PASS}
    volumes:
      - ./postgres_data:/var/lib/postgresql/data
    networks:
      - internal
      - nginx-proxy_web

  mongo:
    image: mongo:7.0
    container_name: lp-mongo-mother
    restart: always
    ports:
      - "27017:27017"
    environment:
      MONGO_INITDB_ROOT_PASSWORD: ${ROOT_PASS}
      MONGO_INITDB_ROOT_USERNAME: root
    volumes:
      - ./mongo_data:/data/db
    networks:
      - internal
      - nginx-proxy_web

  redis:
    image: redis:7-alpine
    container_name: logicpanel_redis
    restart: always
    networks:
      - internal

  db-provisioner:
    build: ./docker/db-provisioner
    container_name: logicpanel_db_provisioner
    restart: always
    environment:
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
    driver: bridge

EOF

# Create storage layout (if not already fetched via tar)
mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views storage/user-apps
chmod -R 777 storage

# Build and start containers with beautiful progress
echo ""
log_info "Building LogicPanel (this may take 3-5 minutes)..."
echo -e "  ${YELLOW}Build logs saved to: /tmp/logicpanel_build.log${NC}"
docker compose build --no-cache > /tmp/logicpanel_build.log 2>&1 &
spinner $! "Compiling LogicPanel Application..."

log_info "Starting Services..."
docker compose up -d > /dev/null 2>&1 &
spinner $! "Launching Docker Containers..."

# 2-minute wait for services to fully initialize
echo ""
log_info "Waiting for services to initialize..."
countdown_progress 120 "Services warming up"

# Download and Inject Admin Setup Script (Now self-contained with embedded schema)
curl -sSL "https://raw.githubusercontent.com/LogicDock/LogicPanel/main/create_admin.php" -o create_admin.php

docker exec logicpanel_app mkdir -p /var/www/html/database
docker cp create_admin.php logicpanel_app:/var/www/html/create_admin.php

rm -f create_admin.php

# Execute Admin Creation
log_info "Creating administrator account..."
docker exec logicpanel_app php /var/www/html/create_admin.php --user="${ADMIN_USER}" --email="${ADMIN_EMAIL}" --pass="${ADMIN_PASS}" > /dev/null 2>&1
docker exec logicpanel_app rm -f /var/www/html/create_admin.php

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
echo -e "${GREEN}║${NC}     Password:    ${CYAN}${ADMIN_PASS}${NC}"
echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
echo -e "${GREEN}╠════════════════════════════════════════════════════════════════╣${NC}"
echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}  ${YELLOW}ℹ️  IMPORTANT NOTES${NC}                                          ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     • First access may show SSL warning                        ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}       Click 'Advanced' > 'Proceed' to continue                 ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     • Database secured with random credentials                 ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${CYAN}Thank you for choosing LogicPanel by LogicDock.cloud${NC} 💙"
echo ""
