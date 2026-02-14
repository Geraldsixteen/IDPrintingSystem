FROM php:8.2-apache

# Install PostgreSQL + zip (ZipArchive needed for download.php)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip

# Copy OnlineRegistration folder (keep directory)
COPY ./OnlineRegistration/ /var/www/html/OnlineRegistration/

# Copy Config
COPY ./Config/ /var/www/html/Config/

# Point Apache to OnlineRegistration
ENV APACHE_DOCUMENT_ROOT=/var/www/html/OnlineRegistration

RUN sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
 && sed -ri "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Persistent backup folder (Render disk)
RUN mkdir -p /mnt/data/UploadsBackup \
    && chown -R www-data:www-data /mnt/data \
    && chmod -R 777 /mnt/data

# Enable rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html/OnlineRegistration

EXPOSE 80
