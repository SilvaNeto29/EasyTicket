FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    nginx \
    nodejs \
    npm \
    git \
    unzip \
    curl \
    oniguruma-dev \
    libxml2-dev \
    sqlite \
    sqlite-dev

RUN docker-php-ext-install bcmath pcntl mbstring xml

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

RUN npm install && npm run build

RUN mkdir -p database storage/logs storage/framework/cache \
        storage/framework/sessions storage/framework/views \
        bootstrap/cache && \
    touch database/database.sqlite && \
    chmod -R 775 storage bootstrap/cache database && \
    chown -R www-data:www-data storage bootstrap/cache database

COPY docker/nginx.conf /etc/nginx/nginx.conf

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
