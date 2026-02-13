FROM php:8.2-apache

# Install dependencies for PostgreSQL PDO
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy your app
COPY . /var/www/html/

EXPOSE 80
