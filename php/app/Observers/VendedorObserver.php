<?php

namespace App\Observers;

use App\Events\VendedorRegistado;
use App\Events\VendedorRemovido;
use App\Models\Tenant;
use App\Models\Vendedor;
use Illuminate\Support\Facades\Log;

class VendedorObserver
{
    public function created(Vendedor $vendedor): void
    {
        try {
            /** @var Tenant|null $tenant */
            $tenant = $vendedor->tenant;
            event(new VendedorRegistado($vendedor, $tenant));
        } catch (\Exception $e) {
            Log::error('Erro ao disparar evento VendedorRegistado: ' . $e->getMessage());
        }
    }

    public function deleted(Vendedor $vendedor): void
    {
        try {
            /** @var Tenant|null $tenant */
            $tenant = $vendedor->tenant;
            event(new VendedorRemovido(
                $vendedor->id,
                $vendedor->nome,
                $tenant,
            ));
        } catch (\Exception $e) {
            Log::error('Erro ao disparar evento VendedorRemovido: ' . $e->getMessage());
        }
    }
}
