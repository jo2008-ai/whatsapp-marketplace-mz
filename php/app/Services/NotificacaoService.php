<?php

namespace App\Services;

use App\Models\Encomenda;
use Illuminate\Support\Facades\Log;

class NotificacaoService
{
    private WahaService $wahaService;

    public function __construct(WahaService $wahaService)
    {
        $this->wahaService = $wahaService;
    }

    public function notificarVendedor(Encomenda $encomenda): void
    {
        $vendedor = $encomenda->vendedor;
        $tenant = $encomenda->tenant;

        if (! $vendedor || ! $tenant) {
            return;
        }

        $instancia = $tenant->instancias()
            ->where('estado', 'conectada')
            ->first();

        if (! $instancia) {
            Log::warning("Tenant {$tenant->id} sem instância WhatsApp conectada para notificação");

            return;
        }

        $mensagem = "🔔 *Nova Encomenda!*\n"
                  ."👤 Cliente: {$encomenda->nome_cliente}\n"
                  ."📱 Número: {$encomenda->numero_cliente}\n"
                  ."🏷️ Produto: {$encomenda->produto->nome}";

        $variantePartes = array_filter([
            $encomenda->cor_escolhida ? "Cor: {$encomenda->cor_escolhida}" : null,
            $encomenda->tamanho_escolhido ? "Tamanho: {$encomenda->tamanho_escolhido}" : null,
        ]);

        if (! empty($variantePartes)) {
            $mensagem .= "\n🎨 ".implode(' · ', $variantePartes);
        }

        $mensagem .= "\n💰 Total: {$encomenda->preco_total} MZN\n"
                   .'🕐 '.now()->format('d/m/Y H:i');

        try {
            $this->wahaService->enviarMensagem($tenant->id, $vendedor->numero_whatsapp, $mensagem, $instancia->waha_url);
        } catch (\Exception $e) {
            Log::error('Erro ao notificar vendedor: '.$e->getMessage());
        }
    }
}
