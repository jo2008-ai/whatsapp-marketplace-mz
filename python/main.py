import os
import time
import hmac
import hashlib
import json
import logging
import threading
from collections import defaultdict
from flask import Flask, request, jsonify, render_template, redirect, url_for, flash
from dotenv import load_dotenv
import requests

from waha import enviar_texto, enviar_imagem, obter_qr_code, obter_estado

load_dotenv()

app = Flask(__name__)
app.secret_key = os.getenv('SECRET_KEY', 'marketplace_secret_2026')

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

PHP_API_URL = os.getenv('PHP_API_URL', 'https://whatsapp-marketplace-mz.onrender.com/api/mensagem')
APP_URL = os.getenv('APP_URL', 'http://localhost:8000')
TENANT_ID_DEFAULT = int(os.getenv('TENANT_ID_DEFAULT', '1'))
TYPEBOT_WEBHOOK_URL = os.getenv('TYPEBOT_WEBHOOK_URL', 'http://php:8000/api/typebot/webhook')
WEBHOOK_SECRET = os.getenv('WEBHOOK_SECRET', '')


class RateLimiter:
    """Rate limiter em memoria com sliding window por numero de telefone."""

    def __init__(self, max_requests: int = 30, window_seconds: int = 60):
        self.max_requests = max_requests
        self.window_seconds = window_seconds
        self._requests: dict[str, list[float]] = defaultdict(list)
        self._lock = threading.Lock()

    def is_allowed(self, key: str) -> bool:
        now = time.time()
        cutoff = now - self.window_seconds
        with self._lock:
            self._requests[key] = [t for t in self._requests[key] if t > cutoff]
            if len(self._requests[key]) >= self.max_requests:
                return False
            self._requests[key].append(now)
            return True

    def remaining(self, key: str) -> int:
        now = time.time()
        cutoff = now - self.window_seconds
        with self._lock:
            self._requests[key] = [t for t in self._requests[key] if t > cutoff]
            return max(0, self.max_requests - len(self._requests[key]))

    def retry_after(self, key: str) -> int:
        now = time.time()
        with self._lock:
            if self._requests[key]:
                oldest = self._requests[key][0]
                return max(1, int(self.window_seconds - (now - oldest)))
        return self.window_seconds


webhook_limiter = RateLimiter(max_requests=30, window_seconds=60)
api_limiter = RateLimiter(max_requests=100, window_seconds=60)


def _compute_signature(body: bytes) -> str:
    """Computa HMAC SHA-256 da requisicao para o PHP."""
    if not WEBHOOK_SECRET:
        return ''
    return 'sha256=' + hmac.new(
        WEBHOOK_SECRET.encode(), body, hashlib.sha256
    ).hexdigest()


def _php_headers(body: bytes) -> dict:
    """Headers com assinatura HMAC para chamar o PHP."""
    headers = {'Content-Type': 'application/json'}
    sig = _compute_signature(body)
    if sig:
        headers['X-Hub-Signature-256'] = sig
    return headers


@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'ok', 'service': 'whatsapp-python'})


@app.route('/webhook/<int:tenant_id>', methods=['POST'])
def webhook(tenant_id: int):
    """Recebe eventos WAHA com routing por tenant_id."""
    try:
        data = request.json
        if not data:
            return jsonify({'error': 'no data'}), 400

        event = data.get('event')
        session = data.get('session', 'default')

        logger.info(f"Tenant {tenant_id} | Evento: {event} | Session: {session}")

        if event == 'session.status':
            session_data = data.get('payload', {})
            state = session_data.get('status', 'unknown')
            logger.info(f"Tenant {tenant_id} | Sessao {session}: {state}")
            return jsonify({'status': 'ignored', 'event': event})

        if event != 'message':
            return jsonify({'status': 'ignored', 'event': event})

        payload = data.get('payload', {})

        if payload.get('fromMe', False):
            return jsonify({'status': 'ignored', 'reason': 'fromMe'})

        sender_full = payload.get('from', '')
        sender = sender_full.replace('@c.us', '').replace('@lid', '')

        rate_key = f"webhook:{tenant_id}:{sender}"
        if not webhook_limiter.is_allowed(rate_key):
            remaining = webhook_limiter.remaining(rate_key)
            logger.warning(
                f"Rate limit excedido para {sender} (tenant {tenant_id}). "
                f"Restam {remaining} requests."
            )
            return jsonify({
                'status': 'rate_limited',
                'retry_after': webhook_limiter.retry_after(rate_key),
            }), 429

        push_name = payload.get('_data', {}).get('notifyName', '')
        corpo = payload.get('body', '')

        if not corpo:
            return jsonify({'status': 'ignored', 'reason': 'empty_body'})

        is_grupo = '@g.us' in sender_full
        grupo_id = sender_full if is_grupo else None

        logger.info(
            f"Tenant {tenant_id} | Mensagem de {sender} ({push_name}): {corpo}"
        )

        php_payload = {
            'tenant_id': tenant_id,
            'instance_name': 'default',
            'numero': sender,
            'mensagem': corpo,
            'nome': push_name,
            'is_grupo': is_grupo,
            'grupo_id': grupo_id,
        }
        php_body = json.dumps(php_payload).encode()
        php_response = requests.post(
            PHP_API_URL,
            data=php_body,
            headers=_php_headers(php_body),
            timeout=15,
        )

        if php_response.ok:
            resultado = php_response.json()
            enviar = resultado.get('enviar', False)

            if enviar:
                resposta = resultado.get('resposta', '')
                imagens = resultado.get('imagens', [])

                if imagens:
                    enviar_detalhe_produto(tenant_id, sender, imagens, resposta)
                elif resposta:
                    enviar_texto(tenant_id, sender, resposta)

                logger.info(f"Resposta enviada a {sender} (tenant {tenant_id})")
        else:
            logger.error(
                f"PHP retornou erro: {php_response.status_code} - "
                f"{php_response.text}"
            )

        return jsonify({'status': 'processed'})

    except requests.RequestException as e:
        logger.error(f"Erro ao contactar PHP: {e}")
        return jsonify({'error': 'php_unavailable'}), 503
    except Exception as e:
        logger.error(f"Erro no webhook: {e}")
        return jsonify({'error': str(e)}), 500


def enviar_detalhe_produto(tenant_id: int, numero: str, imagens: list, texto: str):
    """Envia fotos do produto (frente e tras) seguidas do texto com detalhes."""
    for imagem_url in imagens:
        if not imagem_url:
            continue
        if not imagem_url.startswith('http'):
            imagem_url = f"{APP_URL}{imagem_url}"
        resultado = enviar_imagem(tenant_id, numero, imagem_url)
        if 'error' in resultado:
            logger.error(f"Erro ao enviar imagem {imagem_url}: {resultado}")
        else:
            logger.info(f"Imagem enviada a {numero}: {imagem_url}")
        time.sleep(0.5)
    if texto:
        enviar_texto(tenant_id, numero, texto)


@app.route('/enviar', methods=['POST'])
def enviar():
    """Envia mensagem a pedido do PHP (notificacoes de encomenda, etc)."""
    if request.remote_addr not in ('127.0.0.1', '::1', 'localhost'):
        return jsonify({'error': 'forbidden'}), 403

    data = request.json
    tenant_id = data.get('tenant_id', TENANT_ID_DEFAULT)
    numero = data.get('numero')
    mensagem = data.get('mensagem')

    if not all([numero, mensagem]):
        return jsonify({'error': 'missing parameters'}), 400

    rate_key = f"enviar:{tenant_id}:{numero}"
    if not api_limiter.is_allowed(rate_key):
        return jsonify({
            'error': 'rate_limited',
            'retry_after': api_limiter.retry_after(rate_key),
        }), 429

    resultado = enviar_texto(tenant_id, numero, mensagem)

    if 'error' in resultado:
        return jsonify(resultado), 500

    return jsonify({'status': 'sent', 'result': resultado})


@app.route('/qr/<int:tenant_id>', methods=['GET'])
def qr(tenant_id: int):
    """Retorna o QR code da sessao WAHA para um tenant."""
    resultado = obter_qr_code(tenant_id)
    return jsonify(resultado)


@app.route('/typebot/webhook/<int:tenant_id>', methods=['POST'])
def typebot_webhook(tenant_id: int):
    """Recebe respostas do Typebot e envia ao cliente via WAHA."""
    try:
        data = request.json
        if not data:
            return jsonify({'error': 'no data'}), 400

        numero = data.get('numero')
        mensagem = data.get('mensagem')
        session_id = data.get('session_id')

        if not all([numero, mensagem]):
            return jsonify({'error': 'missing parameters'}), 400

        rate_key = f"typebot:{tenant_id}:{numero}"
        if not api_limiter.is_allowed(rate_key):
            return jsonify({
                'error': 'rate_limited',
                'retry_after': api_limiter.retry_after(rate_key),
            }), 429

        resultado = enviar_texto(tenant_id, numero, mensagem)

        if 'error' in resultado:
            return jsonify(resultado), 500

        return jsonify({'status': 'sent', 'result': resultado})

    except Exception as e:
        logger.error(f"Erro no typebot webhook: {e}")
        return jsonify({'error': str(e)}), 500


@app.route('/estado/<int:tenant_id>', methods=['GET'])
def estado(tenant_id: int):
    """Retorna o estado da sessao WAHA para um tenant."""
    resultado = obter_estado(tenant_id)
    return jsonify(resultado)


@app.route('/painel', methods=['GET'])
def painel():
    """Painel de gestao de lojas."""
    lojas = []
    php_api = os.getenv('PHP_API_URL', 'https://whatsapp-marketplace-mz.onrender.com/api/mensagem').replace('/api/mensagem', '')
    logger.info(f"Painel: a buscar lojas em {php_api}/api/painel/lojas")
    try:
        resp = requests.get(f"{php_api}/api/painel/lojas", timeout=10, headers={
            'Accept': 'application/json',
        })
        logger.info(f"Painel: resposta PHP {resp.status_code} - {resp.text[:200]}")
        if resp.ok:
            lojas = resp.json().get('lojas', [])
    except Exception as e:
        logger.error(f"Erro ao buscar lojas: {e}")
    return render_template('painel.html', lojas=lojas)


@app.route('/painel/criar', methods=['POST'])
def criar_loja():
    """Cria uma nova loja via PHP API."""
    nome_loja = request.form.get('nome_loja', '').strip()
    nome_dono = request.form.get('nome_dono', '').strip()
    telefone = request.form.get('telefone', '').strip()

    if not all([nome_loja, nome_dono, telefone]):
        flash('Preencha todos os campos.', 'erro')
        return redirect(url_for('painel'))

    try:
        php_api = os.getenv('PHP_API_URL', 'https://whatsapp-marketplace-mz.onrender.com/api/mensagem').replace('/api/mensagem', '')
        resp = requests.post(f"{php_api}/api/admin/lojas", json={
            'nome_loja': nome_loja,
            'email': telefone + '@loja.local',
            'telefone': telefone,
        }, timeout=15, headers={
            'X-Admin-Key': os.getenv('ADMIN_API_KEY', ''),
        })

        if resp.ok:
            data = resp.json()
            login_code = data.get('credenciais', {}).get('login_code', '?')
            flash(f"Loja criada! Login Code: {login_code}", 'sucesso')
        else:
            erro = resp.json().get('erro', 'Erro desconhecido')
            flash(f'Erro: {erro}', 'erro')
    except Exception as e:
        flash(f'Erro de conexao: {e}', 'erro')

    return redirect(url_for('painel'))


@app.route('/painel/eliminar/<int:tenant_id>', methods=['POST'])
def eliminar_loja(tenant_id: int):
    """Elimina uma loja via PHP API."""
    try:
        php_api = os.getenv('PHP_API_URL', 'https://whatsapp-marketplace-mz.onrender.com/api/mensagem').replace('/api/mensagem', '')
        resp = requests.delete(f"{php_api}/api/admin/lojas/{tenant_id}", timeout=10, headers={
            'X-Admin-Key': os.getenv('ADMIN_API_KEY', ''),
        })

        if resp.ok:
            flash(f'Loja #{tenant_id} eliminada.', 'sucesso')
        else:
            flash('Erro ao eliminar loja.', 'erro')
    except Exception as e:
        flash(f'Erro de conexao: {e}', 'erro')

    return redirect(url_for('painel'))


@app.route('/painel/eliminar-todas', methods=['POST'])
def eliminar_todas():
    """Elimina todas as lojas excepto mozdv."""
    try:
        php_api = os.getenv('PHP_API_URL', 'https://whatsapp-marketplace-mz.onrender.com/api/mensagem').replace('/api/mensagem', '')
        resp = requests.delete(f"{php_api}/api/admin/lojas", timeout=15, headers={
            'X-Admin-Key': os.getenv('ADMIN_API_KEY', ''),
        })

        if resp.ok:
            msg = resp.json().get('mensagem', 'Feito.')
            flash(msg, 'sucesso')
        else:
            flash('Erro ao eliminar lojas.', 'erro')
    except Exception as e:
        flash(f'Erro de conexao: {e}', 'erro')

    return redirect(url_for('painel'))


@app.route('/painel/instancia/<int:tenant_id>', methods=['POST'])
def criar_instancia(tenant_id: int):
    """Cria instancia WAHA para uma loja."""
    try:
        php_api = os.getenv('PHP_API_URL', 'https://whatsapp-marketplace-mz.onrender.com/api/mensagem').replace('/api/mensagem', '')
        resp = requests.post(f"{php_api}/api/admin/lojas/{tenant_id}/instancia", timeout=15, headers={
            'X-Admin-Key': os.getenv('ADMIN_API_KEY', ''),
        })

        if resp.ok:
            msg = resp.json().get('mensagem', 'Feito.')
            flash(msg, 'sucesso')
        else:
            flash('Erro ao criar instancia.', 'erro')
    except Exception as e:
        flash(f'Erro de conexao: {e}', 'erro')

    return redirect(url_for('painel'))


if __name__ == '__main__':
    port = int(os.getenv('PORT', 5000))
    app.run(
        host='0.0.0.0',
        port=port,
        debug=os.getenv('FLASK_ENV') == 'development',
    )
