#!/bin/sh
# LogicPanel Bootstrap - Downloads and runs the installer with proper line endings
# Usage: curl -sL https://raw.githubusercontent.com/LogicDock/LogicPanel/main/bootstrap.sh | sudo sh

set -e

SCRIPT_URL="https://raw.githubusercontent.com/LogicDock/LogicPanel/main/install.sh"
TMP_FILE="/tmp/logicpanel-install.sh"

echo "Downloading LogicPanel installer..."
curl -sL "$SCRIPT_URL" -o "$TMP_FILE"

# Fix line endings (remove Windows carriage returns)
sed -i 's/\r$//' "$TMP_FILE" 2>/dev/null || sed -i '' 's/\r$//' "$TMP_FILE" 2>/dev/null || true

# Make executable and run
chmod +x "$TMP_FILE"
exec bash "$TMP_FILE"
