FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libpq-dev \
    libicu-dev \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP Extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli pdo_pgsql pgsql zip mbstring intl

# Install PECL extensions (Redis)
RUN pecl install redis && docker-php-ext-enable redis

# Enable Apache Rewrite Module
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Docker CLI (Needed for managing user apps)
COPY --from=docker:latest /usr/local/bin/docker /usr/local/bin/docker

# Set working directory
WORKDIR /var/www/html

# Copy application files (respecting .dockerignore)
COPY . .

# Install dependencies via Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Configuration for Apache
ENV APACHE_DOCUMENT_ROOT="/var/www/html"
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf
RUN sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Custom PHP Config
RUN echo "upload_max_filesize = 512M\npost_max_size = 512M\nmemory_limit = 512M\nmax_execution_time = 300" > /usr/local/etc/php/conf.d/logicpanel.ini

# Fix permissions and ensure directories exist
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views storage/user-apps \
    && chown -R www-data:www-data /var/www/html \
    && find /var/www/html/storage -type d -exec chmod 775 {} + \
    && find /var/www/html/storage -type f -exec chmod 664 {} +

# Setup entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]

EXPOSE 80
