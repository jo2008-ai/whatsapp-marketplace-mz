<?php

namespace App\Listeners;

use App\Events\EncomendaActualizada;
use App\Models\InstanciaWhatsApp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificarClienteWhatsApp implements ShouldQueue
{
    public int $tries = 3;

    public int $backoff = 30;

    private const MENSAGENS = [
        'confirmada' => "✅ A tua encomenda foi confirmada! O vendedor %s irá contactar-te.",
        'em_entrega' => "🚚 A tua encomenda está a caminho!",
        'entregue' => "🎉 Encomenda entregue! Obrigado pela preferência.",
        'cancelada' => "❌ A tua encomenda foi cancelada. Contacta-nos para saber mais.",
    ];

    public function handle(EncomendaActualizada $event): void
    {
        $encomenda = $event->encomenda;

        if (!in_array($encomenda->estado, array_keys(self::MENSAGENS))) {
            return;
        }

        $tenant = $encomenda->tenant;

        if (!$tenant || !$tenant->ativo()) {
            return;
        }

        $instancia = $tenant->instancias()
            ->where('estado', 'conectada')
            ->first();

        if (!$instancia) {
            Log::warning("Tenant {$tenant->id} sem instância WhatsApp para notificação ao cliente", [
                'encomenda_id' => $encomenda->id,
            ]);
            return;
        }

        $mensagem = $this->buildMensagem($encomenda);

        try {
            $response = Http::timeout(10)->post(
                config('services.python.url') . '/enviar',
                [
                    'numero' => $encomenda->numero_cliente,
                    'mensagem' => $mensagem,
                    'instance_name' => $instancia->evolution_instance_name,
                ]
            );

            if (!$response->successful()) {
                Log::error("Falha ao notificar cliente via Python", [
                    'encomenda_id' => $encomenda->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Erro ao notificar cliente WhatsApp: " . $e->getMessage(), [
                'encomenda_id' => $encomenda->id,
            ]);
            throw $e;
        }
    }

    private function buildMensagem(\App\Models\Encomenda $encomenda): string
    {
        $template = self::MENSAGENS[$encomenda->estado] ?? '';

        if ($encomenda->estado === 'confirmada' && $encomenda->vendedor) {
            return sprintf($template, $encomenda->vendedor->nome);
        }

        return $template;
    }
}
