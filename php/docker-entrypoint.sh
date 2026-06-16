#!/bin/bash
set -uo pipefail

echo "==> [1/7] A iniciar entrypoint..."
echo "==> PORT=${PORT:-10000}"
echo "==> APP_ENV=${APP_ENV:-production}"
echo "==> APP_DEBUG=${APP_DEBUG:-false}"

echo "==> [2/7] A limpar caches..."
php artisan config:clear || echo "AVISO: config:clear falhou (ignorado)"
php artisan cache:clear  || echo "AVISO: cache:clear falhou (ignorado)"

echo "==> [3/7] A regenerar caches..."
php artisan config:cache || echo "AVISO: config:cache falhou (ignorado)"
php artisan route:cache  || echo "AVISO: route:cache falhou (ignorado)"
php artisan view:cache   || echo "AVISO: view:cache falhou (ignorado)"

echo "==> [4/7] A criar storage:link..."
php artisan storage:link 2>/dev/null || echo "storage:link ja existe ou nao suportado (ok)"

echo "==> [5/7] A aguardar base de dados..."
DB_READY=false
for i in $(seq 1 30); do
    if php artisan db:show > /dev/null 2>&1; then
        echo "==> Base de dados pronta apos ${i} tentativas."
        DB_READY=true
        break
    fi
    echo "==> Base de dados nao pronta, tentativa $i/30..."
    sleep 2
done

if [ "$DB_READY" = false ]; then
    echo "AVISO: Base de dados nao disponivel apos 60s. A iniciar servidor mesmo assim..."
fi

echo "==> [6/7] A correr migrations..."
if [ "$DB_READY" = true ]; then
    php artisan migrate --force || echo "AVISO: migrate falhou (a continuar...)"
fi

echo "==> [7/7] A iniciar servidor na porta ${PORT:-10000}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
