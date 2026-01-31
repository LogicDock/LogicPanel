#!/usr/bin/env bash

# LogicPanel - Uninstaller v2.0
# Author: LogicDock
# Description: Completely removes LogicPanel and its data.

set -e

INSTALL_DIR="/opt/logicpanel"
NGINX_PROXY_DIR="/opt/nginx-proxy"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# Helpers
log_info() { echo -e "\033[0;34m[INFO]\033[0m $1"; }
log_success() { echo -e "${GREEN}[OK]\033[0m $1"; }
log_warn() { echo -e "${YELLOW}[WARN]\033[0m $1"; }
log_error() { echo -e "${RED}[ERROR]\033[0m $1"; }

# --- 1. Root Check ---
if [[ $EUID -ne 0 ]]; then
   echo "This script must be run as root."
   exit 1
fi

clear
echo -e "${RED}"
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║               LOGICPANEL UNINSTALLER v2.0                     ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

echo -e "${RED}!!! WARNING: THIS WILL PERMANENTLY DELETE ALL DATA !!!${NC}"
echo ""
echo "This will remove:"
echo "  • All LogicPanel containers"
echo "  • All user application containers"
echo "  • All databases and data"
echo "  • All configuration files"
echo ""

# Handle stdin properly when piped
exec 3<&0
if [ -t 0 ]; then :; else
    if [ -c /dev/tty ]; then exec 0</dev/tty; fi
fi

echo -n "Are you sure you want to uninstall LogicPanel? (y/N): "
read CONFIRM

if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
    echo "Uninstall cancelled."
    exit 0
fi

# --- 2. Stop and Remove LogicPanel Containers ---
log_info "Stopping LogicPanel containers..."

# List of LogicPanel containers
CONTAINERS=(
    "logicpanel_app"
    "logicpanel_socket_proxy"
    "logicpanel_gateway"
    "logicpanel_db"
    "logicpanel_redis"
    "logicpanel_db_provisioner"
    "lp-mysql-mother"
    "lp-postgres-mother"
    "lp-mongo-mother"
)

for container in "${CONTAINERS[@]}"; do
    if docker ps -a --format '{{.Names}}' | grep -q "^${container}$"; then
        docker stop "$container" 2>/dev/null || true
        docker rm -f "$container" 2>/dev/null || true
        log_success "Removed $container"
    fi
done

# Remove user app containers (logicpanel_app_service_*)
log_info "Removing user application containers..."
USER_CONTAINERS=$(docker ps -a --format '{{.Names}}' | grep "^logicpanel_app_service_" || true)
for container in $USER_CONTAINERS; do
    docker stop "$container" 2>/dev/null || true
    docker rm -f "$container" 2>/dev/null || true
    log_success "Removed user container: $container"
done

# --- 3. Remove LogicPanel Directory ---
if [ -d "$INSTALL_DIR" ]; then
    log_info "Removing LogicPanel files..."
    
    # Stop any remaining compose services
    (cd "$INSTALL_DIR" && docker compose down -v 2>/dev/null || true)
    
    rm -rf "$INSTALL_DIR"
    log_success "LogicPanel files removed."
else
    log_warn "LogicPanel directory not found at $INSTALL_DIR."
fi

# --- 4. Remove LogicPanel Network ---
log_info "Cleaning up Docker networks..."
docker network rm logicpanel_internal 2>/dev/null || true

# --- 5. Clean up Docker ---
echo -n "Do you want to remove unused Docker images and volumes? (y/N): "
read CLEANUP_CONFIRM
if [[ "$CLEANUP_CONFIRM" =~ ^[Yy]$ ]]; then
    log_info "Cleaning Docker system..."
    docker system prune -f 2>/dev/null || true
    docker volume prune -f 2>/dev/null || true
    log_success "Docker cleanup complete."
fi

# --- 6. Optional Proxy Removal ---
echo -n "Do you also want to remove the Nginx Proxy setup? (y/N): "
read PROXY_CONFIRM

if [[ "$PROXY_CONFIRM" =~ ^[Yy]$ ]]; then
    if [ -d "$NGINX_PROXY_DIR" ]; then
        log_info "Stopping Nginx Reverse Proxy..."
        (cd "$NGINX_PROXY_DIR" && docker compose down -v 2>/dev/null || true)
        
        # Remove nginx-proxy containers
        docker stop nginx-proxy nginx-letsencrypt 2>/dev/null || true
        docker rm -f nginx-proxy nginx-letsencrypt 2>/dev/null || true
        
        rm -rf "$NGINX_PROXY_DIR"
        log_success "Reverse proxy removed."
    fi
    docker network rm nginx-proxy_web 2>/dev/null || true
fi

# --- Success Message ---
echo ""
echo -e "${GREEN}╔═══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║           ✓ UNINSTALLATION COMPLETE!                          ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${CYAN}All LogicPanel components have been removed.${NC}"
echo ""
echo -e "  ${YELLOW}To reinstall:${NC}"
echo -e "  ${CYAN}curl -sSL https://raw.githubusercontent.com/LogicDock/LogicPanel/main/install.sh | bash${NC}"
echo ""
echo -e "  For more info, visit: ${CYAN}https://logicdock.cloud${NC}"
echo ""
