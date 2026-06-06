<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;">
    <div style="max-width:600px;margin:0 auto;background:#fff;">
        <!-- Header -->
        <div style="background:#dc2626;padding:32px;text-align:center;">
            <h1 style="color:#fff;margin:0;font-size:24px;">⚠️ WhatsApp Desconectado</h1>
        </div>

        <!-- Body -->
        <div style="padding:32px;">
            <h2 style="color:#1f2937;margin-top:0;">Olá {{ $instancia->tenant->users->first()->name ?? 'Dono' }}!</h2>

            <p style="color:#4b5563;line-height:1.6;">
                O número WhatsApp da loja <strong>{{ $tenant->nome_loja }}</strong> foi desconectado.
            </p>

            <div style="background:#fef2f2;border-left:4px solid #dc2626;padding:16px;margin:24px 0;border-radius:0 8px 8px 0;">
                <p style="margin:0;color:#991b1b;">
                    <strong>Número:</strong> {{ $instancia->numero_whatsapp ?? 'N/A' }}
                </p>
                <p style="margin:8px 0 0;color:#991b1b;">
                    <strong>Instância:</strong> {{ $instancia->evolution_instance_name }}
                </p>
            </div>

            <p style="color:#4b5563;line-height:1.6;">
                Enquanto estiver desconectado, o <strong>bot WhatsApp está parado</strong>.
                Os clientes não conseguem ver produtos nem fazer encomendas.
            </p>

            <h3 style="color:#1f2937;">Como reconectar:</h3>
            <ol style="color:#4b5563;line-height:1.8;">
                <li>Abre o <a href="{{ $linkReconnect }}" style="color:#2563EB;">painel de WhatsApp</a></li>
                <li>Clica em "Ligar novo número" ou "Reconectar"</li>
                <li>Abre WhatsApp → Definições → Dispositivos ligados</li>
                <li>Clica "Ligar dispositivo" e escaneia o QR code</li>
            </ol>

            <div style="text-align:center;margin:32px 0;">
                <a href="{{ $linkReconnect }}"
                   style="background:#2563EB;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:bold;">
                    Reconectar WhatsApp
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div style="background:#f9fafb;padding:24px;text-align:center;border-top:1px solid #e5e7eb;">
            <p style="color:#9ca3af;font-size:12px;margin:0;">
                WhatsApp Marketplace SaaS — {{ config('app.url') }}
            </p>
        </div>
    </div>
</body>
</html>
