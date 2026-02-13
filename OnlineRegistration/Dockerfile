FROM php:8.2-apache

# Install dependencies for PostgreSQL and PHP PDO
RUN apt-get update && apt-get install -y libpq-dev unzip zip \
    && docker-php-ext-install pdo pdo_pgsql

# Copy your app into container
COPY RegistrationSystem/ /var/www/html/
COPY Config/ /var/www/html/Config/
COPY Public/ /var/www/html/Public/

# Ensure Uploads folder exists and is writable
RUN mkdir -p /var/www/html/Public/Uploads \
    && mkdir -p /var/backup/UploadsBackup \
    && chown -R www-data:www-data /var/www/html/Public \
    && chown -R www-data:www-data /var/backup/UploadsBackup \
    && chmod -R 777 /var/www/html/Public/Uploads \
    && chmod -R 777 /var/backup/UploadsBackup

# Enable Apache mod_rewrite (optional, if you use clean URLs)
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Expose port
EXPOSE 80

# Start Apache in foreground (default CMD already does this)
