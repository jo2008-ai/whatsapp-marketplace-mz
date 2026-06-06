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
            <h1 style="color:#fff;margin:0;font-size:24px;">Trial Expirado</h1>
        </div>

        <!-- Body -->
        <div style="padding:32px;">
            <h2 style="color:#1f2937;margin-top:0;">Olá!</h2>

            <p style="color:#4b5563;line-height:1.6;">
                O trial gratuito da loja <strong>{{ $tenant->nome_loja }}</strong> expirou.
            </p>

            <div style="background:#fef2f2;border-left:4px solid #dc2626;padding:16px;margin:24px 0;border-radius:0 8px 8px 0;">
                <p style="margin:0;color:#991b1b;">
                    <strong>O bot WhatsApp está desactivado.</strong>
                </p>
                <p style="margin:8px 0 0;color:#991b1b;">
                    Os clientes não conseguem ver produtos nem fazer encomendas.
                </p>
            </div>

            <p style="color:#4b5563;line-height:1.6;">
                Para reactivar a tua loja, precisas de escolher um plano pago.
            </p>

            <div style="background:#f0fdf4;border-left:4px solid #22c55e;padding:16px;margin:24px 0;border-radius:0 8px 8px 0;">
                <p style="margin:0;color:#166534;"><strong>Planos disponíveis:</strong></p>
                <p style="margin:8px 0 0;color:#15803d;">
                    Basic — 500 MZN/mês (50 produtos, 1 número)<br>
                    Pro — 1.500 MZN/mês (500 produtos, 3 números)<br>
                    Enterprise — 5.000 MZN/mês (ilimitado)
                </p>
            </div>

            <div style="text-align:center;margin:32px 0;">
                <a href="{{ config('app.url') }}/painel"
                   style="background:#2563EB;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:bold;">
                    Activar Minha Loja
                </a>
            </div>

            <p style="color:#9ca3af;font-size:13px;text-align:center;">
                Se precisares de ajuda, responde a este email.
            </p>
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
