# syntax=docker/dockerfile:1
FROM php:8.5-fpm-alpine AS base
RUN apk add --no-cache icu-libs libzip oniguruma sqlite-libs \
 && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev oniguruma-dev sqlite-dev \
 && docker-php-ext-install -j$(nproc) pdo_mysql pdo_sqlite intl zip bcmath mbstring pcntl \
 && apk del .build-deps
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY docker/php.ini /usr/local/etc/php/conf.d/ipl.ini

FROM base AS app
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
 && mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod +x docker/entrypoint.sh
ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
CMD ["php-fpm"]

FROM app AS test
COPY .env.example .env
RUN composer install --prefer-dist --no-interaction
ENTRYPOINT []
CMD ["php", "artisan", "test"]

FROM nginx:1.28-alpine AS web
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
