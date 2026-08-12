FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git unzip curl libpng-dev libonig-dev libxml2-dev zip \
    libpq-dev libzip-dev libicu-dev nodejs npm $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql pdo_pgsql bcmath gd zip intl exif pcntl \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader \
    && npm ci --legacy-peer-deps && npm run build \
    && php artisan storage:link \
    && chown -R www-data:www-data storage bootstrap/cache public/storage \
    && cp -a . /opt/app-src

COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY docker/fpm-healthcheck /usr/local/bin/fpm-healthcheck
RUN chmod +x /usr/local/bin/docker-entrypoint.sh /usr/local/bin/fpm-healthcheck

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
