FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpq-dev unzip zip \
    && docker-php-ext-install pdo pdo_pgsql

# Copy folders from OnlineRegistration build context
COPY RegistrationSystem/ /var/www/html/
COPY Config/ /var/www/html/Config/
COPY public/ /var/www/html/public/

WORKDIR /var/www/html

EXPOSE 80
