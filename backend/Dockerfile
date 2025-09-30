FROM php:8.2-apache

# Installer extensions PHP nécessaires à Symfony
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libpq-dev libzip-dev zip \
    && docker-php-ext-install intl pdo pdo_mysql opcache

# Activer mod_rewrite pour Symfony
RUN a2enmod rewrite

# Copier la config Apache
COPY ./docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
