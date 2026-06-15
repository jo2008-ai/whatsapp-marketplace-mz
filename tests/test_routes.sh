#!/bin/bash
# WhatsApp Marketplace — Teste de Rotas
# Uso: bash tests/test_routes.sh

set -euo pipefail

BASE_URL="https://whatsapp-marketplace-mz.onrender.com"
PYTHON_URL="https://marketplace-python.onrender.com"
EMAIL="${TEST_EMAIL:-mercearia@teste.com}"
PASSWORD="${TEST_PASSWORD:-123456}"
ADMIN_KEY="${ADMIN_API_KEY:-}"

PASS=0
FAIL=0
TOKEN=""
PRODUTO_ID=""
CATEGORIA_ID=""
VENDEDOR_ID=""
ENCOMENDA_ID=""

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

test_route() {
    local name=$1
    local expected_status=$2
    local actual_status=$3
    local response=$4

    if [ "$actual_status" = "$expected_status" ]; then
        echo -e "${GREEN}PASS:${NC} $name (${actual_status})"
        PASS=$((PASS+1))
    else
        echo -e "${RED}FAIL:${NC} $name (esperado: ${expected_status}, obtido: ${actual_status})"
        echo "   Resposta: ${response:0:200}"
        FAIL=$((FAIL+1))
    fi
}

cleanup() {
    echo ""
    echo -e "${YELLOW}--- Cleanup: apagando dados de teste ---${NC}"

    if [ -n "$TOKEN" ] && [ -n "$PRODUTO_ID" ]; then
        STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
            -X DELETE "${BASE_URL}/api/loja/produtos/${PRODUTO_ID}" \
            -H "Authorization: Bearer ${TOKEN}" 2>/dev/null || true)
        echo "   DELETE produto ${PRODUTO_ID}: ${STATUS}"
    fi

    if [ -n "$TOKEN" ] && [ -n "$CATEGORIA_ID" ]; then
        STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
            -X DELETE "${BASE_URL}/api/loja/categorias/${CATEGORIA_ID}" \
            -H "Authorization: Bearer ${TOKEN}" 2>/dev/null || true)
        echo "   DELETE categoria ${CATEGORIA_ID}: ${STATUS}"
    fi

    if [ -n "$TOKEN" ] && [ -n "$VENDEDOR_ID" ]; then
        STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
            -X DELETE "${BASE_URL}/api/loja/vendedores/${VENDEDOR_ID}" \
            -H "Authorization: Bearer ${TOKEN}" 2>/dev/null || true)
        echo "   DELETE vendedor ${VENDEDOR_ID}: ${STATUS}"
    fi
}

trap cleanup EXIT

echo "============================================"
echo " WhatsApp Marketplace — Teste de Rotas"
echo "============================================"
echo ""

# ─── ROTAS PÚBLICAS (BROWSER) ───────────────────────────

echo -e "${YELLOW}--- Rotas Publicas ---${NC}"

RESP=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/up" 2>/dev/null)
test_route "Health check /up" "200" "$RESP" ""

RESP=$(curl -s "${BASE_URL}/up" 2>/dev/null)
if echo "$RESP" | grep -q '"status"'; then
    echo "   Body: $RESP"
fi

RESP=$(curl -s -o /dev/null -w "%{http_code}" -L "${BASE_URL}/" 2>/dev/null)
test_route "Landing page /" "200" "$RESP" ""

RESP=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/login" 2>/dev/null)
test_route "Pagina de login /login" "200" "$RESP" ""

RESP=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/registar" 2>/dev/null)
test_route "Rota /registar devolve 404 (removida)" "404" "$RESP" ""

echo ""

# ─── API AUTH ───────────────────────────────────────────

echo -e "${YELLOW}--- API Auth ---${NC}"

LOGIN_RESP=$(curl -s -X POST "${BASE_URL}/api/auth/login" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"${EMAIL}\",\"password\":\"${PASSWORD}\"}" 2>/dev/null)

LOGIN_STATUS=$(echo "$LOGIN_RESP" | python3 -c "import sys,json; print(json.load(sys.stdin).get('success',''))" 2>/dev/null || echo "false")

if [ "$LOGIN_STATUS" = "True" ] || [ "$LOGIN_STATUS" = "true" ]; then
    TOKEN=$(echo "$LOGIN_RESP" | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['token'])" 2>/dev/null || echo "")
    test_route "API login POST /api/auth/login" "200" "200" "$LOGIN_RESP"
else
    test_route "API login POST /api/auth/login" "200" "429" "$LOGIN_RESP"
    echo -e "${RED}ERRO: Nao foi possivel obter token. Todos os testes autenticados irao falhar.${NC}"
fi

if [ -n "$TOKEN" ]; then
    ME_RESP=$(curl -s -w "\n%{http_code}" "${BASE_URL}/api/auth/me" \
        -H "Authorization: Bearer ${TOKEN}" 2>/dev/null)
    ME_STATUS=$(echo "$ME_RESP" | tail -1)
    ME_BODY=$(echo "$ME_RESP" | head -n -1)
    test_route "API me GET /api/auth/me" "200" "$ME_STATUS" "$ME_BODY"

    LOGOUT_RESP=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${BASE_URL}/api/auth/logout" \
        -H "Authorization: Bearer ${TOKEN}" 2>/dev/null)
    test_route "API logout POST /api/auth/logout" "200" "$LOGOUT_RESP" ""

    # Login again for subsequent tests
    LOGIN_RESP=$(curl -s -X POST "${BASE_URL}/api/auth/login" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"${EMAIL}\",\"password\":\"${PASSWORD}\"}" 2>/dev/null)
    TOKEN=$(echo "$LOGIN_RESP" | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['token'])" 2>/dev/null || echo "")
fi

echo ""

# ─── API DASHBOARD ──────────────────────────────────────

echo -e "${YELLOW}--- API Dashboard ---${NC}"

if [ -n "$TOKEN" ]; then
    DASH_RESP=$(curl -s -w "\n%{http_code}" "${BASE_URL}/api/loja/dashboard" \
        -H "Authorization: Bearer ${TOKEN}" 2>/dev/null)
    DASH_STATUS=$(echo "$DASH_RESP" | tail -1)
    DASH_BODY=$(echo "$DASH_RESP" | head -n -1)
    test_route "Dashboard GET /api/loja/dashboard" "200" "$DASH_STATUS" "$DASH_BODY"
else
    test_route "Dashboard GET /api/loja/dashboard" "200" "401" "sem token"
fi

echo ""

# ─── API CATEGORIAS ─────────────────────────────────────

echo -e "${YELLOW}--- API Categorias ---${NC}"

if [ -n "$TOKEN" ]; then
    CAT_LIST=$(curl -s -w "\n%{http_code}" "${BASE_URL}/api/loja/categorias" \
        -H "Authorization: Bearer ${TOKEN}" 2>/dev/null)
    CAT_STATUS=$(echo "$CAT_LIST" | tail -1)
    CAT_BODY=$(echo "$CAT_LIST" | head -n -1)
    test_route "Categorias GET /api/loja/categorias" "200" "$CAT_STATUS" "$CAT_BODY"

    CAT_CREATE=$(curl -s -w "\n%{http_code}" -X POST "${BASE_URL}/api/loja/categorias" \
        -H "Authorization: Bearer ${TOKEN}" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{"nome":"Electronica","icone":"📱"}' 2>/dev/null)
    CAT_CREATE_STATUS=$(echo "$CAT_CREATE" | tail -1)
    CAT_CREATE_BODY=$(echo "$CAT_CREATE" | head -n -1)
    test_route "Categorias POST /api/loja/categorias" "201" "$CAT_CREATE_STATUS" "$CAT_CREATE_BODY"

    CATEGORIA_ID=$(echo "$CAT_CREATE_BODY" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('data',{}).get('id',''))" 2>/dev/null || echo "")

    if [ -n "$CATEGORIA_ID" ]; then
        CAT_SHOW=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/api/loja/categorias/${CATEGORIA_ID}" \
            -H "Authorization: Bearer ${TOKEN}" 2>/dev/null)
        test_route "Categorias GET /api/loja/categorias/${CATEGORIA_ID}" "200" "$CAT_SHOW" ""

        CAT_UPDATE=$(curl -s -o /dev/null -w "%{http_code}" -X PUT "${BASE_URL}/api/loja/categorias/${CATEGORIA_ID}" \
            -H "Authorization: Bearer ${TOKEN}" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d '{"nome":"Electronica Atualizada","icone":"💻"}' 2>/dev/null)
        test_route "Categorias PUT /api/loja/categorias/${CATEGORIA_ID}" "200" "$CAT_UPDATE" ""
    fi
else
    test_route "Categorias GET" "200" "401" "sem token"
    test_route "Categorias POST" "201" "401" "sem token"
fi

echo ""

# ─── API VENDEDORES ─────────────────────────────────────

echo -e "${YELLOW}--- API Vendedores ---${NC}"

if [ -n "$TOKEN" ]; then
    VEND_LIST=$(curl -s -w "\n%{http_code}" "${BASE_URL}/api/loja/vendedores" \
        -H "Authorization: Bearer ${TOKEN}" 2>/dev/null)
    VEND_STATUS=$(echo "$VEND_LIST" | tail -1)
    VEND_BODY=$(echo "$VEND_LIST" | head -n -1)
    test_route "Vendedores GET /api/loja/vendedores" "200" "$VEND_STATUS" "$VEND_BODY"

    VEND_CREATE=$(curl -s -w "\n%{http_code}" -X POST "${BASE_URL}/api/loja/vendedores" \
        -H "Authorization: Bearer ${TOKEN}" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{"nome":"Joao Silva","numero_whatsapp":"258841234567"}' 2>/dev/null)
    VEND_CREATE_STATUS=$(echo "$VEND_CREATE" | tail -1)
    VEND_CREATE_BODY=$(echo "$VEND_CREATE" | head -n -1)
    test_route "Vendedores POST /api/loja/vendedores" "201" "$VEND_CREATE_STATUS" "$VEND_CREATE_BODY"

    VENDEDOR_ID=$(echo "$VEND_CREATE_BODY" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('data',{}).get('id',''))" 2>/dev/null || echo "")

    if [ -n "$VENDEDOR_ID" ]; then
        VEND_SHOW=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/api/loja/vendedores/${VENDEDOR_ID}" \
            -H "Authorization: Bearer ${TOKEN}" 2>/dev/null)
        test_route "Vendedores GET /api/loja/vendedores/${VENDEDOR_ID}" "200" "$VEND_SHOW" ""

        VEND_TOGGLE=$(curl -s -o /dev/null -w "%{http_code}" -X PATCH "${BASE_URL}/api/loja/vendedores/${VENDEDOR_ID}/toggle" \
            -H "Authorization: Bearer ${TOKEN}" 2>/dev/null)
        test_route "Vendedores PATCH toggle" "200" "$VEND_TOGGLE" ""
    fi
else
    test_route "Vendedores GET" "200" "401" "sem token"
    test_route "Vendedores POST" "201" "401" "sem token"
fi

echo ""

# ─── API PRODUTOS ───────────────────────────────────────

echo -e "${YELLOW}--- API Produtos ---${NC}"

if [ -n "$TOKEN" ]; then
    # GET produtos
    PROD_LIST=$(curl -s -w "\n%{http_code}" "${BASE_URL}/api/loja/produtos" \
        -H "Authorization: Bearer ${TOKEN}" 2>/dev/null)
    PROD_STATUS=$(echo "$PROD_LIST" | tail -1)
    PROD_BODY=$(echo "$PROD_LIST" | head -n -1)
    test_route "Produtos GET /api/loja/produtos" "200" "$PROD_STATUS" "$PROD_BODY"

    # POST criar produto (requer categoria_id e vendedor_id)
    PROD_PAYLOAD="{\"nome\":\"Produto Teste\",\"preco\":100,\"stock\":10"
    if [ -n "$CATEGORIA_ID" ]; then
        PROD_PAYLOAD="${PROD_PAYLOAD},\"categoria_id\":${CATEGORIA_ID}"
    fi
    if [ -n "$VENDEDOR_ID" ]; then
        PROD_PAYLOAD="${PROD_PAYLOAD},\"vendedor_id\":${VENDEDOR_ID}"
    fi
    PROD_PAYLOAD="${PROD_PAYLOAD}}"

    PROD_CREATE=$(curl -s -w "\n%{http_code}" -X POST "${BASE_URL}/api/loja/produtos" \
        -H "Authorization: Bearer ${TOKEN}" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "$PROD_PAYLOAD" 2>/dev/null)
    PROD_CREATE_STATUS=$(echo "$PROD_CREATE" | tail -1)
    PROD_CREATE_BODY=$(echo "$PROD_CREATE" | head -n -1)
    test_route "Produtos POST /api/loja/produtos" "201" "$PROD_CREATE_STATUS" "$PROD_CREATE_BODY"

    PRODUTO_ID=$(echo "$PROD_CREATE_BODY" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('data',{}).get('id',''))" 2>/dev/null || echo "")

    if [ -n "$PRODUTO_ID" ]; then
        # GET produto por ID
        PROD_SHOW=$(curl -s -w "\n%{http_code}" "${BASE_URL}/api/loja/produtos/${PRODUTO_ID}" \
            -H "Authorization: Bearer ${TOKEN}" 2>/dev/null)
        PROD_SHOW_STATUS=$(echo "$PROD_SHOW" | tail -1)
        PROD_SHOW_BODY=$(echo "$PROD_SHOW" | head -n -1)
        test_route "Produtos GET /api/loja/produtos/${PRODUTO_ID}" "200" "$PROD_SHOW_STATUS" "$PROD_SHOW_BODY"

        # PUT atualizar produto
        PROD_UPDATE=$(curl -s -o /dev/null -w "%{http_code}" -X PUT "${BASE_URL}/api/loja/produtos/${PRODUTO_ID}" \
            -H "Authorization: Bearer ${TOKEN}" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d "{\"nome\":\"Produto Teste Atualizado\",\"preco\":150,\"stock\":20,\"categoria_id\":${CATEGORIA_ID},\"vendedor_id\":${VENDEDOR_ID}}" 2>/dev/null)
        test_route "Produtos PUT /api/loja/produtos/${PRODUTO_ID}" "200" "$PROD_UPDATE" ""

        # PATCH toggle
        TOGGLE_RESP=$(curl -s -o /dev/null -w "%{http_code}" -X PATCH "${BASE_URL}/api/loja/produtos/${PRODUTO_ID}/toggle" \
            -H "Authorization: Bearer ${TOKEN}" 2>/dev/null)
        test_route "Produtos PATCH toggle /api/loja/produtos/${PRODUTO_ID}/toggle" "200" "$TOGGLE_RESP" ""
    fi
else
    test_route "Produtos GET" "200" "401" "sem token"
    test_route "Produtos POST" "201" "401" "sem token"
fi

echo ""

# ─── API ENCOMENDAS ─────────────────────────────────────

echo -e "${YELLOW}--- API Encomendas ---${NC}"

if [ -n "$TOKEN" ]; then
    ENC_LIST=$(curl -s -w "\n%{http_code}" "${BASE_URL}/api/loja/encomendas" \
        -H "Authorization: Bearer ${TOKEN}" 2>/dev/null)
    ENC_STATUS=$(echo "$ENC_LIST" | tail -1)
    ENC_BODY=$(echo "$ENC_LIST" | head -n -1)
    test_route "Encomendas GET /api/loja/encomendas" "200" "$ENC_STATUS" "$ENC_BODY"

    ENCOMENDA_ID=$(echo "$ENC_BODY" | python3 -c "
import sys,json
d=json.load(sys.stdin)
data=d.get('data',{})
if isinstance(data, dict):
    items=data.get('data',[])
else:
    items=data if isinstance(data, list) else []
print(items[0]['id'] if items else '')
" 2>/dev/null || echo "")

    if [ -n "$ENCOMENDA_ID" ]; then
        ENC_PATCH=$(curl -s -o /dev/null -w "%{http_code}" -X PATCH "${BASE_URL}/api/loja/encomendas/${ENCOMENDA_ID}/estado" \
            -H "Authorization: Bearer ${TOKEN}" \
            -H "Content-Type: application/json" \
            -d '{"estado":"confirmada"}' 2>/dev/null)
        test_route "Encomendas PATCH /api/loja/encomendas/${ENCOMENDA_ID}/estado" "200" "$ENC_PATCH" ""
    else
        echo "   Nenhuma encomenda encontrada para testar PATCH"
    fi
else
    test_route "Encomendas GET" "200" "401" "sem token"
fi

echo ""

# ─── API DEFINICOES ─────────────────────────────────────

echo -e "${YELLOW}--- API Definicoes ---${NC}"

if [ -n "$TOKEN" ]; then
    DEF_GET=$(curl -s -w "\n%{http_code}" "${BASE_URL}/api/loja/definicoes" \
        -H "Authorization: Bearer ${TOKEN}" 2>/dev/null)
    DEF_STATUS=$(echo "$DEF_GET" | tail -1)
    DEF_BODY=$(echo "$DEF_GET" | head -n -1)
    test_route "Definicoes GET /api/loja/definicoes" "200" "$DEF_STATUS" "$DEF_BODY"
else
    test_route "Definicoes GET" "200" "401" "sem token"
fi

echo ""

# ─── API UPLOAD ─────────────────────────────────────────

echo -e "${YELLOW}--- API Upload ---${NC}"

if [ -n "$TOKEN" ]; then
    TMPFILE=$(mktemp /tmp/test_upload_XXXXXX.png)
    python3 -c "
import struct, zlib
def create_png(path):
    sig = b'\\x89PNG\\r\\n\\x1a\\n'
    ihdr_data = struct.pack('>IIBBBBB', 1, 1, 8, 2, 0, 0, 0)
    ihdr_crc = zlib.crc32(b'IHDR' + ihdr_data) & 0xffffffff
    ihdr = struct.pack('>I', 13) + b'IHDR' + ihdr_data + struct.pack('>I', ihdr_crc)
    raw = zlib.compress(b'\\x00\\xff\\x00\\x00')
    idat_crc = zlib.crc32(b'IDAT' + raw) & 0xffffffff
    idat = struct.pack('>I', len(raw)) + b'IDAT' + raw + struct.pack('>I', idat_crc)
    iend_crc = zlib.crc32(b'IEND') & 0xffffffff
    iend = struct.pack('>I', 0) + b'IEND' + struct.pack('>I', iend_crc)
    with open(path, 'wb') as f:
        f.write(sig + ihdr + idat + iend)
create_png('$TMPFILE')
" 2>/dev/null

    UPLOAD_RESP=$(curl -s -w "\n%{http_code}" -X POST "${BASE_URL}/api/loja/upload/imagem" \
        -H "Authorization: Bearer ${TOKEN}" \
        -F "imagem=@${TMPFILE}" 2>/dev/null)
    UPLOAD_STATUS=$(echo "$UPLOAD_RESP" | tail -1)
    UPLOAD_BODY=$(echo "$UPLOAD_RESP" | head -n -1)
    test_route "Upload POST /api/loja/upload/imagem" "200" "$UPLOAD_STATUS" "$UPLOAD_BODY"

    rm -f "$TMPFILE"
else
    test_route "Upload POST" "200" "401" "sem token"
fi

echo ""

# ─── API ADMIN ──────────────────────────────────────────

echo -e "${YELLOW}--- API Admin ---${NC}"

if [ -n "$ADMIN_KEY" ]; then
    ADMIN_LIST=$(curl -s -w "\n%{http_code}" "${BASE_URL}/api/admin/lojas" \
        -H "X-Admin-Key: ${ADMIN_KEY}" 2>/dev/null)
    ADMIN_STATUS=$(echo "$ADMIN_LIST" | tail -1)
    ADMIN_BODY=$(echo "$ADMIN_LIST" | head -n -1)
    test_route "Admin lojas GET /api/admin/lojas" "200" "$ADMIN_STATUS" "$ADMIN_BODY"

    UNIQUE_EMAIL="teste_$(date +%s)@teste.com"
    ADMIN_CREATE=$(curl -s -w "\n%{http_code}" -X POST "${BASE_URL}/api/admin/lojas" \
        -H "X-Admin-Key: ${ADMIN_KEY}" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{\"nome_loja\":\"Loja Teste Script\",\"email\":\"${UNIQUE_EMAIL}\",\"telefone\":\"258841234567\"}" 2>/dev/null)
    ADMIN_CREATE_STATUS=$(echo "$ADMIN_CREATE" | tail -1)
    ADMIN_CREATE_BODY=$(echo "$ADMIN_CREATE" | head -n -1)
    test_route "Admin lojas POST /api/admin/lojas" "201" "$ADMIN_CREATE_STATUS" "$ADMIN_CREATE_BODY"
else
    echo -e "${YELLOW}ADMIN_API_KEY nao definida - saltando testes admin${NC}"
    echo "   Defina: export ADMIN_API_KEY=sua_chave_aqui"
fi

echo ""

# ─── WEBHOOK (Python -> PHP) ────────────────────────────

echo -e "${YELLOW}--- Webhook (Python -> PHP) ---${NC}"

WEBHOOK_SECRET="${WEBHOOK_SECRET:-marketplace_secret_key_2026}"
WEBHOOK_BODY='{"tenant_id":1,"numero":"258841234567","mensagem":"Ola","nome":"Cliente Teste","is_grupo":false}'
WEBHOOK_SIG=$(echo -n "$WEBHOOK_BODY" | openssl dgst -sha256 -hmac "$WEBHOOK_SECRET" -binary | xxd -p -c 256)

WEBHOOK_RESP=$(curl -s -w "\n%{http_code}" -X POST "${BASE_URL}/api/mensagem" \
    -H "Content-Type: application/json" \
    -H "X-Webhook-Signature: ${WEBHOOK_SIG}" \
    -d "$WEBHOOK_BODY" 2>/dev/null)
WEBHOOK_STATUS=$(echo "$WEBHOOK_RESP" | tail -1)
WEBHOOK_BODY_RESP=$(echo "$WEBHOOK_RESP" | head -n -1)
test_route "Webhook POST /api/mensagem" "200" "$WEBHOOK_STATUS" "$WEBHOOK_BODY_RESP"

echo ""

# ─── ROTAS PYTHON FLASK ─────────────────────────────────

echo -e "${YELLOW}--- Python Flask ---${NC}"

PY_HEALTH=$(curl -s -w "\n%{http_code}" "${PYTHON_URL}/health" 2>/dev/null)
PY_HEALTH_STATUS=$(echo "$PY_HEALTH" | tail -1)
PY_HEALTH_BODY=$(echo "$PY_HEALTH" | head -n -1)
test_route "Python health GET /health" "200" "$PY_HEALTH_STATUS" "$PY_HEALTH_BODY"

PY_QR=$(curl -s -w "\n%{http_code}" "${PYTHON_URL}/qr/1" 2>/dev/null)
PY_QR_STATUS=$(echo "$PY_QR" | tail -1)
PY_QR_BODY=$(echo "$PY_QR" | head -n -1)
test_route "Python QR GET /qr/1" "200" "$PY_QR_STATUS" "$PY_QR_BODY"

PY_ESTADO=$(curl -s -w "\n%{http_code}" "${PYTHON_URL}/estado/1" 2>/dev/null)
PY_ESTADO_STATUS=$(echo "$PY_ESTADO" | tail -1)
PY_ESTADO_BODY=$(echo "$PY_ESTADO" | head -n -1)
test_route "Python estado GET /estado/1" "200" "$PY_ESTADO_STATUS" "$PY_ESTADO_BODY"

echo ""

# ─── ROTAS WEB PAINEL (browser autenticado) ─────────────

echo -e "${YELLOW}--- Web Painel (autenticado via cookie) ---${NC}"

COOKIE_JAR=$(mktemp /tmp/cookies_XXXXXX.txt)

# Login web para obter sessao
LOGIN_PAGE=$(curl -s -c "$COOKIE_JAR" "${BASE_URL}/login" 2>/dev/null)
CSRF_TOKEN=$(echo "$LOGIN_PAGE" | python3 -c "
import sys, re
html = sys.stdin.read()
m = re.search(r'name=\"_token\"[^>]*value=\"([^\"]+)\"', html)
if not m:
    m = re.search(r'content=\"([^\"]+)\"[^>]*name=\"csrf-token\"', html)
print(m.group(1) if m else '')
" 2>/dev/null || echo "")

curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -o /dev/null -X POST "${BASE_URL}/login" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -d "_token=${CSRF_TOKEN}&email=${EMAIL}&password=${PASSWORD}" \
    -L 2>/dev/null

WEB_DASH=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" "${BASE_URL}/painel" 2>/dev/null)
test_route "Web painel GET /painel" "200" "$WEB_DASH" ""

WEB_PROD=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" "${BASE_URL}/painel/produtos" 2>/dev/null)
test_route "Web painel/produtos GET" "200" "$WEB_PROD" ""

WEB_CAT=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" "${BASE_URL}/painel/categorias" 2>/dev/null)
test_route "Web painel/categorias GET" "200" "$WEB_CAT" ""

WEB_VEND=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" "${BASE_URL}/painel/vendedores" 2>/dev/null)
test_route "Web painel/vendedores GET" "200" "$WEB_VEND" ""

WEB_ENC=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" "${BASE_URL}/painel/encomendas" 2>/dev/null)
test_route "Web painel/encomendas GET" "200" "$WEB_ENC" ""

WEB_WA=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" "${BASE_URL}/painel/whatsapp" 2>/dev/null)
test_route "Web painel/whatsapp GET" "200" "$WEB_WA" ""

WEB_DEF=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" "${BASE_URL}/painel/definicoes" 2>/dev/null)
test_route "Web painel/definicoes GET" "200" "$WEB_DEF" ""

rm -f "$COOKIE_JAR"

echo ""

# ─── RESULTADOS ─────────────────────────────────────────

echo "============================================"
TOTAL=$((PASS + FAIL))
if [ "$FAIL" -eq 0 ]; then
    echo -e "${GREEN}RESULTADOS: ${PASS} passou | ${FAIL} falhou (${TOTAL} total)${NC}"
else
    echo -e "${RED}RESULTADOS: ${PASS} passou | ${FAIL} falhou (${TOTAL} total)${NC}"
fi
echo "============================================"

exit $FAIL
