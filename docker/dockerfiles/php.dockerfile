FROM php:8.3-fpm-alpine3.20

# Install system dependencies first
RUN apk update && apk upgrade --no-cache && \
    apk add --no-cache \
      libzip-dev \
      oniguruma-dev \
      mysql-dev \
      curl \
      git \
      zip \
      unzip \
      shadow \
    && docker-php-ext-install pdo pdo_mysql mbstring zip

# Set working directory
WORKDIR /var/www/html

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create directories with proper permissions
RUN mkdir -p /var/www/html/vendor && \
    mkdir -p /.composer && \
    chmod -R 777 /var/www/html && \
    chmod -R 777 /.composer

# Set up user
RUN addgroup -g 1000 laravel && \
    adduser -G laravel -u 1000 -s /bin/sh -D laravel && \
    chown -R laravel:laravel /var/www/html && \
    chown -R laravel:laravel /.composer

# Pre-create the .env file to avoid permission issues
RUN touch /var/www/html/.env && \
    chmod 777 /var/www/html/.env && \
    chown laravel:laravel /var/www/html/.env

USER laravel

EXPOSE 9000