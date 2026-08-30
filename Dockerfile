# syntax=docker/dockerfile:1
FROM php:8.4-fpm-alpine AS builder

RUN apk add --no-cache \
    git unzip curl libpng-dev oniguruma-dev libxml2-dev libzip-dev icu-dev \
    postgresql-dev nodejs npm autoconf g++ make linux-headers \
    && docker-php-ext-install pdo_mysql pdo_pgsql bcmath gd zip intl exif pcntl \
    && pecl install redis && docker-php-ext-enable redis \
    && rm -rf /var/cache/apk/* /tmp/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-scripts --no-autoloader --optimize-autoloader --classmap-authoritative

COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci --legacy-peer-deps

COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && npm run build \
    && php artisan storage:link \
    && chown -R www-data:www-data storage bootstrap/cache public/storage \
    && rm -rf node_modules .npm

FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    libpng oniguruma libxml2 libzip icu libpq \
    && docker-php-ext-install pdo_mysql pdo_pgsql bcmath gd zip intl exif pcntl \
    && pecl install redis && docker-php-ext-enable redis \
    && rm -rf /var/cache/apk/* /tmp/*

COPY --from=builder /app/vendor /app/vendor
COPY --from=builder /app/public/build /app/public/build
COPY --from=builder /app/bootstrap/cache /app/bootstrap/cache
COPY --from=builder /app/storage /app/storage
COPY --from=builder /app/app /app/app
COPY --from=builder /app/config /app/config
COPY --from=builder /app/database /app/database
COPY --from=builder /app/lang /app/lang
COPY --from=builder /app/resources /app/resources
COPY --from=builder /app/routes /app/routes
COPY --from=builder /app/composer.json /app/composer.json
COPY --from=builder /app/composer.lock /app/composer.lock
COPY --from=builder /app/package.json /app/package.json
COPY --from=builder /app/artisan /app/artisan
COPY --from=builder /app/public/storage /app/public/storage
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY docker/fpm-healthcheck /usr/local/bin/fpm-healthcheck
COPY docker/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf
RUN chmod +x /usr/local/bin/docker-entrypoint.sh /usr/local/bin/fpm-healthcheck \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/public/storage \
    && cp -a /app /opt/app-src

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
