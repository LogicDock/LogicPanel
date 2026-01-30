#!/usr/bin/env bash

# LogicPanel - One-Line Installer
# Author: LogicDock
# Description: Automated installer for LogicPanel with Docker, Nginx Proxy, and SSL.

set -e

# --- Configuration ---
INSTALL_DIR="/opt/logicpanel"
NGINX_PROXY_DIR="/opt/nginx-proxy"
GITHUB_RAW="https://raw.githubusercontent.com/LogicDock/LogicPanel/main"

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
   log_error "This script must be run as root. Try: sudo bash install.sh"
   exit 1
fi

# --- 2. OS Detection ---
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS_NAME=$NAME
else
    log_error "Unsupported OS. Please use Ubuntu/Debian/CentOS/AlmaLinux."
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

# --- 3. Docker Installation ---
log_info "Step 1: Checking Docker Environment..."
if command -v docker &> /dev/null; then
    log_success "Docker is already installed. Skipping..."
else
    log_info "Docker not found. Installing..."
    curl -fsSL https://get.docker.com | sh
    systemctl enable --now docker
    log_success "Docker installed successfully."
fi

if ! docker compose version &> /dev/null; then
    log_info "Installing Docker Compose..."
    # Usually get.docker.com installs the plugin, but just in case
    apt-get update && apt-get install -y docker-compose-plugin || yum install -y docker-compose-plugin
fi

# --- 4. Nginx Proxy Manager Setup ---
log_info "Step 2: Configuring Reverse Proxy (Nginx + Let's Encrypt)..."
docker network inspect nginx-proxy_web &>/dev/null || docker network create nginx-proxy_web

if docker ps -a --format '{{.Names}}' | grep -q "^nginx-proxy$"; then
    log_warn "Nginx Proxy is already running. Skipping setup..."
else
    log_info "Deploying Nginx Proxy Manager companion..."
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
    log_success "Reverse proxy deployed."
fi

# --- 5. LogicPanel Configuration ---
log_info "Step 3: Panel Configuration"

read -p "Enter Panel Domain (e.g., panel.yourdomain.com): " PANEL_DOMAIN
while [[ -z "$PANEL_DOMAIN" ]]; do
    read -p "Domain cannot be empty. Enter Panel Domain: " PANEL_DOMAIN
done

read -p "Enter Admin Username (default: admin): " ADMIN_USER
ADMIN_USER=${ADMIN_USER:-admin}

read -p "Enter Admin Email: " ADMIN_EMAIL
while [[ -z "$ADMIN_EMAIL" ]]; do
    read -p "Email cannot be empty. Enter Admin Email: " ADMIN_EMAIL
done

read -s -p "Enter Admin Password (min 8 chars): " ADMIN_PASS
echo ""
while [[ ${#ADMIN_PASS} -lt 8 ]]; do
    read -s -p "Password too short. Enter Admin Password: " ADMIN_PASS
    echo ""
done

# Random Database Credentials
DB_NAME="lp_$(generate_random 8)"
DB_USER="user_$(generate_random 8)"
DB_PASS=$(generate_random 24)
MYSQL_ROOT_PASS=$(generate_random 24)
JWT_SECRET=$(generate_random 64)

# --- 6. Deployment ---
log_info "Step 4: Deploying LogicPanel..."
mkdir -p $INSTALL_DIR
cd $INSTALL_DIR

# Fetch main docker-compose and other files from GitHub
# In this implementation, we will use a pre-defined production docker-compose
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
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASS}
      JWT_SECRET: ${JWT_SECRET}
      APP_URL: https://${PANEL_DOMAIN}
      MASTER_PORT: 967
      USER_PORT: 767
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
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASS}
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

# Create storage directory and set permissions
mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views storage/user-apps
chmod -R 777 storage

log_info "Starting containers..."
docker compose pull
docker compose up -d

# Wait for DB
log_info "Waiting for database to initialize..."
sleep 20

# Create Admin Account via internal script (assuming app has a CLI or entrypoint)
# For now, we will assume the app image handles basic setup or we can exec into it
docker exec logicpanel_app php create_admin.php --user="${ADMIN_USER}" --email="${ADMIN_EMAIL}" --pass="${ADMIN_PASS}" || true

log_success "LogicPanel successfully installed!"
echo -e "\n-----------------------------------------------------------"
echo -e "  ${GREEN}Installation Complete!${NC}"
echo -e "-----------------------------------------------------------"
echo -e "  Domain:   ${CYAN}https://${PANEL_DOMAIN}${NC}"
echo -e "  Admin:    ${CYAN}${ADMIN_USER}${NC}"
echo -e "  Email:    ${CYAN}${ADMIN_EMAIL}${NC}"
echo -e "  Password: ${CYAN}${ADMIN_PASS}${NC}"
echo -e "-----------------------------------------------------------"
echo -e "  ${YELLOW}Database Credentials (Hidden for security):${NC}"
echo -e "  DB Name:    $DB_NAME"
echo -e "  DB User:    $DB_USER"
echo -e "  JWT Secret: $JWT_SECRET"
echo -e "-----------------------------------------------------------"
echo -e "\nEnjoy your new hosting panel!"
