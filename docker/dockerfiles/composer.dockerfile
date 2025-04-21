FROM composer:latest

RUN addgroup -g 1000 laravel && \
    adduser -G laravel -u 1000 -s /bin/sh -D laravel

USER laravel
WORKDIR /var/www/html

ENTRYPOINT [ "composer" ]
