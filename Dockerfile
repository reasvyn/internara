# syntax=docker/dockerfile:1
FROM php:8.4-fpm AS builder

RUN apt-get update && apt-get install -y \
    git unzip curl libpng-dev libonig-dev libxml2-dev zip \
    libpq-dev libzip-dev libicu-dev nodejs npm $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql pdo_pgsql bcmath gd zip intl exif pcntl \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-scripts --no-autoloader

COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci --legacy-peer-deps

COPY . .
RUN composer dump-autoload --no-dev --optimize \
    && npm run build \
    && php artisan storage:link \
    && chown -R www-data:www-data storage bootstrap/cache public/storage \
    && rm -rf node_modules

FROM php:8.4-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev libonig-dev libxml2-dev libpq-dev libzip-dev libicu-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql bcmath gd zip intl exif pcntl \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=builder /app /app
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY docker/fpm-healthcheck /usr/local/bin/fpm-healthcheck
COPY docker/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf
RUN chmod +x /usr/local/bin/docker-entrypoint.sh /usr/local/bin/fpm-healthcheck \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/public/storage \
    && cp -a /app /opt/app-src

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
