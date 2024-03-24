FROM pecherskiy/php82-swoole as swoole

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

COPY ./config /var/task/config
COPY ./src /var/task/src
COPY ./vendor /var/task/vendor
COPY ./composer.json /var/task/composer.json

RUN chmod ugo+x /var/task/src/run.php

VOLUME [ "/var/task" ]

CMD ["php", "/var/task/src/run.php"]