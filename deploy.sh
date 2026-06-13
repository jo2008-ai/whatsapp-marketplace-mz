#!/bin/bash
set -e

#=============================================
# WhatsApp Marketplace SaaS — Deploy Script
# Para: Ubuntu 22.04/24.04 VPS
#=============================================

DOMAIN="${1:-localhost}"
EMAIL="${2:-admin@$DOMAIN}"
DB_PASSWORD=$(openssl rand -hex 16)
APP_KEY=$(openssl rand -base64 32)
PROJECT_DIR="/var/www/marketplace"

echo "=========================================="
echo " Deploy WhatsApp Marketplace SaaS"
echo " Domínio: $DOMAIN"
echo "=========================================="

#---------------------------------------------
# 1. Actualizar sistema
#---------------------------------------------
echo "[1/12] Actualizando sistema..."
apt-get update -qq
apt-get upgrade -y -qq

#---------------------------------------------
# 2. Instalar dependências
#---------------------------------------------
echo "[2/12] Instalando dependências..."
apt-get install -y -qq \
    nginx \
    postgresql postgresql-contrib \
    redis-server \
    php8.3-fpm php8.3-cli php8.3-pgsql php8.3-redis \
    php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
    php8.3-bcmath php8.3-intl php8.3-gd \
    supervisor \
    certbot python3-certbot-nginx \
    git unzip curl \
    python3 python3-pip python3-venv

#---------------------------------------------
# 3. Configurar PostgreSQL
#---------------------------------------------
echo "[3/12] Configurando PostgreSQL..."
systemctl start postgresql
systemctl enable postgresql

sudo -u postgres psql -c "DROP DATABASE IF EXISTS marketplace_saas;" 2>/dev/null || true
sudo -u postgres psql -c "DROP USER IF EXISTS marketplace;" 2>/dev/null || true
sudo -u postgres psql -c "CREATE USER marketplace WITH PASSWORD '$DB_PASSWORD';"
sudo -u postgres psql -c "CREATE DATABASE marketplace_saas OWNER marketplace;"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE marketplace_saas TO marketplace;"

#---------------------------------------------
# 4. Configurar Redis
#---------------------------------------------
echo "[4/12] Configurando Redis..."
systemctl start redis-server
systemctl enable redis-server

#---------------------------------------------
# 5. Configurar PHP-FPM
#---------------------------------------------
echo "[5/12] Configurando PHP-FPM..."
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 10M/' /etc/php/8.3/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 10M/' /etc/php/8.3/fpm/php.ini
sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php/8.3/fpm/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 60/' /etc/php/8.3/fpm/php.ini
systemctl restart php8.3-fpm

#---------------------------------------------
# 6. Copiar projecto
#---------------------------------------------
echo "[6/12] Copiando projecto..."
mkdir -p $PROJECT_DIR
rsync -a --exclude='.git' --exclude='node_modules' --exclude='vendor' \
    $(dirname "$0")/ $PROJECT_DIR/

#---------------------------------------------
# 7. Instalar dependências PHP
#---------------------------------------------
echo "[7/12] Instalando Composer dependencies..."
cd $PROJECT_DIR/php
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
composer install --no-dev --optimize-autoloader --no-interaction

#---------------------------------------------
# 8. Configurar Laravel
#---------------------------------------------
echo "[8/12] Configurando Laravel..."
cat > $PROJECT_DIR/php/.env <<EOF
APP_NAME="WhatsApp Marketplace SaaS"
APP_ENV=production
APP_KEY=base64:$APP_KEY
APP_DEBUG=false
APP_URL=https://$DOMAIN

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=marketplace_saas
DB_USERNAME=marketplace
DB_PASSWORD=$DB_PASSWORD

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=$(openssl rand -hex 16)

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

PYTHON_SERVICE_URL=http://127.0.0.1:5000

WAHA_URL_1=http://127.0.0.1:3001
WAHA_URL_2=http://127.0.0.1:3002
WAHA_URL_3=http://127.0.0.1:3003
WAHA_URL_4=http://127.0.0.1:3004
WAHA_API_KEY=$(openssl rand -hex 32)

LOG_CHANNEL=daily
LOG_LEVEL=error

SUPER_ADMIN_EMAIL=admin@$DOMAIN
EOF

cd $PROJECT_DIR/php
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link

#---------------------------------------------
# 9. Configurar Python
#---------------------------------------------
echo "[9/12] Configurando Python..."
cd $PROJECT_DIR/python
python3 -m venv venv
source venv/bin/activate
pip install -q flask requests python-dotenv gunicorn

cat > $PROJECT_DIR/python/.env <<EOF
PHP_API_URL=http://127.0.0.1:8000/api/mensagem
WAHA_API_KEY=$(grep WAHA_API_KEY $PROJECT_DIR/php/.env | cut -d= -f2)
WAHA_URL_1=http://127.0.0.1:3001
WAHA_URL_2=http://127.0.0.1:3002
WAHA_URL_3=http://127.0.0.1:3003
WAHA_URL_4=http://127.0.0.1:3004
APP_URL=https://$DOMAIN
FLASK_ENV=production
EOF

#---------------------------------------------
# 10. Configurar Nginx
#---------------------------------------------
echo "[10/12] Configurando Nginx..."
cat > /etc/nginx/sites-available/marketplace <<EOF
server {
    listen 80;
    server_name $DOMAIN;
    root $PROJECT_DIR/php/public;
    index index.php;

    charset utf-8;
    client_max_body_size 10M;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml;

    # Laravel
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # PHP-FPM
    location ~ \.php\$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny .env and hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static files cache
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2?)\$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Python webhook proxy
    location /webhook {
        proxy_pass http://127.0.0.1:5000;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    # Python health
    location /python-health {
        proxy_pass http://127.0.0.1:5000/health;
    }
}
EOF

ln -sf /etc/nginx/sites-available/marketplace /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

#---------------------------------------------
# 11. Configurar Supervisor (Queue Workers)
#---------------------------------------------
echo "[11/12] Configurando Supervisor..."

cat > /etc/supervisor/conf.d/marketplace-worker.conf <<EOF
[program:marketplace-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $PROJECT_DIR/php/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=$PROJECT_DIR/storage/logs/worker.log
stopwaitsecs=3600
EOF

cat > /etc/supervisor/conf.d/marketplace-scheduler.conf <<EOF
[program:marketplace-scheduler]
process_name=%(program_name)s
command=/bin/bash -c "while true; do php $PROJECT_DIR/php/artisan schedule:run --verbose --no-interaction & sleep 60; done"
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=$PROJECT_DIR/storage/logs/scheduler.log
EOF

cat > /etc/supervisor/conf.d/marketplace-python.conf <<EOF
[program:marketplace-python]
process_name=%(program_name)s
command=$PROJECT_DIR/python/venv/bin/gunicorn --bind 127.0.0.1:5000 --workers 2 --timeout 30 main:app
directory=$PROJECT_DIR/python
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=$PROJECT_DIR/storage/logs/python.log
environment=PYTHONUNBUFFERED="1"
EOF

supervisorctl reread
supervisorctl update
supervisorctl start all

#---------------------------------------------
# 12. Permissões e Firewall
#---------------------------------------------
echo "[12/12] Configurando permissões..."
chown -R www-data:www-data $PROJECT_DIR
chmod -R 755 $PROJECT_DIR
chmod -R 775 $PROJECT_DIR/storage
chmod -R 775 $PROJECT_DIR/bootstrap/cache

# Firewall
ufw allow 'Nginx Full'
ufw allow ssh
ufw --force enable

#---------------------------------------------
# SSL (se domínio não for localhost)
#---------------------------------------------
if [ "$DOMAIN" != "localhost" ]; then
    echo ""
    echo "Para activar SSL, execute:"
    echo "  certbot --nginx -d $DOMAIN --non-interactive --agree-tos -m $EMAIL"
    echo ""
fi

#---------------------------------------------
# Concluído
#---------------------------------------------
echo ""
echo "=========================================="
echo " DEPLOY CONCLUÍDO!"
echo "=========================================="
echo ""
echo " URL:      https://$DOMAIN"
echo " Login:    https://$DOMAIN/login"
echo " Super:    https://$DOMAIN/super"
echo " Registar: https://$DOMAIN/registar"
echo ""
echo " Credenciais:"
echo "   Super Admin: admin@$DOMAIN / admin123"
echo "   Mercearia:   mercearia@teste.com / 123456"
echo "   Boutique:    boutique@teste.com / 123456"
echo ""
echo " Base de dados: guardada em $PROJECT_DIR/php/.env"
echo " (guarda esta password!)"
echo ""
echo " Comandos úteis:"
echo "   supervisorctl status"
echo "   supervisorctl restart all"
echo "   tail -f $PROJECT_DIR/storage/logs/laravel.log"
echo "   php $PROJECT_DIR/php/artisan tinker"
echo ""
echo " Para SSL:"
echo "   certbot --nginx -d $DOMAIN"
echo "=========================================="
