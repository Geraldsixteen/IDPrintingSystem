FROM php:8.2-apache

# Copy OnlineRegistration into Apache root
COPY OnlineRegistration/ /var/www/html/

EXPOSE 10000
