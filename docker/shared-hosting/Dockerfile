FROM php:8.4-apache

# === Shared hosting simulation ===
# This image mimics a basic shared hosting environment:
# - Apache + PHP-FPM (no Nginx)
# - No Composer (build externally)
# - No Node.js / npm
# - No Redis / Memcached
# - No pcntl / posix extensions
# - File-based cache and sessions
# - SQLite as default database

# System dependencies — mimics shared hosting with MySQL
RUN apt-get update && apt-get install -y \
    libsqlite3-dev libmariadb-dev-compat \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite

# Set document root to public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# Application code (expected to be mounted or copied in)
COPY . .

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Shared hosting env preset
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV QUEUE_CONNECTION=sync
ENV CACHE_STORE=file
ENV SESSION_DRIVER=file
ENV BROADCAST_CONNECTION=log
ENV DB_CONNECTION=sqlite

EXPOSE 80

CMD ["apache2-foreground"]
