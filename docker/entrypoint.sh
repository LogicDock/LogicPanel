#!/bin/sh
set -e

echo "LogicPanel - Starting initialization..."

echo "Waiting for database..."
MAX_TRIES=30
TRIES=0
while ! nc -z ${DB_HOST:-logicpanel-db} 3306 2>/dev/null; do
    TRIES=$((TRIES+1))
    if [ $TRIES -ge $MAX_TRIES ]; then
        echo "Database not available after $MAX_TRIES attempts"
        break
    fi
    echo "Waiting for database... ($TRIES/$MAX_TRIES)"
    sleep 2
done
sleep 3

echo "Creating .env file..."
cat > /var/www/html/.env << EOF
APP_ENV="${APP_ENV:-production}"
APP_DEBUG="${APP_DEBUG:-false}"
APP_URL="${APP_URL:-http://localhost}"
APP_SECRET="${APP_SECRET:-defaultsecret}"

DB_HOST="${DB_HOST:-logicpanel-db}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-logicpanel}"
DB_USERNAME="${DB_USERNAME:-logicpanel}"
DB_PASSWORD="${DB_PASSWORD:-password}"
DB_PREFIX="${DB_PREFIX:-lp_}"

ADMIN_USERNAME="${ADMIN_USERNAME:-admin}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@localhost}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-password}"

API_KEY="${API_KEY:-lp_default}"
API_SECRET="${API_SECRET:-default}"
EOF
chmod 644 /var/www/html/.env

echo ".env created - ADMIN_USERNAME: ${ADMIN_USERNAME:-admin}"

echo "Setting up database..."
if php /var/www/html/scripts/setup-db.php; then
    echo "Database setup complete!"
else
    echo "Database setup failed or already done"
fi

echo "Checking admin user..."
php /var/www/html/scripts/create-admin.php

chown -R nobody:nobody /var/www/html/storage 2>/dev/null || true

echo "LogicPanel initialization complete!"
echo "Starting services..."

exec /usr/bin/supervisord -c /etc/supervisord.conf
