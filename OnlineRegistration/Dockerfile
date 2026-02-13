FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpq-dev unzip zip \
    && docker-php-ext-install pdo pdo_pgsql

COPY RegistrationSystem/ /var/www/html/
COPY Config/ /var/www/html/Config/
COPY Public/ /var/www/html/Public/

RUN mkdir -p /var/www/html/Public/Uploads \
    && chown -R www-data:www-data /var/www/html/Public \
    && chmod -R 755 /var/www/html/Public

WORKDIR /var/www/html
EXPOSE 80
