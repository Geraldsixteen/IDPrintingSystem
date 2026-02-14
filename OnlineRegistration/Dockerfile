FROM php:8.2-apache

# Install PostgreSQL, zip dependencies, and utilities
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    apt-utils \
    && docker-php-ext-configure zip \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy current folder (OnlineRegistration) into Apache root
COPY . /var/www/html/

# Persistent backup folder (Render disk)
RUN mkdir -p /mnt/data/UploadsBackup \
    && chmod -R 777 /mnt/data

# Enable Apache rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

EXPOSE 80
