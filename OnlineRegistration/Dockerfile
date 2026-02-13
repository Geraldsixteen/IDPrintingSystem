FROM php:8.2-apache

# Install dependencies for PostgreSQL and PHP PDO
RUN apt-get update && apt-get install -y libpq-dev unzip zip \
    && docker-php-ext-install pdo pdo_pgsql

# Copy the entire OnlineRegistration folder content into /var/www/html
COPY . /var/www/html/

# Ensure Public/Uploads folder exists and is writable by PHP
RUN mkdir -p /var/www/html/Public/Uploads \
    && chown -R www-data:www-data /var/www/html/Public \
    && chmod -R 777 /var/www/html/Public/Uploads

# Optional: create backup folder inside container
RUN mkdir -p /var/backup/UploadsBackup \
    && chmod -R 777 /var/backup/UploadsBackup

# Set working directory
WORKDIR /var/www/html

# Expose port 80
EXPOSE 80
