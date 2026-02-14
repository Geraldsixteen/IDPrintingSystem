FROM php:8.2-apache

# ------------------ Install system dependencies ------------------
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip

# ------------------ Copy OnlineRegistration ------------------
COPY . /var/www/html/OnlineRegistration/

# ------------------ Apache root ------------------
ENV APACHE_DOCUMENT_ROOT=/var/www/html/OnlineRegistration/RegistrationSystem

RUN sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/*.conf \
 && sed -ri "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# ------------------ Backup folder ------------------
RUN mkdir -p /mnt/data/UploadsBackup \
    && chown -R www-data:www-data /mnt/data \
    && chmod -R 777 /mnt/data

# ------------------ Enable Apache rewrite ------------------
RUN a2enmod rewrite

# ------------------ Working directory ------------------
WORKDIR /var/www/html/OnlineRegistration/RegistrationSystem

# ------------------ Expose port ------------------
EXPOSE 80
