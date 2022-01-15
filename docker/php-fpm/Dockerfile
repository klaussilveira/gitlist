FROM php:8.1-fpm-alpine

ENV PHP_EXT_DEPS \
    gettext=gettext-dev \
    icu=icu-dev \
    libgcrypt=libgcrypt-dev \
    libxml2=libxml2-dev \
    libxslt=libxslt-dev \
    libzip=libzip-dev

RUN set -x; \
    apk add --no-cache --virtual .php-extensions-build-dependencies \
        $PHPIZE_DEPS \
        $(echo ${PHP_EXT_DEPS} | tr ' ' '\n' | cut -d = -f 2) \
    && apk add --no-cache \
        $(echo ${PHP_EXT_DEPS} | tr ' ' '\n' | cut -d = -f 1) \
    && docker-php-ext-install \
        exif \
        gettext \
        intl \
        opcache \
        sockets \
        xsl \
        zip \
    && printf "\n" | pecl install apcu xdebug \
    && docker-php-ext-enable apcu \
    && apk del .php-extensions-build-dependencies

RUN apk add --no-cache \
    bash \
    coreutils \
    git \
    grep \
    mercurial \
    make \
    wget

ENV COMPOSER_ALLOW_SUPERUSER 1

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --2

ENV PATH="/application/bin:/application/vendor/bin:${PATH}"

WORKDIR "/application"

CMD ["php-fpm", "--allow-to-run-as-root"]

COPY php.ini /usr/local/etc/php/conf.d/99-overrides.ini
COPY php-fpm.d/* /usr/local/etc/php-fpm.d/
