# Dockerfile for LogicPanel
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    git \
    zip \
    unzip \
    docker-cli \
    mysql-client \
    nodejs \
    npm \
    netcat-openbsd \
    openssl

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Setup working directory
WORKDIR /var/www/html

# Copy application
COPY logicpanel/ .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Create storage directory
RUN mkdir -p storage/logs storage/cache storage/sessions \
    && chown -R nobody:nobody storage

# Copy nginx config
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Copy supervisor config
COPY docker/supervisord.conf /etc/supervisord.conf

# Copy entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expose port
EXPOSE 80

# Use entrypoint for auto-setup
ENTRYPOINT ["/entrypoint.sh"]
