FROM php:8.2-apache

# Install PostgreSQL drivers
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo_pgsql pgsql

# Copy ALL application files to the container[cite: 4]
COPY . /var/www/html/

# Set correct permissions[cite: 4]
RUN chmod -R 755 /var/www/html/ && \
    chown -R www-data:www-data /var/www/html/

# Enable Apache rewrite for cleaner URLs
RUN a2enmod rewrite

EXPOSE 80