# Use official PHP 8.4 FPM Alpine image
FROM php:8.4-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    git \
    unzip \
    libpq-dev \
    bash \
    shadow \
    && docker-php-ext-install pdo pdo_pgsql

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Optionally: copy composer files first to cache dependencies
# COPY composer.json composer.lock ./
# RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copy full project
# COPY . .

# Optional: set permissions for Laravel directories (storage and bootstrap/cache)
# RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
#     && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Expose PHP-FPM port
EXPOSE 9000

# Start PHP-FPM in foreground
CMD ["php-fpm", "-F"]
