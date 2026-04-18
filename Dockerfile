FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql mbstring curl

RUN a2enmod rewrite

COPY . /var/www/html/

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80
ENV PORT=80
