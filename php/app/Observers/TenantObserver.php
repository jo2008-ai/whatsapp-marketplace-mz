<?php

namespace App\Observers;

use App\Events\TenantActivado;
use App\Events\TenantSuspenso;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class TenantObserver
{
    public function updated(Tenant $tenant): void
    {
        if ($tenant->wasChanged('estado')) {
            try {
                match ($tenant->estado) {
                    'activo' => event(new TenantActivado($tenant)),
                    'suspenso' => event(new TenantSuspenso($tenant, 'Estado alterado para suspenso')),
                    default => null,
                };
            } catch (\Exception $e) {
                Log::error('Erro ao disparar evento de tenant: ' . $e->getMessage());
            }
        }
    }
}
