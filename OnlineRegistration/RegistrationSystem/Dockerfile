# Use official PHP + Apache image
FROM php:8.2-apache

# Install PostgreSQL PHP extension
RUN apt-get update && apt-get install -y libpq-dev git unzip zip \
    && docker-php-ext-install pdo pdo_pgsql

# Copy files in current context (RegistrationSystem/) to /var/www/html/
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Expose port 80 (default for Apache)
EXPOSE 80
