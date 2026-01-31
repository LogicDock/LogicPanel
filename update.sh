#!/usr/bin/env bash

# LogicPanel - Update Script v2.0
# Author: LogicDock
# Description: Updates LogicPanel to the latest version while preserving all data.

set -e

INSTALL_DIR="/opt/logicpanel"

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

# --- 1. Root Check ---
if [[ $EUID -ne 0 ]]; then
   log_error "This script must be run as root."
   exit 1
fi

clear
echo -e "${CYAN}"
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║               LOGICPANEL UPDATER v2.0                         ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# --- 2. Check if LogicPanel is installed ---
if [ ! -d "$INSTALL_DIR" ]; then
    log_error "LogicPanel is not installed at $INSTALL_DIR"
    log_info "Please run the install script first:"
    echo -e "  ${CYAN}curl -sSL https://raw.githubusercontent.com/LogicDock/LogicPanel/main/install.sh | bash${NC}"
    exit 1
fi

cd $INSTALL_DIR

# --- 3. Get Current Version ---
CURRENT_VERSION="unknown"
if [ -f "VERSION" ]; then
    CURRENT_VERSION=$(cat VERSION)
fi
log_info "Current version: $CURRENT_VERSION"

# --- 4. Fetch Latest Version Info ---
log_info "Checking for updates..."
LATEST_VERSION=$(curl -sSL https://raw.githubusercontent.com/LogicDock/LogicPanel/main/VERSION 2>/dev/null || echo "unknown")
log_info "Latest version: $LATEST_VERSION"

if [ "$CURRENT_VERSION" = "$LATEST_VERSION" ]; then
    log_success "You are already running the latest version!"
    exit 0
fi

# --- 5. Backup Current Config ---
log_info "Backing up configuration..."
BACKUP_DIR="/opt/logicpanel_backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Backup important files
cp -r config "$BACKUP_DIR/" 2>/dev/null || true
cp -r storage "$BACKUP_DIR/" 2>/dev/null || true
cp docker-compose.yml "$BACKUP_DIR/" 2>/dev/null || true
cp .env "$BACKUP_DIR/" 2>/dev/null || true

log_success "Backup created at: $BACKUP_DIR"

# --- 6. Download Latest Source ---
log_info "Downloading latest version..."

# Create temp directory for new source
TEMP_DIR=$(mktemp -d)
curl -sSL https://github.com/LogicDock/LogicPanel/archive/refs/heads/main.tar.gz | tar xz -C "$TEMP_DIR" --strip-components=1

# --- 7. Update Application Files (preserve data) ---
log_info "Updating application files..."

# Files/directories to preserve (not overwrite)
PRESERVE=(
    "storage"
    "config/settings.json"
    "mysql_data_main"
    "mysql_data_mother"
    "postgres_data"
    "mongo_data"
    "docker-compose.yml"
    ".env"
)

# Update source code directories
UPDATE_DIRS=(
    "src"
    "templates"
    "public"
    "services"
    "docker"
    "database"
)

for dir in "${UPDATE_DIRS[@]}"; do
    if [ -d "$TEMP_DIR/$dir" ]; then
        rm -rf "$INSTALL_DIR/$dir"
        cp -r "$TEMP_DIR/$dir" "$INSTALL_DIR/"
        log_success "Updated: $dir"
    fi
done

# Update root files (except preserved ones)
UPDATE_FILES=(
    "composer.json"
    "composer.lock"
    "create_admin.php"
    "VERSION"
)

for file in "${UPDATE_FILES[@]}"; do
    if [ -f "$TEMP_DIR/$file" ]; then
        cp "$TEMP_DIR/$file" "$INSTALL_DIR/"
    fi
done

# Cleanup
rm -rf "$TEMP_DIR"

# --- 8. Rebuild and Restart Containers ---
log_info "Rebuilding application container..."
docker compose build --no-cache app > /tmp/logicpanel_update_build.log 2>&1 &
BUILD_PID=$!

# Simple progress indicator
while ps -p $BUILD_PID > /dev/null 2>&1; do
    echo -n "."
    sleep 2
done
echo ""

# Check if build succeeded
wait $BUILD_PID
if [ $? -ne 0 ]; then
    log_error "Build failed! Check /tmp/logicpanel_update_build.log"
    log_warn "Restoring from backup..."
    cp -r "$BACKUP_DIR/"* "$INSTALL_DIR/"
    exit 1
fi

log_success "Build completed!"

# --- 9. Restart Services ---
log_info "Restarting services..."
docker compose up -d

# --- 10. Run Any Migrations (if exist) ---
if [ -f "database/migrations/run.php" ]; then
    log_info "Running database migrations..."
    docker exec logicpanel_app php /var/www/html/database/migrations/run.php 2>/dev/null || true
fi

# --- 11. Cleanup Old Backups (keep last 5) ---
log_info "Cleaning old backups..."
ls -dt /opt/logicpanel_backup_* 2>/dev/null | tail -n +6 | xargs rm -rf 2>/dev/null || true

# --- Success ---
echo ""
echo -e "${GREEN}╔═══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              ✓ UPDATE SUCCESSFUL!                             ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${CYAN}Previous version:${NC} $CURRENT_VERSION"
echo -e "  ${CYAN}New version:${NC}      $LATEST_VERSION"
echo ""
echo -e "  ${YELLOW}Backup location:${NC} $BACKUP_DIR"
echo ""
echo -e "  ${GREEN}All your data has been preserved!${NC}"
echo ""
echo -e "  For more info, visit: ${CYAN}https://logicdock.cloud${NC}"
echo ""
