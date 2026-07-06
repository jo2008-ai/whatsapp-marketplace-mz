<?php

namespace App\Listeners;

use App\Events\EncomendaActualizada;
use App\Models\Encomenda;
use App\Services\WahaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotificarClienteWhatsApp implements ShouldQueue
{
    public int $tries = 3;

    public int $backoff = 30;

    private WahaService $wahaService;

    private const MENSAGENS = [
        'confirmada' => '✅ A tua encomenda foi confirmada! O vendedor %s irá contactar-te.',
        'em_entrega' => '🚚 A tua encomenda está a caminho!',
        'entregue' => '🎉 Encomenda entregue! Obrigado pela preferência.',
        'cancelada' => '❌ A tua encomenda foi cancelada. Contacta-nos para saber mais.',
    ];

    public function __construct(WahaService $wahaService)
    {
        $this->wahaService = $wahaService;
    }

    public function handle(EncomendaActualizada $event): void
    {
        $encomenda = $event->encomenda;

        if (! in_array($encomenda->estado, array_keys(self::MENSAGENS))) {
            return;
        }

        $tenant = $encomenda->tenant;

        if (! $tenant || ! $tenant->activo) {
            return;
        }

        $instancia = $tenant->instancias()
            ->where('estado', 'conectada')
            ->first();

        if (! $instancia) {
            Log::warning("Tenant {$tenant->id} sem instância WhatsApp para notificação ao cliente", [
                'encomenda_id' => $encomenda->id,
            ]);

            return;
        }

        $mensagem = $this->buildMensagem($encomenda);

        try {
            $this->wahaService->enviarMensagem($tenant->id, $encomenda->numero_cliente, $mensagem, $instancia->waha_url);
        } catch (\Exception $e) {
            Log::error('Erro ao notificar cliente WhatsApp: '.$e->getMessage(), [
                'encomenda_id' => $encomenda->id,
            ]);
            throw $e;
        }
    }

    private function buildMensagem(Encomenda $encomenda): string
    {
        $template = self::MENSAGENS[$encomenda->estado] ?? '';

        if ($encomenda->estado === 'confirmada' && $encomenda->vendedor) {
            return sprintf($template, $encomenda->vendedor->nome);
        }

        return $template;
    }
}
