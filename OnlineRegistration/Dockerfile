FROM php:8.2-apache

# Copy all files from OnlineRegistration into Apache root
COPY . /var/www/html/

# Enable PostgreSQL PDO
RUN docker-php-ext-install pdo pdo_pgsql

# Expose the port Render uses
EXPOSE 10000
