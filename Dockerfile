# food-delivery/Dockerfile
FROM php:8.1-fpm

# Install OS dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html