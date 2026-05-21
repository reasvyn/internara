FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git unzip curl libpng-dev libonig-dev libxml2-dev zip \
    libpq-dev libzip-dev nodejs npm \
    && docker-php-ext-install pdo_mysql pdo_pgsql bcmath gd zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader \
    && npm install && npm run build \
    && chown -R www-data:www-data storage bootstrap/cache public/storage \
    && php artisan storage:link

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
