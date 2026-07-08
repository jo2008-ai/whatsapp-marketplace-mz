import os
import logging
import requests
from dotenv import load_dotenv

load_dotenv()

WAHA_API_KEY = os.getenv('WAHA_API_KEY', '')
WAHA_URL = os.getenv('WAHA_URL', '').rstrip('/')
WAHA_SESSION = os.getenv('WAHA_SESSION', 'default')

logger = logging.getLogger(__name__)


def _headers() -> dict:
    return {
        'X-Api-Key': WAHA_API_KEY,
        'Content-Type': 'application/json',
    }


def _session_name(tenant_id: int) -> str:
    return f"loja-{tenant_id}"


def enviar_texto(tenant_id: int, numero: str, texto: str) -> dict:
    try:
        url = f"{WAHA_URL}/api/sendText"
        response = requests.post(
            url,
            json={
                'session': _session_name(tenant_id),
                'chatId': f"{numero}@c.us",
                'text': texto,
            },
            headers=_headers(),
            timeout=10,
        )
        return response.json() if response.ok else {
            'error': response.text,
            'status': response.status_code,
        }
    except requests.RequestException as e:
        return {'error': str(e)}


def enviar_imagem(tenant_id: int, numero: str, url_imagem: str, caption: str = '') -> dict:
    try:
        url = f"{WAHA_URL}/api/sendImage"
        response = requests.post(
            url,
            json={
                'session': _session_name(tenant_id),
                'chatId': f"{numero}@c.us",
                'file': {'url': url_imagem},
                'caption': caption,
            },
            headers=_headers(),
            timeout=15,
        )
        return response.json() if response.ok else {
            'error': response.text,
            'status': response.status_code,
        }
    except requests.RequestException as e:
        return {'error': str(e)}


def obter_estado(tenant_id: int) -> dict:
    try:
        session_name = _session_name(tenant_id)
        url = f"{WAHA_URL}/api/sessions/{session_name}"
        response = requests.get(url, headers=_headers(), timeout=10)
        if response.ok:
            data = response.json()
            state = data.get('status', 'unknown')
            return {
                'estado': 'conectada' if state == 'WORKING' else 'desconectada',
                'state': state,
            }
        if response.status_code == 404:
            return {'estado': 'desconectada', 'state': 'not_found'}
        return {'estado': 'erro', 'error': response.text}
    except requests.RequestException as e:
        return {'estado': 'erro', 'error': str(e)}


def obter_qr_code(tenant_id: int) -> dict:
    try:
        estado = obter_estado(tenant_id)
        if estado.get('estado') == 'conectada':
            return {'estado': 'conectada', 'qr': None}

        session_name = _session_name(tenant_id)
        url = f"{WAHA_URL}/api/{session_name}/auth/qr"
        qr_resp = requests.get(url, headers=_headers(), timeout=10)
        if qr_resp.ok:
            qr_data = qr_resp.json()
            base64_qr = qr_data.get('base64', '')
            if base64_qr:
                return {'estado': 'aguarda_qr', 'qr': base64_qr}
        return {'estado': 'aguarda_qr', 'qr': None}
    except requests.RequestException as e:
        return {'estado': 'erro', 'qr': None, 'error': str(e)}
