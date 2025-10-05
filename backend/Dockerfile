FROM php:8.2-apache

# 1. Installer dépendances système & PHP
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libpq-dev libzip-dev zip \
    && docker-php-ext-install intl pdo pdo_mysql zip opcache

# 2. Activer mod_rewrite pour Symfony
RUN a2enmod rewrite

# 3. Copier la config Apache
COPY ./docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# 4. Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 5. Configurer les variables d’environnement
ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# ✅ 6. Copier tout le projet AVANT d’installer Composer
COPY . .

# 7. Installer les dépendances PHP (sans scripts Symfony)
RUN composer install --no-interaction --no-scripts --prefer-dist --optimize-autoloader

# 8. Configurer OPCache
RUN docker-php-ext-enable opcache \
 && echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini

EXPOSE 80
