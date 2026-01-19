FROM adminer:4.8.1

# Copy LogicPanel custom theme
COPY adminer.css /var/www/html/adminer.css

# Copy custom wrapper with theme toggle
COPY index.php /var/www/html/index.php

# Download adminer PHP file
RUN curl -L https://github.com/vrana/adminer/releases/download/v4.8.1/adminer-4.8.1.php -o /var/www/html/adminer-4.8.1.php

# Set permissions
RUN chown -R www-data:www-data /var/www/html
