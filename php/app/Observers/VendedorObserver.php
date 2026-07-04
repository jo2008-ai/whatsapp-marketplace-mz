<?php

namespace App\Observers;

use App\Events\VendedorRegistado;
use App\Events\VendedorRemovido;
use App\Models\Vendedor;
use Illuminate\Support\Facades\Log;

class VendedorObserver
{
    public function created(Vendedor $vendedor): void
    {
        try {
            event(new VendedorRegistado($vendedor, $vendedor->tenant));
        } catch (\Exception $e) {
            Log::error('Erro ao disparar evento VendedorRegistado: ' . $e->getMessage());
        }
    }

    public function deleted(Vendedor $vendedor): void
    {
        try {
            event(new VendedorRemovido(
                $vendedor->id,
                $vendedor->nome,
                $vendedor->tenant,
            ));
        } catch (\Exception $e) {
            Log::error('Erro ao disparar evento VendedorRemovido: ' . $e->getMessage());
        }
    }
}
