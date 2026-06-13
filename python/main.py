import os
import time
import logging
import threading
from collections import defaultdict
from flask import Flask, request, jsonify
from dotenv import load_dotenv
import requests

from waha import enviar_texto, enviar_imagem, obter_qr_code

load_dotenv()

app = Flask(__name__)

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

PHP_API_URL = os.getenv('PHP_API_URL', 'http://localhost:8000/api/mensagem')
APP_URL = os.getenv('APP_URL', 'http://localhost:8000')
TENANT_ID_DEFAULT = int(os.getenv('TENANT_ID_DEFAULT', '1'))


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


@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'ok', 'service': 'whatsapp-python'})


@app.route('/webhook', methods=['POST'])
def webhook():
    """Recebe eventos da WAHA com rate limiting por numero."""
    try:
        data = request.json
        if not data:
            return jsonify({'error': 'no data'}), 400

        event = data.get('event')
        session = data.get('session', 'default')

        logger.info(f"Evento: {event} | Session: {session}")

        if event == 'session.status':
            session_data = data.get('payload', {})
            state = session_data.get('status', 'unknown')
            logger.info(f"Sessao {session}: {state}")
            return jsonify({'status': 'ignored', 'event': event})

        if event != 'message':
            return jsonify({'status': 'ignored', 'event': event})

        payload = data.get('payload', {})

        if payload.get('fromMe', False):
            return jsonify({'status': 'ignored', 'reason': 'fromMe'})

        sender_full = payload.get('from', '')
        sender = sender_full.replace('@c.us', '').replace('@lid', '')

        rate_key = f"webhook:{session}:{sender}"
        if not webhook_limiter.is_allowed(rate_key):
            remaining = webhook_limiter.remaining(rate_key)
            logger.warning(
                f"Rate limit excedido para {sender}. "
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

        logger.info(f"Mensagem de {sender} ({push_name}): {corpo}")

        php_response = requests.post(
            PHP_API_URL,
            json={
                'tenant_id': TENANT_ID_DEFAULT,
                'instance_name': 'default',
                'numero': sender,
                'mensagem': corpo,
                'nome': push_name,
                'is_grupo': is_grupo,
                'grupo_id': grupo_id,
            },
            timeout=15,
        )

        if php_response.ok:
            resultado = php_response.json()
            enviar = resultado.get('enviar', False)

            if enviar:
                resposta = resultado.get('resposta', '')
                imagens = resultado.get('imagens', [])

                if imagens:
                    enviar_detalhe_produto(sender, imagens, resposta)
                elif resposta:
                    enviar_texto(sender, resposta)

                logger.info(f"Resposta enviada a {sender}")
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


def enviar_detalhe_produto(numero: str, imagens: list, texto: str):
    """Envia fotos do produto (frente e tras) seguidas do texto com detalhes."""
    for imagem_url in imagens:
        if not imagem_url:
            continue
        if not imagem_url.startswith('http'):
            imagem_url = f"{APP_URL}{imagem_url}"
        resultado = enviar_imagem(numero, imagem_url)
        if 'error' in resultado:
            logger.error(f"Erro ao enviar imagem {imagem_url}: {resultado}")
        else:
            logger.info(f"Imagem enviada a {numero}: {imagem_url}")
        time.sleep(0.5)
    if texto:
        enviar_texto(numero, texto)


@app.route('/enviar', methods=['POST'])
def enviar():
    """Envia mensagem a pedido do PHP (notificacoes de encomenda, etc)."""
    if request.remote_addr not in ('127.0.0.1', '::1', 'localhost'):
        return jsonify({'error': 'forbidden'}), 403

    data = request.json
    numero = data.get('numero')
    mensagem = data.get('mensagem')

    if not all([numero, mensagem]):
        return jsonify({'error': 'missing parameters'}), 400

    rate_key = f"enviar:{numero}"
    if not api_limiter.is_allowed(rate_key):
        return jsonify({
            'error': 'rate_limited',
            'retry_after': api_limiter.retry_after(rate_key),
        }), 429

    resultado = enviar_texto(numero, mensagem)

    if 'error' in resultado:
        return jsonify(resultado), 500

    return jsonify({'status': 'sent', 'result': resultado})


@app.route('/qr', methods=['GET'])
def qr():
    """Retorna o QR code da sessao WAHA."""
    resultado = obter_qr_code()
    return jsonify(resultado)


@app.route('/estado', methods=['GET'])
def estado():
    """Retorna o estado da sessao WAHA."""
    from waha import obter_estado
    resultado = obter_estado()
    return jsonify(resultado)


if __name__ == '__main__':
    port = int(os.getenv('PORT', 5000))
    app.run(
        host='0.0.0.0',
        port=port,
        debug=os.getenv('FLASK_ENV') == 'development',
    )
