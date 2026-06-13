<?php

namespace App\Observers;

use App\Events\NovaEncomenda;
use App\Models\Encomenda;
use Illuminate\Support\Facades\Log;

class EncomendaObserver
{
    public function created(Encomenda $encomenda): void
    {
        try {
            event(new NovaEncomenda($encomenda));
        } catch (\Exception $e) {
            Log::error('Erro ao broadcast encomenda: ' . $e->getMessage());
        }
    }

    public function updated(Encomenda $encomenda): void
    {
        if ($encomenda->wasChanged('estado')) {
            try {
                event(new \App\Events\EncomendaActualizada($encomenda));
            } catch (\Exception $e) {
                Log::error('Erro ao broadcast actualização: ' . $e->getMessage());
            }
        }
    }
}
