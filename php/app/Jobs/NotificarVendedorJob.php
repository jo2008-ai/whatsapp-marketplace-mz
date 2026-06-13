<?php

namespace App\Jobs;

use App\Models\Encomenda;
use App\Services\NotificacaoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotificarVendedorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $encomendaId)
    {
    }

    public function handle(NotificacaoService $notificacao): void
    {
        $encomenda = Encomenda::with(['produto', 'vendedor', 'tenant.instancias'])->find($this->encomendaId);

        if (!$encomenda || !$encomenda->vendedor) {
            return;
        }

        try {
            $notificacao->notificarVendedor($encomenda);
        } catch (\Exception $e) {
            Log::error('Job notificação falhou', [
                'encomenda_id' => $this->encomendaId,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw para retry
        }
    }
}
