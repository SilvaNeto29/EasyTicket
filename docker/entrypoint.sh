#!/bin/sh
set -e

cd /var/www/html

if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Fix permissions on mounted volumes (host files may be owned by a different user)
chmod -R 775 storage bootstrap/cache database 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache database 2>/dev/null || true

php artisan key:generate --no-interaction --force 2>/dev/null || true
php artisan migrate --force --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction

# Ensure bootstrap/cache files are writable by the host user (uid 1000) for local dev
chmod -R 777 bootstrap/cache 2>/dev/null || true

php-fpm -D
nginx -g "daemon off;"
