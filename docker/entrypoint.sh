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

# Pass control to the main command (apache2-foreground)
exec "$@"
