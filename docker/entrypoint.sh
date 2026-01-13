#!/bin/sh
set -e

echo "LogicPanel - Starting initialization..."

# Wait for MySQL to be ready
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

# Create .env file from environment variables (including ADMIN variables)
echo "Creating .env file..."
cat > /var/www/html/.env << EOF
APP_ENV=${APP_ENV:-production}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}
APP_SECRET=${APP_SECRET:-$(openssl rand -hex 32)}

DB_HOST=${DB_HOST:-logicpanel-db}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-logicpanel}
DB_USERNAME=${DB_USERNAME:-logicpanel}
DB_PASSWORD=${DB_PASSWORD:-password}
DB_PREFIX=${DB_PREFIX:-lp_}

ADMIN_EMAIL=${ADMIN_EMAIL:-admin@localhost}
ADMIN_NAME=${ADMIN_NAME:-Administrator}
ADMIN_PASSWORD=${ADMIN_PASSWORD:-password}

API_KEY=${API_KEY:-lp_default}
API_SECRET=${API_SECRET:-default}
EOF
chmod 644 /var/www/html/.env

echo ".env file created with:"
echo "  ADMIN_EMAIL: ${ADMIN_EMAIL:-admin@localhost}"
echo "  ADMIN_NAME: ${ADMIN_NAME:-Administrator}"

# Run database migrations/schema
echo "Setting up database..."
if php /var/www/html/scripts/setup-db.php; then
    echo "Database setup complete!"
else
    echo "Database setup failed or already done"
fi

# Create admin user if not exists
echo "Checking admin user..."
php /var/www/html/scripts/create-admin.php

# Set proper permissions
chown -R nobody:nobody /var/www/html/storage 2>/dev/null || true

echo "LogicPanel initialization complete!"
echo "Starting services..."

# Start supervisord (nginx + php-fpm)
exec /usr/bin/supervisord -c /etc/supervisord.conf
