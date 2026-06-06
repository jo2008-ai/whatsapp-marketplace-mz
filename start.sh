#!/bin/bash

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "=========================================="
echo " WhatsApp Marketplace SaaS"
echo "=========================================="
echo ""

# Verificar serviços
echo "[1/4] Verificando PostgreSQL..."
if pg_isready -q 2>/dev/null; then
    echo "  ✅ PostgreSQL a correr"
else
    echo "  ❌ PostgreSQL não está a correr. Execute: sudo systemctl start postgresql"
    exit 1
fi

echo "[2/4] Verificando Redis..."
if redis-cli ping 2>/dev/null | grep -q PONG; then
    echo "  ✅ Redis a correr"
else
    echo "  ⚠️  Redis não está a correr. Execute: sudo systemctl start redis-server"
    echo "  Continuando sem Redis (usando file cache)..."
fi

echo "[3/4] Iniciando Laravel na porta 8000..."
cd "$PROJECT_DIR/php"
php artisan serve --host=0.0.0.0 --port=8000 > /tmp/laravel.log 2>&1 &
LARAVEL_PID=$!
echo "  ✅ Laravel PID: $LARAVEL_PID"

echo "[4/4] Iniciando Python Flask na porta 5000..."
cd "$PROJECT_DIR/python"
source venv/bin/activate
python3 main.py > /tmp/python.log 2>&1 &
PYTHON_PID=$!
echo "  ✅ Python PID: $PYTHON_PID"

echo ""
echo "=========================================="
echo " SERVIÇOS A CORRER"
echo "=========================================="
echo ""
echo " Laravel:   http://localhost:8000"
echo " Python:    http://localhost:5000/health"
echo " Login:     http://localhost:8000/login"
echo " Super:     http://localhost:8000/super"
echo " Registar:  http://localhost:8000/registar"
echo ""
echo " Credenciais:"
echo "   Super Admin: admin@plataforma.com / admin123"
echo "   Mercearia:   mercearia@teste.com / 123456"
echo "   Boutique:    boutique@teste.com / 123456"
echo ""
echo " Logs:"
echo "   Laravel: tail -f /tmp/laravel.log"
echo "   Python:  tail -f /tmp/python.log"
echo ""
echo " Para parar: kill $LARAVEL_PID $PYTHON_PID"
echo "=========================================="

# Esperar
wait
