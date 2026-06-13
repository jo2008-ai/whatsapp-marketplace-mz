import os
import time
import logging
import threading
from collections import defaultdict
from flask import Flask, request, jsonify
from dotenv import load_dotenv
import requests

from evolution import criar_instancia, enviar_mensagem, enviar_media, obter_qr_code

load_dotenv()

app = Flask(__name__)

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

PHP_API_URL = os.getenv('PHP_API_URL', 'http://localhost:8000/api/mensagem')
APP_URL = os.getenv('APP_URL', 'http://localhost:8000')


class RateLimiter:
    """Rate limiter em memória com sliding window por número de telefone."""

    def __init__(self, max_requests: int = 30, window_seconds: int = 60):
        self.max_requests = max_requests
        self.window_seconds = window_seconds
        self._requests: dict[str, list[float]] = defaultdict(list)
        self._lock = threading.Lock()

    def is_allowed(self, key: str) -> bool:
        now = time.time()
        cutoff = now - self.window_seconds

        with self._lock:
            self._requests[key] = [
                t for t in self._requests[key] if t > cutoff
            ]

            if len(self._requests[key]) >= self.max_requests:
                return False

            self._requests[key].append(now)
            return True

    def remaining(self, key: str) -> int:
        now = time.time()
        cutoff = now - self.window_seconds

        with self._lock:
            self._requests[key] = [
                t for t in self._requests[key] if t > cutoff
            ]
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
    """Recebe eventos da Evolution API com rate limiting por número."""
    try:
        data = request.json
        if not data:
            return jsonify({'error': 'no data'}), 400

        event = data.get('event')
        instance_name = data.get('instance')

        logger.info(f"Evento: {event} | Instância: {instance_name}")

        if event != 'messages.upsert':
            return jsonify({'status': 'ignored', 'event': event})

        msg_data = data.get('data', {})
        key = msg_data.get('key', {})

        if key.get('fromMe', False):
            return jsonify({'status': 'ignored', 'reason': 'fromMe'})

        remote_jid = key.get('remoteJid', '')
        sender = remote_jid.replace('@s.whatsapp.net', '').replace('@lid', '')

        rate_key = f"webhook:{instance_name}:{sender}"
        if not webhook_limiter.is_allowed(rate_key):
            remaining = webhook_limiter.remaining(rate_key)
            logger.warning(
                f"Rate limit excedido para {sender} na instância {instance_name}. "
                f"Restam {remaining} requests."
            )
            return jsonify({
                'status': 'rate_limited',
                'retry_after': webhook_limiter.retry_after(rate_key),
            }), 429

        push_name = msg_data.get('pushName', '')

        message_content = msg_data.get('message', {})
        corpo = ''
        if 'conversation' in message_content:
            corpo = message_content['conversation']
        elif 'extendedTextMessage' in message_content:
            corpo = message_content['extendedTextMessage'].get('text', '')
        elif 'buttonsResponseMessage' in message_content:
            corpo = message_content['buttonsResponseMessage'].get(
                'selectedButtonId', ''
            )
        elif 'listResponseMessage' in message_content:
            corpo = (
                message_content['listResponseMessage']
                .get('singleSelectReply', {})
                .get('selectedRowId', '')
            )

        if not corpo:
            return jsonify({'status': 'ignored', 'reason': 'empty_body'})

        is_grupo = '@g.us' in remote_jid
        grupo_id = remote_jid if is_grupo else None

        logger.info(f"Mensagem de {sender} ({push_name}): {corpo}")

        php_response = requests.post(
            PHP_API_URL,
            json={
                'tenant_id': _resolver_tenant_id(instance_name),
                'instance_name': instance_name,
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
                    enviar_detalhe_produto(instance_name, sender, imagens, resposta)
                elif resposta:
                    enviar_mensagem(instance_name, sender, resposta)

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


def enviar_detalhe_produto(instance_name: str, numero: str, imagens: list, texto: str):
    """Envia fotos do produto (frente e trás) seguidas do texto com detalhes."""
    for imagem_url in imagens:
        if not imagem_url:
            continue

        if not imagem_url.startswith('http'):
            imagem_url = f"{APP_URL}{imagem_url}"

        resultado = enviar_media(instance_name, numero, imagem_url)
        if 'error' in resultado:
            logger.error(f"Erro ao enviar imagem {imagem_url}: {resultado}")
        else:
            logger.info(f"Imagem enviada a {numero}: {imagem_url}")

        time.sleep(0.5)

    if texto:
        enviar_mensagem(instance_name, numero, texto)


@app.route('/enviar', methods=['POST'])
def enviar():
    """Envia mensagem a pedido do PHP (notificações de encomenda, etc)."""
    if request.remote_addr not in ('127.0.0.1', '::1', 'localhost'):
        return jsonify({'error': 'forbidden'}), 403

    data = request.json
    numero = data.get('numero')
    mensagem = data.get('mensagem')
    instance_name = data.get('instance_name')

    if not all([numero, mensagem, instance_name]):
        return jsonify({'error': 'missing parameters'}), 400

    rate_key = f"enviar:{instance_name}:{numero}"
    if not api_limiter.is_allowed(rate_key):
        return jsonify({
            'error': 'rate_limited',
            'retry_after': api_limiter.retry_after(rate_key),
        }), 429

    resultado = enviar_mensagem(instance_name, numero, mensagem)

    if 'error' in resultado:
        return jsonify(resultado), 500

    return jsonify({'status': 'sent', 'result': resultado})


@app.route('/conectar-instancia', methods=['POST'])
def conectar_instancia():
    """Cria uma nova instância no Evolution API."""
    if request.remote_addr not in ('127.0.0.1', '::1', 'localhost'):
        return jsonify({'error': 'forbidden'}), 403

    data = request.json
    instance_name = data.get('instance_name')
    tenant_id = data.get('tenant_id')

    if not instance_name:
        return jsonify({'error': 'instance_name required'}), 400

    resultado = criar_instancia(instance_name)

    if 'error' in resultado:
        logger.error(f"Erro ao criar instância: {resultado}")
        return jsonify(resultado), 500

    logger.info(f"Instância criada: {instance_name} para tenant {tenant_id}")
    return jsonify({
        'status': 'created',
        'instance': instance_name,
        'result': resultado,
    })


@app.route('/qr/<instance_name>', methods=['GET'])
def qr(instance_name):
    """Retorna o QR code de uma instância."""
    resultado = obter_qr_code(instance_name)
    return jsonify(resultado)


def _resolver_tenant_id(instance_name: str) -> int:
    """Extrai o tenant_id do nome da instância (formato: loja_{id}_{timestamp})."""
    try:
        parts = instance_name.split('_')
        if len(parts) >= 2 and parts[0] == 'loja':
            return int(parts[1])
    except (ValueError, IndexError):
        pass
    return 0


if __name__ == '__main__':
    port = int(os.getenv('PORT', 5000))
    app.run(
        host='0.0.0.0',
        port=port,
        debug=os.getenv('FLASK_ENV') == 'development',
    )
