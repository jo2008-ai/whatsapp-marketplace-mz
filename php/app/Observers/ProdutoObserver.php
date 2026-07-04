<?php

namespace App\Observers;

use App\Events\ProdutoActualizado;
use App\Events\ProdutoCriado;
use App\Events\ProdutoRemovido;
use App\Models\Produto;
use Illuminate\Support\Facades\Log;

class ProdutoObserver
{
    public function created(Produto $produto): void
    {
        try {
            event(new ProdutoCriado($produto, $produto->tenant));
        } catch (\Exception $e) {
            Log::error('Erro ao disparar evento ProdutoCriado: ' . $e->getMessage());
        }
    }

    public function updated(Produto $produto): void
    {
        try {
            event(new ProdutoActualizado($produto, $produto->tenant));
        } catch (\Exception $e) {
            Log::error('Erro ao disparar evento ProdutoActualizado: ' . $e->getMessage());
        }
    }

    public function deleted(Produto $produto): void
    {
        try {
            event(new ProdutoRemovido(
                $produto->id,
                $produto->nome,
                $produto->tenant,
            ));
        } catch (\Exception $e) {
            Log::error('Erro ao disparar evento ProdutoRemovido: ' . $e->getMessage());
        }
    }
}
