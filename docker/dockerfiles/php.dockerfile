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

# Set working directory with proper permissions
WORKDIR /var/www/html

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create a directory for composer cache with proper permissions
RUN mkdir -p /var/www/html/vendor && \
    mkdir -p /.composer && \
    chmod -R 777 /var/www/html && \
    chmod -R 777 /.composer

# Create the laravel user with appropriate permissions
RUN addgroup -g 1000 laravel && \
    adduser -G laravel -u 1000 -s /bin/sh -D laravel && \
    chown -R laravel:laravel /var/www/html && \
    chown -R laravel:laravel /.composer

# The following line is important for GitHub Actions to work properly
RUN usermod -aG root laravel

USER laravel

EXPOSE 9000