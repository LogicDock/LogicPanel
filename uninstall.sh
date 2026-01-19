#!/bin/bash
#
# LogicPanel - Uninstaller
# Complete cleanup script
#

set -e

INSTALL_DIR="/opt/logicpanel"

echo "============================================"
echo "  LogicPanel - Uninstaller"
echo "============================================"

# Confirm uninstall
read -p "This will PERMANENTLY DELETE all LogicPanel data. Continue? [y/N]: " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Uninstall cancelled."
    exit 0
fi

echo ""
echo "Stopping and removing containers..."

# Stop and remove LogicPanel containers
cd $INSTALL_DIR 2>/dev/null || true
docker compose down -v 2>/dev/null || true

# Stop and remove shared database containers
cd $INSTALL_DIR/shared-db 2>/dev/null || true
docker compose down -v 2>/dev/null || true

# Remove all LogicPanel containers
docker ps -a | grep logicpanel | awk '{print $1}' | xargs -r docker stop 2>/dev/null || true
docker ps -a | grep logicpanel | awk '{print $1}' | xargs -r docker rm 2>/dev/null || true

echo "Removing volumes..."

# Remove all LogicPanel volumes
docker volume ls | grep logicpanel | awk '{print $2}' | xargs -r docker volume rm 2>/dev/null || true
docker volume ls | grep shared-db | awk '{print $2}' | xargs -r docker volume rm 2>/dev/null || true

echo "Removing installation directory..."

# Remove installation directory
rm -rf $INSTALL_DIR

echo "Removing network..."

# Remove internal network if exists
docker network rm logicpanel_internal 2>/dev/null || true
docker network rm shared-db_internal 2>/dev/null || true

echo ""
echo "============================================"
echo "  Uninstall Complete"
echo "============================================"
echo ""
echo "LogicPanel has been completely removed from this server."
echo ""
