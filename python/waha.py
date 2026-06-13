import requests
import os
from dotenv import load_dotenv

load_dotenv()

WAHA_URL = os.getenv('WAHA_URL', 'http://localhost:3000')
WAHA_API_KEY = os.getenv('WAHA_API_KEY', '')
WAHA_SESSION = os.getenv('WAHA_SESSION', 'default')

HEADERS = {
    'X-Api-Key': WAHA_API_KEY,
    'Content-Type': 'application/json',
}


def enviar_texto(numero: str, texto: str) -> dict:
    """Envia uma mensagem de texto via WAHA."""
    try:
        response = requests.post(
            f"{WAHA_URL}/api/sendText",
            json={
                'session': WAHA_SESSION,
                'chatId': f"{numero}@c.us",
                'text': texto,
            },
            headers=HEADERS,
            timeout=10,
        )
        return response.json() if response.ok else {'error': response.text, 'status': response.status_code}
    except requests.RequestException as e:
        return {'error': str(e)}


def enviar_imagem(numero: str, url_imagem: str, caption: str = '') -> dict:
    """Envia uma imagem via WAHA."""
    try:
        response = requests.post(
            f"{WAHA_URL}/api/sendImage",
            json={
                'session': WAHA_SESSION,
                'chatId': f"{numero}@c.us",
                'file': {'url': url_imagem},
                'caption': caption,
            },
            headers=HEADERS,
            timeout=15,
        )
        return response.json() if response.ok else {'error': response.text, 'status': response.status_code}
    except requests.RequestException as e:
        return {'error': str(e)}


def obter_estado() -> dict:
    """Verifica o estado da sessao WAHA."""
    try:
        response = requests.get(
            f"{WAHA_URL}/api/sessions",
            headers=HEADERS,
            timeout=10,
        )
        if response.ok:
            sessions = response.json()
            for session in sessions:
                if session.get('name') == WAHA_SESSION:
                    state = session.get('status', 'unknown')
                    return {'estado': 'conectada' if state == 'WORKING' else 'desconectada', 'state': state}
            return {'estado': 'desconectada', 'state': 'not_found'}
        return {'estado': 'erro', 'error': response.text}
    except requests.RequestException as e:
        return {'estado': 'erro', 'error': str(e)}


def obter_qr_code() -> dict:
    """Obtem o QR code da sessao WAHA."""
    try:
        estado = obter_estado()
        if estado.get('estado') == 'conectada':
            return {'estado': 'conectada', 'qr': None}

        qr_resp = requests.get(
            f"{WAHA_URL}/api/default/auth/qr",
            headers=HEADERS,
            timeout=10,
        )

        if qr_resp.ok:
            qr_data = qr_resp.json()
            base64_qr = qr_data.get('base64', '')
            if base64_qr:
                return {'estado': 'aguarda_qr', 'qr': base64_qr}

        return {'estado': 'aguarda_qr', 'qr': None}

    except requests.RequestException as e:
        return {'estado': 'erro', 'qr': None, 'error': str(e)}
