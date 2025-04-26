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
      # Add sudo for permission management
      sudo \
    && docker-php-ext-install pdo pdo_mysql mbstring zip

# Set working directory
WORKDIR /var/www/html

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create directories with proper permissions - before user creation
RUN mkdir -p /var/www/html/vendor && \
    mkdir -p /.composer && \
    chmod -R 777 /var/www/html && \
    chmod -R 777 /.composer

# Set up user with sudo privileges
RUN addgroup -g 1000 laravel && \
    adduser -G laravel -u 1000 -s /bin/sh -D laravel && \
    echo "laravel ALL=(ALL) NOPASSWD: ALL" > /etc/sudoers.d/laravel && \
    chmod 0440 /etc/sudoers.d/laravel && \
    chown -R laravel:laravel /var/www/html && \
    chown -R laravel:laravel /.composer

# Pre-create the .env file to avoid permission issues
RUN touch /var/www/html/.env && \
    chmod 777 /var/www/html/.env && \
    chown laravel:laravel /var/www/html/.env

# Ensure the entrypoint script can fix permissions during runtime
COPY ./docker/scripts/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Switch to laravel user for most operations
USER laravel

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]