<?php

namespace App\Listeners;

use App\Events\ProdutoCriado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificarAdminNovoProduto implements ShouldQueue
{
    public int $tries = 2;

    public int $backoff = 15;

    public function handle(ProdutoCriado $event): void
    {
        $produto = $event->produto;
        $tenant = $event->tenant;

        if (!$tenant) {
            return;
        }

        try {
            $adminUrl = config('app.admin_url');

            if (!$adminUrl) {
                return;
            }

            Http::timeout(5)->post("{$adminUrl}/api/webhook/produto-criado", [
                'tenant_id' => $tenant->id,
                'tenant_nome' => $tenant->nome_loja,
                'produto_id' => $produto->id,
                'produto_nome' => $produto->nome,
                'preco' => (float) $produto->preco,
            ]);
        } catch (\Exception $e) {
            Log::debug('Notificação admin (produto criado) não enviada: ' . $e->getMessage());
        }
    }
}
