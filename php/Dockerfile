FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY tailwind.config.js ./
COPY resources/css/ ./resources/css/
RUN npm run build

FROM php:8.3-cli

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions pdo_pgsql zip mbstring bcmath intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

COPY . .

RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chmod +x docker-entrypoint.sh

COPY --from=frontend /app/public/css/ ./public/css/

EXPOSE 10000

ENTRYPOINT ["/bin/sh", "/app/docker-entrypoint.sh"]
