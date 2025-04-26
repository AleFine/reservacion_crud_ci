FROM composer:latest

WORKDIR /var/www/html

# Environment variables for composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/.composer

# Create composer home directory with proper permissions
RUN mkdir -p /.composer && chmod -R 777 /.composer

# Add user with same UID/GID as host system
RUN addgroup -g 1000 laravel && \
    adduser -G laravel -u 1000 -s /bin/sh -D laravel && \
    chown -R laravel:laravel /.composer

USER laravel

ENTRYPOINT [ "composer" ]