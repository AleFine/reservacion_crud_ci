FROM php:8.3-fpm-alpine3.20

RUN apk update && apk upgrade --no-cache

RUN addgroup -g 1000 laravel && \
    adduser -G laravel -u 1000 -s /bin/sh -D laravel

WORKDIR /var/www/html

RUN apk add --no-cache \
      libzip-dev \
      oniguruma-dev \
      mysql-dev \
      curl \
      git \
    && docker-php-ext-install pdo pdo_mysql mbstring zip

USER laravel

EXPOSE 9000
