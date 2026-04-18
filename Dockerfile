FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql mbstring

COPY . /var/www/html/

EXPOSE 80
