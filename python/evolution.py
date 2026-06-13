import requests
import os
from dotenv import load_dotenv

load_dotenv()

EVOLUTION_API_URL = os.getenv('EVOLUTION_API_URL', 'http://localhost:8080')
EVOLUTION_API_KEY = os.getenv('EVOLUTION_API_KEY', '')

HEADERS = {
    'apikey': EVOLUTION_API_KEY,
    'Content-Type': 'application/json',
}


def criar_instancia(instance_name: str) -> dict:
    """Cria uma nova instância no Evolution API."""
    try:
        response = requests.post(
            f"{EVOLUTION_API_URL}/instance/create",
            json={
                'instanceName': instance_name,
                'integration': 'WHATSAPP-BAILEYS',
                'qrcode': True,
            },
            headers=HEADERS,
            timeout=15,
        )
        return response.json() if response.ok else {'error': response.text, 'status': response.status_code}
    except requests.RequestException as e:
        return {'error': str(e)}


def enviar_mensagem(instance_name: str, numero: str, mensagem: str) -> dict:
    """Envia uma mensagem de texto via Evolution API."""
    try:
        response = requests.post(
            f"{EVOLUTION_API_URL}/message/sendText/{instance_name}",
            json={
                'number': numero,
                'text': mensagem,
            },
            headers=HEADERS,
            timeout=10,
        )
        return response.json() if response.ok else {'error': response.text, 'status': response.status_code}
    except requests.RequestException as e:
        return {'error': str(e)}


def enviar_media(instance_name: str, numero: str, media_url: str, caption: str = '') -> dict:
    """Envia uma imagem via Evolution API (sendMedia)."""
    try:
        response = requests.post(
            f"{EVOLUTION_API_URL}/message/sendMedia/{instance_name}",
            json={
                'number': numero,
                'mediatype': 'image',
                'media': media_url,
                'caption': caption,
            },
            headers=HEADERS,
            timeout=15,
        )
        return response.json() if response.ok else {'error': response.text, 'status': response.status_code}
    except requests.RequestException as e:
        return {'error': str(e)}


def obter_qr_code(instance_name: str) -> dict:
    """Obtém o QR code de uma instância."""
    try:
        estado_resp = requests.get(
            f"{EVOLUTION_API_URL}/instance/connectionState/{instance_name}",
            headers=HEADERS,
            timeout=10,
        )

        if estado_resp.ok:
            estado_data = estado_resp.json()
            state = estado_data.get('instance', {}).get('state', 'unknown')

            if state == 'open':
                return {'estado': 'conectada', 'qr': None}

        qr_resp = requests.get(
            f"{EVOLUTION_API_URL}/instance/connect/{instance_name}",
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


def estado_instancia(instance_name: str) -> dict:
    """Verifica o estado de uma instância."""
    try:
        response = requests.get(
            f"{EVOLUTION_API_URL}/instance/connectionState/{instance_name}",
            headers=HEADERS,
            timeout=10,
        )
        if response.ok:
            data = response.json()
            state = data.get('instance', {}).get('state', 'unknown')
            return {'estado': 'conectada' if state == 'open' else 'desconectada', 'state': state}
        return {'estado': 'erro', 'error': response.text}
    except requests.RequestException as e:
        return {'estado': 'erro', 'error': str(e)}
