<?php

namespace App\Services;

use App\Models\Encomenda;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificacaoService
{
    public function notificarVendedor(Encomenda $encomenda): void
    {
        $vendedor = $encomenda->vendedor;
        $tenant = $encomenda->tenant;

        if (!$vendedor || !$tenant) {
            return;
        }

        $instancia = $tenant->instancias()
            ->where('estado', 'conectada')
            ->first();

        if (!$instancia) {
            Log::warning("Tenant {$tenant->id} sem instância WhatsApp conectada para notificação");
            return;
        }

        $mensagem = "🔔 *Nova Encomenda!*\n"
                  . "👤 Cliente: {$encomenda->nome_cliente}\n"
                  . "📱 Número: {$encomenda->numero_cliente}\n"
                  . "🏷️ Produto: {$encomenda->produto->nome}";

        $variantePartes = array_filter([
            $encomenda->cor_escolhida ? "Cor: {$encomenda->cor_escolhida}" : null,
            $encomenda->tamanho_escolhido ? "Tamanho: {$encomenda->tamanho_escolhido}" : null,
        ]);

        if (!empty($variantePartes)) {
            $mensagem .= "\n🎨 " . implode(' · ', $variantePartes);
        }

        $mensagem .= "\n💰 Total: {$encomenda->preco_total} MZN\n"
                   . "🕐 " . now()->format('d/m/Y H:i');

        try {
            $response = Http::timeout(10)->post(
                config('services.python.url') . '/enviar',
                [
                    'tenant_id' => $tenant->id,
                    'numero' => $vendedor->numero_whatsapp,
                    'mensagem' => $mensagem,
                    'instance_name' => 'default',
                ]
            );

            if (!$response->successful()) {
                Log::error("Falha ao notificar vendedor via Python: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Erro ao chamar Python para notificação: " . $e->getMessage());
        }
    }
}
