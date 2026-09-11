ARG PHP_VERSION=8.2

FROM composer:2 AS composer_stage

FROM php:${PHP_VERSION}-fpm

ARG HOST_UID=1000
ARG HOST_GID=1000

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      git \
      unzip \
      libicu-dev \
      libzip-dev \
      zip; \
    docker-php-ext-install -j"$(nproc)" intl mysqli pdo pdo_mysql zip; \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer_stage /usr/bin/composer /usr/bin/composer

RUN set -eux; \
    groupmod --gid "${HOST_GID}" www-data; \
    usermod --uid "${HOST_UID}" --gid "${HOST_GID}" www-data

RUN { \
      echo 'memory_limit=512M'; \
      echo 'max_execution_time=120'; \
      echo 'max_input_vars=2000'; \
    } > /usr/local/etc/php/conf.d/fabryq-harbor.ini

WORKDIR /var/www/html

EXPOSE 9000
