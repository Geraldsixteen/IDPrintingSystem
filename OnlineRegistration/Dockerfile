FROM php:8.2-apache

# Install dependencies for PostgreSQL + ZipArchive
RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip

# Copy your OnlineRegistration folder into container
COPY OnlineRegistration/ /var/www/html/OnlineRegistration/

# Copy Config folder
COPY Config/ /var/www/html/Config/

# Set Apache root to AdminSystem
ENV APACHE_DOCUMENT_ROOT=/var/www/html/OnlineRegistration/AdminSystem

# Update Apache configuration to use new root
RUN sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/*.conf \
 && sed -ri "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Create persistent backup folder (Render disk mount)
RUN mkdir -p /mnt/data/UploadsBackup \
    && chown -R www-data:www-data /mnt/data \
    && chmod -R 777 /mnt/data

# Enable Apache rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html/OnlineRegistration/AdminSystem

# Expose port
EXPOSE 80
