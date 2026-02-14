FROM php:8.2-apache

# Install PostgreSQL + zip (ZipArchive needed for download.php)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip

# Copy CURRENT folder (OnlineRegistration) into Apache root
COPY . /var/www/html/

# Persistent backup folder (Render disk)
RUN mkdir -p /mnt/data/UploadsBackup \
    && chmod -R 777 /mnt/data

# Enable rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

EXPOSE 80
