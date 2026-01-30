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

if ! docker compose version &> /dev/null; then
    log_info "Installing Docker Compose Plugin..."
    if [ -f /etc/debian_version ]; then
        apt-get update && apt-get install -y docker-compose-plugin
    elif [ -f /etc/redhat-release ]; then
        yum install -y docker-compose-plugin
    fi
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

log_info "Step 4: Deploying LogicPanel Services..."
mkdir -p $INSTALL_DIR
cd $INSTALL_DIR

# --- 5. Step 4: Deployment ---
cat > docker-compose.yml << EOF
version: '3.8'

services:
  app:
    image: ghcr.io/logicdock/logicpanel:latest
    container_name: logicpanel_app
    restart: always
    environment:
      VIRTUAL_HOST: ${PANEL_DOMAIN}
      LETSENCRYPT_HOST: ${PANEL_DOMAIN}
      LETSENCRYPT_EMAIL: ${ADMIN_EMAIL}
      DB_CONNECTION: mysql
      DB_HOST: logicpanel_db
      DB_PORT: 3306
      DB_DATABASE: ${DB_NAME}
      DB_USERNAME: ${DB_USER}
      DB_PASSWORD: ${DB_PASS}
      JWT_SECRET: ${JWT_SECRET}
      ENCRYPTION_KEY: ${ENC_KEY}
      APP_URL: https://${PANEL_DOMAIN}
      MASTER_PORT: 967
      USER_PORT: 676
      APP_ENV: production
    volumes:
      - ./storage:/var/www/html/storage
      - /var/run/docker.sock:/var/run/docker.sock:rw
    networks:
      - nginx-proxy_web
      - internal

  logicpanel_db:
    image: mysql:8.0
    container_name: logicpanel_db
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: ${ROOT_PASS}
      MYSQL_DATABASE: ${DB_NAME}
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASS}
    volumes:
      - ./mysql_data:/var/lib/mysql
    networks:
      - internal

networks:
  nginx-proxy_web:
    external: true
  internal:
    driver: bridge

EOF

# Create storage layout
mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views storage/user-apps
chmod -R 777 storage

log_info "Pulling and starting containers..."
docker compose pull
docker compose up -d

log_info "Finalizing configuration (waiting 20s for DB)..."
sleep 20

# Download and Inject Admin Setup Script (ensures it's present even if image is old)
curl -sSL "https://raw.githubusercontent.com/LogicDock/LogicPanel/main/create_admin.php" -o create_admin.php
docker cp create_admin.php logicpanel_app:/var/www/html/create_admin.php
rm create_admin.php

# Execute Admin Creation
docker exec logicpanel_app php create_admin.php --user="${ADMIN_USER}" --email="${ADMIN_EMAIL}" --pass="${ADMIN_PASS}"

log_success "LogicPanel is now LIVE!"
echo -e "\n${GREEN}============================================================${NC}"
echo -e "  Panel URL:   ${CYAN}https://${PANEL_DOMAIN}${NC}"
echo -e "  Admin User:  ${CYAN}${ADMIN_USER}${NC}"
echo -e "  Admin Email: ${CYAN}${ADMIN_EMAIL}${NC}"
echo -e "  Admin Pass:  ${CYAN}${ADMIN_PASS}${NC}"
echo -e "${GREEN}============================================================${NC}"
echo -e "  ${YELLOW}Internal Ports:${NC} Master: 967 | User: 676"
echo -e "  ${YELLOW}Database Info:${NC} Secured with random credentials."
echo -e "${GREEN}============================================================${NC}\n"
echo -e "Thank you for choosing LogicPanel by LogicDock.cloud"
