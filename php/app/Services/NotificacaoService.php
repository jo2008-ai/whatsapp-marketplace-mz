<?php

namespace App\Services;

use App\Models\Encomenda;
use App\Models\Produto;
use Illuminate\Support\Facades\Log;

class NotificacaoService
{
    private EvolutionService $evolutionService;

    public function __construct(EvolutionService $evolutionService)
    {
        $this->evolutionService = $evolutionService;
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
            $this->evolutionService->enviarMensagem($tenant->id, $vendedor->numero_whatsapp, $mensagem, $instancia->waha_url);
        } catch (\Exception $e) {
            Log::error('Erro ao notificar vendedor: '.$e->getMessage());
        }
    }

    public function notificarStockBaixo(Produto $produto): void
    {
        $tenant = $produto->tenant;
        if (! $tenant) {
            return;
        }

        $vendedor = $produto->vendedor;
        if (! $vendedor || ! $vendedor->ativo) {
            return;
        }

        $instancia = $tenant->instancias()
            ->where('estado', 'conectada')
            ->first();

        if (! $instancia) {
            Log::warning("Tenant {$tenant->id} sem instância WhatsApp conectada para alerta stock baixo");

            return;
        }

        $mensagem = "⚠️ *ALERTA DE STOCK BAIXO*\n\n"
                  ."📦 Produto: {$produto->nome}\n"
                  ."📊 Stock actual: {$produto->stock} {$produto->unidade}\n"
                  ."📉 Stock mínimo: {$produto->stock_minimo} {$produto->unidade}\n\n"
                  ."Repõe o stock no painel:\n"
                  .config('app.url').'/painel/stock';

        try {
            $this->evolutionService->enviarMensagem($tenant->id, $vendedor->numero_whatsapp, $mensagem, $instancia->waha_url);
        } catch (\Exception $e) {
            Log::error('Erro ao notificar vendedor sobre stock baixo: '.$e->getMessage());
        }
    }
}
