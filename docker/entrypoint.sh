#!/bin/sh
set -e

cd /var/www/html

if [ ! -f ".env" ]; then
    cp .env.example .env
fi

php artisan key:generate --no-interaction --force 2>/dev/null || true
php artisan migrate --force --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction

php-fpm -D
nginx -g "daemon off;"
