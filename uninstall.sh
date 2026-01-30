#!/usr/bin/env bash

# LogicPanel - Uninstaller
# Author: LogicDock
# Description: Completely removes LogicPanel and its data.

set -e

INSTALL_DIR="/opt/logicpanel"
NGINX_PROXY_DIR="/opt/nginx-proxy"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Helpers
log_info() { echo -e "\033[0;34m[INFO]\033[0m $1"; }
log_success() { echo -e "${GREEN}[OK]\033[0m $1"; }
log_warn() { echo -e "${YELLOW}[WARN]\033[0m $1"; }

# --- 1. Root Check ---
if [[ $EUID -ne 0 ]]; then
   echo "This script must be run as root."
   exit 1
fi

clear
echo -e "${RED}"
echo "-----------------------------------------------------------"
echo "  LOGICPANEL UNINSTALLER   "
echo "-----------------------------------------------------------"
echo -e "${NC}"

# Redirect stdin from tty to allow interactive input when piped
exec 3<&0
exec < /dev/tty

echo -e "${RED}!!! WARNING: THIS WILL PERMANENTLY DELETE ALL DATA !!!${NC}"
read -p "Are you sure you want to uninstall LogicPanel? (y/N): " CONFIRM
if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
    echo "Uninstall cancelled."
    exec <&3 # Restore for cleanup
    exit 0
fi

# --- 2. Remove Panel ---
if [ -d "$INSTALL_DIR" ]; then
    log_info "Stopping and removing LogicPanel infrastructure..."
    (cd "$INSTALL_DIR" && docker compose down -v 2>/dev/null || true)
    rm -rf "$INSTALL_DIR"
    log_success "LogicPanel files and volumes removed."
else
    log_warn "LogicPanel directory not found at $INSTALL_DIR."
fi

# --- 3. Optional Proxy Removal ---
read -p "Do you also want to remove the Nginx Proxy setup? (y/N): " PROXY_CONFIRM

# Restore original stdin
exec <&3
exec 3<&-
if [[ "$PROXY_CONFIRM" =~ ^[Yy]$ ]]; then
    if [ -d "$NGINX_PROXY_DIR" ]; then
        log_info "Stopping Nginx Reverse Proxy..."
        (cd "$NGINX_PROXY_DIR" && docker compose down -v 2>/dev/null || true)
        rm -rf "$NGINX_PROXY_DIR"
        log_success "Reverse proxy removed."
    fi
    docker network rm nginx-proxy_web 2>/dev/null || true
fi

echo -e "\n-----------------------------------------------------------"
echo -e "  ${GREEN}Uninstallation Complete!${NC}"
echo -e "-----------------------------------------------------------"
echo -e "  For more info, visit: https://logicdock.cloud"
echo -e "-----------------------------------------------------------"
