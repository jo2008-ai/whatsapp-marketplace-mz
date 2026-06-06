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
            <h1 style="color:#fff;margin:0;font-size:24px;">Pedido de Upgrade</h1>
        </div>

        <!-- Body -->
        <div style="padding:32px;">
            <h2 style="color:#1f2937;margin-top:0;">Novo pedido de upgrade</h2>

            <p style="color:#4b5563;line-height:1.6;">
                A loja <strong>{{ $tenant->nome_loja }}</strong> solicitou um upgrade de plano.
            </p>

            <div style="background:#f0f9ff;border-left:4px solid #2563EB;padding:16px;margin:24px 0;border-radius:0 8px 8px 0;">
                <p style="margin:0;color:#1e40af;">
                    <strong>Plano actual:</strong> {{ ucfirst($tenant->plano) }}
                </p>
                <p style="margin:4px 0 0;color:#1e40af;">
                    <strong>Plano pretendido:</strong> {{ ucfirst($this->plano) }}
                </p>
                <p style="margin:4px 0 0;color:#1e40af;">
                    <strong>Referência M-Pesa:</strong> {{ $this->referenciaPagamento }}
                </p>
                <p style="margin:4px 0 0;color:#1e40af;">
                    <strong>Email do dono:</strong> {{ $tenant->email_dono }}
                </p>
            </div>

            <p style="color:#4b5563;line-height:1.6;">
                Para activar o upgrade, confirma o pagamento no painel de super admin.
            </p>

            <div style="text-align:center;margin:32px 0;">
                <a href="{{ config('app.url') }}/super/lojas/{{ $tenant->id }}"
                   style="background:#2563EB;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:bold;">
                    Ver Loja no Admin
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
