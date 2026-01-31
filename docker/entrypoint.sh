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

echo "=== SSL Certificate Setup ==="
echo "Looking for certs for domain: $DOMAIN"
echo "Certificate directory: $CERT_DIR"

# Debug: List all files in cert directory and domain subfolder
echo "Files in $CERT_DIR:"
ls -la "$CERT_DIR" 2>/dev/null || echo "(directory empty or not mounted)"
if [ -d "$CERT_DIR/$DOMAIN" ]; then
    echo "Files in $CERT_DIR/$DOMAIN:"
    ls -la "$CERT_DIR/$DOMAIN" 2>/dev/null
fi

# Wait up to 90 seconds for certs to appear (LetsEncrypt companion needs time)
CERT_FOUND=false
for i in {1..18}; do
    # Pattern 1: domain.crt and domain.key (flat structure)
    if [ -f "$CERT_DIR/$DOMAIN.crt" ] && [ -f "$CERT_DIR/$DOMAIN.key" ]; then
        echo "Found certs (flat): $DOMAIN.crt and $DOMAIN.key"
        ln -sf "$CERT_DIR/$DOMAIN.crt" /etc/apache2/ssl/server.crt
        ln -sf "$CERT_DIR/$DOMAIN.key" /etc/apache2/ssl/server.key
        CERT_FOUND=true
        break
    # Pattern 2: domain/fullchain.pem and domain/key.pem (nginx-proxy-companion standard)
    elif [ -f "$CERT_DIR/$DOMAIN/fullchain.pem" ] && [ -f "$CERT_DIR/$DOMAIN/key.pem" ]; then
        echo "Found certs (folder): $DOMAIN/fullchain.pem and key.pem"
        ln -sf "$CERT_DIR/$DOMAIN/fullchain.pem" /etc/apache2/ssl/server.crt
        ln -sf "$CERT_DIR/$DOMAIN/key.pem" /etc/apache2/ssl/server.key
        CERT_FOUND=true
        break
    # Pattern 3: domain/cert.pem and domain/key.pem
    elif [ -f "$CERT_DIR/$DOMAIN/cert.pem" ] && [ -f "$CERT_DIR/$DOMAIN/key.pem" ]; then
        echo "Found certs (folder): $DOMAIN/cert.pem and key.pem"
        ln -sf "$CERT_DIR/$DOMAIN/cert.pem" /etc/apache2/ssl/server.crt
        ln -sf "$CERT_DIR/$DOMAIN/key.pem" /etc/apache2/ssl/server.key
        CERT_FOUND=true
        break
    # Pattern 4: domain/privkey.pem (some setups use this name)
    elif [ -f "$CERT_DIR/$DOMAIN/fullchain.pem" ] && [ -f "$CERT_DIR/$DOMAIN/privkey.pem" ]; then
        echo "Found certs (folder): $DOMAIN/fullchain.pem and privkey.pem"
        ln -sf "$CERT_DIR/$DOMAIN/fullchain.pem" /etc/apache2/ssl/server.crt
        ln -sf "$CERT_DIR/$DOMAIN/privkey.pem" /etc/apache2/ssl/server.key
        CERT_FOUND=true
        break
    fi
    echo "[$i/18] Certs not found yet, waiting 5s..."
    sleep 5
done

if [ "$CERT_FOUND" = true ]; then
    a2ensite ssl-custom.conf 2>/dev/null || true
    echo "✓ SSL Enabled on ports 999 and 777 with valid certificates!"
else
    echo "⚠ No valid certificates found after 90s. Generating self-signed fallback..."
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout /etc/apache2/ssl/server.key \
        -out /etc/apache2/ssl/server.crt \
        -subj "/C=US/ST=State/L=City/O=LogicPanel/CN=$DOMAIN" 2>/dev/null
    a2ensite ssl-custom.conf 2>/dev/null || true
    echo "Self-signed certificate created for: $DOMAIN"
fi

echo "=== SSL Setup Complete ==="

# Configure WebSocket Proxy for Terminal Gateway
cat >> /etc/apache2/sites-enabled/000-default.conf << 'WSEOF'

# WebSocket Proxy for Terminal Gateway
<Location /ws/terminal>
    ProxyPass ws://logicpanel_gateway:3002
    ProxyPassReverse ws://logicpanel_gateway:3002
    ProxyPreserveHost On
</Location>
WSEOF
echo "✓ WebSocket proxy configured for /ws/terminal"

# Pass control to the main command (apache2-foreground)
exec "$@"
