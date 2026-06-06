# Deploy para Servidor Cloud (VPS)

## Pré-requisitos

- VPS Ubuntu 22.04 ou 24.04
- Acesso root ou sudo
- Domínio apontando para o IP do VPS (opcional)

## Opção 1 — Script automático

```bash
# Copiar projecto para o servidor
scp -r whatsapp-marketplace-saas/ root@SEU_IP:/tmp/

# No servidor:
ssh root@SEU_IP
cd /tmp/whatsapp-marketplace-saas
chmod +x deploy.sh

# Com domínio:
./deploy.sh meudominio.com admin@meudominio.com

# Sem domínio (IP directo):
./deploy.sh localhost
```

O script instala automaticamente:
- Nginx (reverse proxy)
- PostgreSQL 16
- Redis
- PHP 8.3 FPM
- Supervisor (queue workers)
- Certbot (SSL)

## Opção 2 — Manual

### 1. Instalar dependências

```bash
apt-get update && apt-get install -y \
    nginx postgresql redis-server \
    php8.3-fpm php8.3-pgsql php8.3-redis php8.3-mbstring php8.3-xml \
    php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl \
    supervisor certbot python3-certbot-nginx \
    python3 python3-pip python3-venv
```

### 2. Configurar PostgreSQL

```bash
sudo -u postgres psql -c "CREATE USER marketplace WITH PASSWORD 'PASSWORD_AQUI';"
sudo -u postgres psql -c "CREATE DATABASE marketplace_saas OWNER marketplace;"
```

### 3. Copiar projecto

```bash
mkdir -p /var/www/marketplace
cp -r php/ python/ /var/www/marketplace/
```

### 4. Instalar dependências

```bash
cd /var/www/marketplace/php
composer install --no-dev --optimize-autoloader

cd /var/www/marketplace/python
python3 -m venv venv
source venv/bin/activate
pip install flask requests python-dotenv gunicorn
```

### 5. Configurar .env

```bash
cd /var/www/marketplace/php
cp .env.example .env
php artisan key:generate
# Editar .env com dados de produção
```

### 6. Migrar e seed

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan storage:link
```

### 7. Configurar Nginx

```bash
# Ficheiro em /etc/nginx/sites-available/marketplace
# Ver deploy.sh para configuração completa
```

### 8. Configurar Supervisor

```bash
# Ficheiros em /etc/supervisor/conf.d/
# Ver deploy.sh para configuração completa
supervisorctl reread && supervisorctl update
```

### 9. SSL

```bash
certbot --nginx -d meudominio.com
```

## Comandos úteis

```bash
# Status dos serviços
supervisorctl status

# Reiniciar workers
supervisorctl restart marketplace-worker:*

# Logs
tail -f /var/www/marketplace/storage/logs/laravel.log
tail -f /var/www/marketplace/storage/logs/worker.log

# Artisan
cd /var/www/marketplace/php
php artisan tinker
php artisan migrate
php artisan cache:clear

# PostgreSQL
sudo -u postgres psql marketplace_saas
```

## Arquitectura no servidor

```
Internet → Nginx (porta 80/443)
    ├── / → PHP-FPM (Laravel)
    ├── /webhook → Python Flask (porta 5000)
    └── /static → Ficheiros estáticos

Supervisor gere:
    ├── marketplace-worker (2 processos queue)
    ├── marketplace-scheduler (cron)
    └── marketplace-python (gunicorn)
```

## Actualizar

```bash
cd /var/www/marketplace
git pull origin main

cd php
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

supervisorctl restart all
```
