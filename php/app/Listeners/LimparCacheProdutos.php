<?php

namespace App\Listeners;

use App\Events\ProdutoCriado;
use App\Events\ProdutoActualizado;
use App\Events\ProdutoRemovido;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LimparCacheProdutos implements ShouldQueue
{
    public int $tries = 2;

    public int $backoff = 5;

    public function handle(object $event): void
    {
        $tenantId = match (true) {
            $event instanceof ProdutoCriado => $event->tenant->id,
            $event instanceof ProdutoActualizado => $event->tenant->id,
            $event instanceof ProdutoRemovido => $event->tenant->id,
            default => null,
        };

        if (!$tenantId) {
            return;
        }

        try {
            Cache::tags(["tenant:{$tenantId}:produtos"])->flush();
            Cache::tags(["tenant:{$tenantId}:catalogo"])->flush();
        } catch (\Exception $e) {
            Log::warning('Erro ao limpar cache de produtos: ' . $e->getMessage());
        }
    }
}
