FROM pecherskiy/php82-swoole as swoole
FROM pecherskiy/php82-xdebug as xdebug
FROM pecherskiy/php82-inotify as inotify

FROM php:8.2-cli-alpine3.17 as base

MAINTAINER Pecherskiy Valeriy <pecherskiy.v@yandex.ru>
LABEL org.opencontainers.image.authors="Pecherskiy Valeriy <pecherskiy.v@gmail.com>"

FROM base as run

LABEL org.opencontainers.image.authors="Pecherskiy Valeriy <pecherskiy.v@yandex.ru>"

RUN apk update && apk add --no-cache libstdc++
RUN apk update && apk add --update linux-headers

RUN docker-php-ext-install sockets
COPY --from=swoole /usr/local/ /usr/local/

MAINTAINER Pecherskiy Valeriy <pecherskiy.v@gmail.com>

#############################################################################
# xDebug
#############################################################################
FROM run as debug
COPY --from=xdebug /usr/local/ /usr/local/
COPY ./docker/dev/ext-xdebug.ini /usr/local/etc/php/conf.d/conf-xdebug.ini
