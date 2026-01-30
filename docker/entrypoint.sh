#!/bin/bash
set -e

# Fix docker socket permissions
if [ -S /var/run/docker.sock ]; then
    # Option 1: Change group ownership to www-data (if possible)
    # chgrp www-data /var/run/docker.sock
    
    # Option 2: Allow everyone to read/write (Easiest for dev)
    chmod 666 /var/run/docker.sock
fi

# Fix user-apps volume permissions (allow www-data to write)
if [ -d /var/www/html/storage/user-apps ]; then
    chown www-data:www-data /var/www/html/storage/user-apps
    chmod 777 /var/www/html/storage/user-apps
fi

# logicpanel-ssl: Auto-link Let's Encrypt certs for Apache
mkdir -p /etc/apache2/ssl
CERT_DIR="/etc/nginx/certs"
DOMAIN="${VIRTUAL_HOST}"

if [ -f "$CERT_DIR/$DOMAIN.crt" ] && [ -f "$CERT_DIR/$DOMAIN.key" ]; then
    echo "Found certificates for $DOMAIN"
    ln -sf "$CERT_DIR/$DOMAIN.crt" /etc/apache2/ssl/server.crt
    ln -sf "$CERT_DIR/$DOMAIN.key" /etc/apache2/ssl/server.key
    # Enable the custom SSL configuration
    a2ensite ssl-custom.conf
    echo "Enabled SSL on ports 999 and 777"
else
    echo "No certificates found in $CERT_DIR for $DOMAIN or mount missing."
    # Fallback: Create self-signed IF NOT EXISTS to prevent apache crash
    if [ ! -f /etc/apache2/ssl/server.crt ]; then
        echo "Generating self-signed fallback certificate..."
        openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
            -keyout /etc/apache2/ssl/server.key \
            -out /etc/apache2/ssl/server.crt \
            -subj "/C=US/ST=State/L=City/O=LogicPanel/CN=localhost"
        a2ensite ssl-custom.conf
    fi
fi

# Pass control to the main command (apache2-foreground)
exec "$@"
