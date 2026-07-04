<?php

namespace App\Observers;

use App\Events\CategoriaActualizada;
use App\Events\CategoriaCriada;
use App\Events\CategoriaRemovida;
use App\Models\Categoria;
use Illuminate\Support\Facades\Log;

class CategoriaObserver
{
    public function created(Categoria $categoria): void
    {
        try {
            event(new CategoriaCriada($categoria, $categoria->tenant));
        } catch (\Exception $e) {
            Log::error('Erro ao disparar evento CategoriaCriada: ' . $e->getMessage());
        }
    }

    public function updated(Categoria $categoria): void
    {
        try {
            event(new CategoriaActualizada($categoria, $categoria->tenant));
        } catch (\Exception $e) {
            Log::error('Erro ao disparar evento CategoriaActualizada: ' . $e->getMessage());
        }
    }

    public function deleted(Categoria $categoria): void
    {
        try {
            event(new CategoriaRemovida(
                $categoria->id,
                $categoria->nome,
                $categoria->tenant,
            ));
        } catch (\Exception $e) {
            Log::error('Erro ao disparar evento CategoriaRemovida: ' . $e->getMessage());
        }
    }
}
