<?php

namespace App\Services;

use App\Context\TenantContext;
use App\Models\Categoria;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class CategoriaService
{
    private function resolveTenant(?Tenant $tenant = null): Tenant
    {
        if ($tenant) {
            return $tenant;
        }

        return app(TenantContext::class)->tenant();
    }

    /** @return Collection<int, Categoria> */
    public function listar(?Tenant $tenant = null): Collection
    {
        $tenant = $this->resolveTenant($tenant);

        return Categoria::where('ativo', true)
            ->orderBy('ordem')
            ->withCount('produtos')
            ->get();
    }

    public function obterPorId(?Tenant $tenant = null, int $id): ?Categoria
    {
        $tenant = $this->resolveTenant($tenant);

        return Categoria::withCount('produtos')
            ->find($id);
    }

    /**
     * @param array<string, mixed> $validated
     */
    public function criar(?Tenant $tenant = null, array $validated): Categoria
    {
        $tenant = $this->resolveTenant($tenant);
        $validated['tenant_id'] = $tenant->id;

        return Categoria::create($validated);
    }

    /**
     * @param array<string, mixed> $validated
     */
    public function actualizar(?Tenant $tenant = null, int $id, array $validated): ?Categoria
    {
        $categoria = $this->obterPorId($tenant, $id);

        if (!$categoria) {
            return null;
        }

        $categoria->update($validated);

        return $categoria;
    }

    /** @return array{success: bool, message: string} */
    public function eliminar(?Tenant $tenant = null, int $id): array
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
