<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class CategoriaService
{
    public function listar(Tenant $tenant): Collection
    {
        return $tenant->categorias()
            ->where('ativo', true)
            ->orderBy('ordem')
            ->withCount('produtos')
            ->get();
    }

    public function obterPorId(Tenant $tenant, int $id): ?Categoria
    {
        return $tenant->categorias()
            ->withCount('produtos')
            ->find($id);
    }

    public function criar(Tenant $tenant, array $validated): Categoria
    {
        $validated['tenant_id'] = $tenant->id;

        return Categoria::create($validated);
    }

    public function actualizar(Tenant $tenant, int $id, array $validated): ?Categoria
    {
        $categoria = $this->obterPorId($tenant, $id);

        if (!$categoria) {
            return null;
        }

        $categoria->update($validated);

        return $categoria;
    }

    public function eliminar(Tenant $tenant, int $id): array
    {
        $categoria = $this->obterPorId($tenant, $id);

        if (!$categoria) {
            return ['success' => false, 'message' => 'Categoria não encontrada.'];
        }

        if ($categoria->produtos()->count() > 0) {
            return ['success' => false, 'message' => 'Não é possível remover uma categoria com produtos.'];
        }

        $categoria->delete();

        return ['success' => true, 'message' => 'Categoria removida.'];
    }
}
