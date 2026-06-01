FROM php:8.2-apache

# Install PostgreSQL drivers and enable extensions
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo_pgsql pgsql

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chmod -R 755 /var/www/html/ && \
    chown -R www-data:www-data /var/www/html/

# Enable Apache mod_rewrite
RUN a2enmod rewrite

EXPOSE 80