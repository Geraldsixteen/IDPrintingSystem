FROM php:8.2-apache

# Install PostgreSQL dependencies
RUN apt-get update && apt-get install -y libpq-dev unzip zip \
    && docker-php-ext-install pdo pdo_pgsql

# Copy OnlineRegistration as a folder (KEEP directory level)
COPY OnlineRegistration /var/www/html/OnlineRegistration

# Copy Config folder
COPY Config /var/www/html/Config

# Point Apache to OnlineRegistration
ENV APACHE_DOCUMENT_ROOT=/var/www/html/OnlineRegistration

RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Create persistent backup folder (Render disk mount)
RUN mkdir -p /mnt/data/UploadsBackup \
    && chown -R www-data:www-data /mnt/data \
    && chmod -R 777 /mnt/data

# Enable Apache rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html/OnlineRegistration

EXPOSE 80
