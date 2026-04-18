FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    php8.1-mysql \
    php8.1-mbstring \
    php8.1-xml \
    php8.1-curl \
    libapache2-mod-php8.1 \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Wipe default Apache content
RUN rm -rf /var/www/html/*

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Enable AllowOverride and set DirectoryIndex directly in Apache config
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf && \
    sed -i 's|DirectoryIndex index.html|DirectoryIndex index.php index.html|g' /etc/apache2/mods-enabled/dir.conf

CMD sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf && \
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf && \
    apache2ctl -D FOREGROUND
