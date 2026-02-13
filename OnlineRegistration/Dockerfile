FROM php:8.2-apache

# Install PostgreSQL PDO support
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copy Admin system into web root
COPY LocalAdminSystem/ /var/www/html/

# Ensure Apache serves index.php
RUN echo "DirectoryIndex index.php" >> /etc/apache2/apache2.conf

# Optional: enable rewrite if you have routing
RUN a2enmod rewrite

WORKDIR /var/www/html/

EXPOSE 10000
