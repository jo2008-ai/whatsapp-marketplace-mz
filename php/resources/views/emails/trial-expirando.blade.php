<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;">
    <div style="max-width:600px;margin:0 auto;background:#fff;">
        <div style="background:#f59e0b;padding:24px;text-align:center;">
            <h1 style="color:#fff;margin:0;font-size:20px;">⚠️ Trial a expirar!</h1>
        </div>

        <div style="padding:32px;">
            <h2 style="color:#1f2937;margin-top:0;">Olá!</h2>

            <p style="color:#4b5563;line-height:1.6;">
                O trial da tua loja <strong>{{ $tenant->nome_loja }}</strong> expira em
                <strong>{{ $diasRestantes }} {{ $diasRestantes === 1 ? 'dia' : 'dias' }}</strong>.
            </p>

            <div style="background:#fffbeb;border-left:4px solid #f59e0b;padding:16px;margin:24px 0;border-radius:0 8px 8px 0;">
                <p style="margin:0;color:#92400e;">
                    Após o fim do trial, a tua loja será suspensa e o bot deixará de responder.
                </p>
            </div>

            <h3 style="color:#1f2937;">Planos disponíveis:</h3>
            <ul style="color:#4b5563;line-height:1.8;">
                <li><strong>Basic</strong> — 500 MZN/mês (50 produtos)</li>
                <li><strong>Pro</strong> — 1.500 MZN/mês (500 produtos)</li>
                <li><strong>Enterprise</strong> — sob consulta (ilimitado)</li>
            </ul>

            <div style="text-align:center;margin:32px 0;">
                <a href="{{ config('app.url') }}/painel/definicoes"
                   style="background:#f59e0b;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:bold;">
                    Renovar Subscrição
                </a>
            </div>
        </div>

        <div style="background:#f9fafb;padding:16px;text-align:center;border-top:1px solid #e5e7eb;">
            <p style="color:#9ca3af;font-size:12px;margin:0;">WhatsApp Marketplace SaaS</p>
        </div>
    </div>
</body>
</html>
