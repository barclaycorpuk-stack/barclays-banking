FROM php:8.2-apache

# Install PostgreSQL drivers
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chmod -R 755 /var/www/html/

EXPOSE 80