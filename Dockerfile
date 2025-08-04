# food-delivery/Dockerfile
FROM php:8.1-fpm

# Install OS dependencies# system libs …
RUN apt-get update && apt-get install -y \
    libpq-dev zip unzip && \
    docker-php-ext-install pdo pdo_pgsql opcache

# ---------- FPM pool override ----------
COPY php-fpm-pool.conf /usr/local/etc/php-fpm.d/zz-custom.conf
# ---------------------------------------

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html