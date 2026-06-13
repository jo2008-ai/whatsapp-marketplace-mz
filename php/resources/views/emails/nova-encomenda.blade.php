<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;">
    <div style="max-width:600px;margin:0 auto;background:#fff;">
        <div style="background:#10b981;padding:24px;text-align:center;">
            <h1 style="color:#fff;margin:0;font-size:20px;">🔔 Nova Encomenda!</h1>
        </div>

        <div style="padding:32px;">
            <p style="color:#4b5563;">Recebeste uma nova encomenda na <strong>{{ $encomenda->tenant->nome_loja }}</strong>:</p>

            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:20px;margin:24px 0;">
                <table style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="padding:8px 0;color:#6b7280;font-size:14px;">Cliente:</td>
                        <td style="padding:8px 0;color:#1f2937;font-weight:bold;">{{ $encomenda->nome_cliente ?? $encomenda->numero_cliente }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#6b7280;font-size:14px;">WhatsApp:</td>
                        <td style="padding:8px 0;color:#1f2937;">{{ $encomenda->numero_cliente }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#6b7280;font-size:14px;">Produto:</td>
                        <td style="padding:8px 0;color:#1f2937;font-weight:bold;">{{ $encomenda->produto->nome ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#6b7280;font-size:14px;">Total:</td>
                        <td style="padding:8px 0;color:#10b981;font-weight:bold;font-size:18px;">{{ number_format($encomenda->preco_total, 2) }} MZN</td>
                    </tr>
                </table>
            </div>

            <div style="text-align:center;margin:24px 0;">
                <a href="{{ config('app.url') }}/painel/encomendas"
                   style="background:#2563EB;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;">
                    Ver Encomendas
                </a>
            </div>
        </div>

        <div style="background:#f9fafb;padding:16px;text-align:center;border-top:1px solid #e5e7eb;">
            <p style="color:#9ca3af;font-size:12px;margin:0;">{{ $encomenda->created_at->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
