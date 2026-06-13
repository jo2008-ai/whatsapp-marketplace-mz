#!/bin/sh
set -e

echo "==> Clearing caches..."
php artisan config:clear
php artisan cache:clear

echo "==> Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan storage:link 2>/dev/null || true

echo "==> Waiting for database..."
for i in $(seq 1 30); do
    if php artisan db:show > /dev/null 2>&1; then
        echo "==> Database is ready."
        break
    fi
    echo "==> Database not ready, retrying in 2s... ($i/30)"
    sleep 2
done

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Starting server on port ${PORT:-10000}..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
