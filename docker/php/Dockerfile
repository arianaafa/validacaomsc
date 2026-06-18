FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-dev \
    libpq-dev \
    libzip-dev \
    oniguruma-dev \
    unzip \
    zip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
        intl \
        mbstring \
        opcache \
        pdo_pgsql \
        pgsql \
        zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
