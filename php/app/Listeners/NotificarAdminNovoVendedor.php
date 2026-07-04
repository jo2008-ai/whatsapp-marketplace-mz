<?php

namespace App\Listeners;

use App\Events\VendedorRegistado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificarAdminNovoVendedor implements ShouldQueue
{
    public int $tries = 2;

    public int $backoff = 15;

    public function handle(VendedorRegistado $event): void
    {
        $vendedor = $event->vendedor;
        $tenant = $event->tenant;

        try {
            $adminUrl = config('app.admin_url');

            if (!$adminUrl) {
                return;
            }

            Http::timeout(5)->post("{$adminUrl}/api/webhook/vendedor-registado", [
                'tenant_id' => $tenant->id,
                'tenant_nome' => $tenant->nome_loja,
                'vendedor_id' => $vendedor->id,
                'vendedor_nome' => $vendedor->nome,
                'numero_whatsapp' => $vendedor->numero_whatsapp,
            ]);
        } catch (\Exception $e) {
            Log::debug('Notificação admin (vendedor registado) não enviada: ' . $e->getMessage());
        }
    }
}
