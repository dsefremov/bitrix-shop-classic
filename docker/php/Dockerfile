# Bitrix Shop: PHP-FPM + расширения, нужные ядру 1С-Битрикс (mysqli, gd, zip, intl, opcache), и Composer 2.
ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip curl libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev default-mysql-client \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" mysqli pdo_mysql intl zip gd opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY www.conf /usr/local/etc/php-fpm.d/zz-app.conf

ARG UID=1000
ARG GID=1000
RUN groupmod -o -g "${GID}" www-data && usermod -o -u "${UID}" www-data \
    && git config --system --add safe.directory /var/www/app

WORKDIR /var/www/app
