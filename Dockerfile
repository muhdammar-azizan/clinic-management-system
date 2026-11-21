# syntax=docker/dockerfile:1

# Stage 1: build the front end (Vite and Tailwind).
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# Stage 2: install the PHP packages without the development tools.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --prefer-dist

# Stage 3: the image that runs. Apache with several PHP worker processes and OPcache.
FROM php:8.2-apache AS app

COPY --from=ghcr.io/mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN install-php-extensions pdo_pgsql pdo_mysql opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Serve the public folder, and listen on the port the platform gives us ($PORT).
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    PORT=8080
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -ri -e 's/Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -ri -e 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/apache.conf /etc/apache2/conf-available/zz-app.conf
RUN a2enconf zz-app

WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-scripts \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/private bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

EXPOSE 8080

# Uses PHP itself so the image needs no extra tools. /up only proves the framework starts.
HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD php -r "exit(@file_get_contents('http://127.0.0.1:'.(getenv('PORT') ?: 8080).'/up') === false ? 1 : 0);"

ENTRYPOINT ["app-entrypoint"]
CMD ["apache2-foreground"]
