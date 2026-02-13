FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    zip \
    && docker-php-ext-install pdo pdo_pgsql

# Copy your app
COPY RegistrationSystem/ /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Expose port (Render uses 10000 by default)
EXPOSE 10000
