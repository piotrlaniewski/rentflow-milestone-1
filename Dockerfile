FROM php:8.4-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git libpq-dev unzip \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json ./
RUN composer install --no-interaction --no-scripts --prefer-dist

COPY . .
RUN composer dump-autoload --classmap-authoritative \
    && mkdir -p var/cache var/log \
    && chown -R www-data:www-data var

USER www-data

CMD ["php-fpm"]
