FROM composer:latest

WORKDIR /var/www/html

# Don't run as root
ENV COMPOSER_ALLOW_SUPERUSER=0
ENV COMPOSER_HOME=/.composer

RUN mkdir -p /.composer && chmod -R 777 /.composer

# Add user with same UID/GID as host system
RUN addgroup -g 1000 laravel && \
    adduser -G laravel -u 1000 -s /bin/sh -D laravel

USER laravel

ENTRYPOINT [ "composer" ]