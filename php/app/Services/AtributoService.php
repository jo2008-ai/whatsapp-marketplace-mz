<?php

namespace App\Services;

use App\Models\Atributo;
use App\Models\AtributoValor;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class AtributoService
{
    /** @return Collection<int, Atributo> */
    public function listar(?Tenant $tenant = null): Collection
    {
        return Atributo::with('valores')
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get();
    }

    public function obterPorId(?Tenant $tenant = null, int $id): ?Atributo
    {
        return Atributo::with('valores')->find($id);
    }

    /**
     * @param array<string, mixed> $validated
     */
    public function criar(?Tenant $tenant = null, array $validated): Atributo
    {
        $tenant = $tenant ?? app(\App\Context\TenantContext::class)->tenant();
        $validated['tenant_id'] = $tenant->id;

        return Atributo::create($validated);
    }

    /**
     * @param array<string, mixed> $validated
     */
    public function actualizar(?Tenant $tenant = null, int $id, array $validated): ?Atributo
    {
        $atributo = $this->obterPorId($tenant, $id);

        if (!$atributo) {
            return null;
        }

        $atributo->update($validated);

        return $atributo;
    }

    public function eliminar(?Tenant $tenant = null, int $id): bool
    {
        $atributo = $this->obterPorId($tenant, $id);

        if (!$atributo) {
            return false;
        }

        return $atributo->delete();
    }

    /**
     * @param array<string, mixed> $validated
     */
    public function adicionarValor(?Tenant $tenant = null, int $atributoId, array $validated): ?AtributoValor
    {
        $atributo = $this->obterPorId($tenant, $atributoId);

        if (!$atributo) {
            return null;
        }

        return $atributo->valores()->create($validated);
    }

    /**
     * @param array<string, mixed> $validated
     */
    public function actualizarValor(?Tenant $tenant = null, int $valorId, array $validated): ?AtributoValor
    {
        $valor = AtributoValor::find($valorId);

        if (!$valor) {
            return null;
        }

        $valor->update($validated);

        return $valor;
    }

    public function eliminarValor(?Tenant $tenant = null, int $valorId): bool
    {
        $valor = AtributoValor::find($valorId);

        if (!$valor) {
            return false;
        }

        return $valor->delete();
    }
}
