FROM php:8.2-apache

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip

# Copy OnlineRegistration folder
COPY OnlineRegistration/ /var/www/html/OnlineRegistration/

# Set Apache root to RegistrationSystem
ENV APACHE_DOCUMENT_ROOT=/var/www/html/OnlineRegistration/RegistrationSystem

RUN sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/*.conf \
 && sed -ri "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Backup folder for uploaded photos
RUN mkdir -p /mnt/data/UploadsBackup \
    && chown -R www-data:www-data /mnt/data \
    && chmod -R 777 /mnt/data

# Enable Apache rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html/OnlineRegistration/RegistrationSystem
EXPOSE 80