ARG PHP_VERSION

FROM php:${PHP_VERSION}-cli

ARG POSTGRESQL_VERSION=""
ARG SWOOLE_VERSION

COPY script/ /tmp/script

RUN set -eux \
    && apt-get update && apt-get -y install procps libpq-dev unzip git libzip-dev libevent-dev libssl-dev libicu-dev libc-ares-dev libcurl4-openssl-dev unixodbc-dev libsqlite3-dev libbrotli-dev \
    && docker-php-ext-install -j$(nproc) bcmath mysqli pdo_mysql pdo_pgsql pcntl sockets intl zip \
    && (php --ri redis || (pecl install redis && docker-php-ext-enable redis)) \
    && pecl install inotify \
    && docker-php-ext-enable inotify \
    && pecl install event \
    && docker-php-ext-enable --ini-name z-event.ini event \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && curl -sfL https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer && chmod +x /usr/bin/composer \
    && curl -L -o swoole.tar.gz https://github.com/swoole/swoole-src/archive/${SWOOLE_VERSION}.tar.gz && mkdir -p swoole && tar -xzf swoole.tar.gz -C swoole --strip-components=1 && rm swoole.tar.gz && cd swoole && ((stat ./make.sh && ./make.sh) || (stat ./scripts/make.sh && ./scripts/make.sh)) && cd - && docker-php-ext-enable swoole \
    && bash /tmp/script/swoole_postgresql.sh ${POSTGRESQL_VERSION} \
    && echo "zend_extension=opcache.so" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
