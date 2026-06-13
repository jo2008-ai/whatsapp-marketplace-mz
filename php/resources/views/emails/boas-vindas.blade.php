<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;">
    <div style="max-width:600px;margin:0 auto;background:#fff;">
        <!-- Header -->
        <div style="background:#2563EB;padding:32px;text-align:center;">
            <h1 style="color:#fff;margin:0;font-size:24px;">WhatsApp Marketplace</h1>
        </div>

        <!-- Body -->
        <div style="padding:32px;">
            <h2 style="color:#1f2937;margin-top:0;">Olá {{ $user->name }}! 👋</h2>

            <p style="color:#4b5563;line-height:1.6;">
                Bem-vindo ao <strong>WhatsApp Marketplace</strong>! A tua loja
                <strong>{{ $tenant->nome_loja }}</strong> foi criada com sucesso.
            </p>

            <div style="background:#f0f9ff;border-left:4px solid #2563EB;padding:16px;margin:24px 0;border-radius:0 8px 8px 0;">
                <p style="margin:0;color:#1e40af;">
                    <strong>Tens {{ $tenant->trial_termina_em ? $tenant->trial_termina_em->diffInDays() : 7 }} dias de trial gratuito!</strong>
                </p>
                <p style="margin:8px 0 0;color:#3b82f6;font-size:14px;">
                    Plano: {{ ucfirst($tenant->plano) }} — Até {{ $tenant->max_produtos }} produtos
                </p>
            </div>

            <h3 style="color:#1f2937;">Primeiros passos:</h3>
            <ol style="color:#4b5563;line-height:1.8;">
                <li>acede ao <a href="{{ config('app.url') }}/painel" style="color:#2563EB;">teu painel</a></li>
                <li>liga o teu número WhatsApp</li>
                <li>adiciona produtos e categorias</li>
                <li>partilha o teu número com clientes!</li>
            </ol>

            <div style="text-align:center;margin:32px 0;">
                <a href="{{ config('app.url') }}/painel"
                   style="background:#2563EB;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:bold;">
                    Abrir Painel
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
