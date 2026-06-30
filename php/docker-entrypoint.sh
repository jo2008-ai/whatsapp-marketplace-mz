#!/bin/sh

echo "==> [ENTRYPOINT] Script iniciado"
echo "==> pwd: $(pwd)"
echo "==> ls: $(ls -la /app/docker-entrypoint.sh 2>&1 || echo 'ficheiro nao encontrado')"
echo "==> PORT=${PORT:-10000}"
echo "==> APP_ENV=${APP_ENV:-production}"
echo "==> APP_DEBUG=${APP_DEBUG:-false}"

echo "==> [1/8] A limpar caches..."
php artisan config:clear 2>&1 || echo "AVISO: config:clear falhou (ignorado)"
php artisan cache:clear 2>&1  || echo "AVISO: cache:clear falhou (ignorado)"
php artisan view:clear 2>&1   || echo "AVISO: view:clear falhou (ignorado)"

echo "==> [2/8] A regenerar caches..."
php artisan config:cache 2>&1 || echo "AVISO: config:cache falhou (ignorado)"
php artisan route:cache 2>&1  || echo "AVISO: route:cache falhou (ignorado)"
php artisan view:cache 2>&1   || echo "AVISO: view:cache falhou (ignorado)"

echo "==> [3/8] A criar storage:link..."
php artisan storage:link 2>/dev/null || echo "storage:link ja existe ou nao suportado (ok)"

echo "==> [4/8] A aguardar base de dados..."
DB_READY=false
i=1
while [ $i -le 30 ]; do
    if php artisan db:show > /dev/null 2>&1; then
        echo "==> Base de dados pronta apos ${i} tentativas."
        DB_READY=true
        break
    fi
    echo "==> Base de dados nao pronta, tentativa $i/30..."
    i=$((i + 1))
    sleep 2
done

if [ "$DB_READY" = false ]; then
    echo "AVISO: Base de dados nao disponivel apos 60s. A iniciar servidor mesmo assim..."
fi

echo "==> [5/8] A correr migrations..."
if [ "$DB_READY" = true ]; then
    php artisan migrate --force 2>&1 || echo "AVISO: migrate falhou (a continuar...)"
    php artisan migrate --force 2>&1 || echo "AVISO: 2o attempt migrate falhou (a continuar...)"
fi

echo "==> [6/8] A correr seeders..."
if [ "$DB_READY" = true ]; then
    php artisan db:seed --force 2>&1 || echo "AVISO: seed falhou (a continuar...)"
fi

echo "==> [7/8] A iniciar scheduler em background..."
(while true; do php artisan schedule:run --force 2>&1; sleep 60; done) &

echo "==> [8/8] A iniciar servidor na porta ${PORT:-10000}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
