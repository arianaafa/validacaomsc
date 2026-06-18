#!/bin/sh
set -e

if [ -f artisan ]; then
    mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || chmod -R 777 storage bootstrap/cache

    if [ ! -d vendor ]; then
        composer install --no-interaction --prefer-dist
    fi

    if [ ! -f .env ] && [ -f .env.example ]; then
        cp .env.example .env
    fi

    if [ -f .env ] && ! grep -q '^APP_KEY=base64:' .env; then
        php artisan key:generate --force
    fi
fi

exec "$@"
