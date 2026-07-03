<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VendedorRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Vendedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApiVendedorController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $vendedores = Vendedor::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        return $this->success($vendedores);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $vendedor = Vendedor::where('tenant_id', $tenant->id)->find($id);

        if (!$vendedor) {
            return $this->notFound('Vendedor não encontrado.');
        }

        Gate::authorize('view', $vendedor);

        return $this->success($vendedor);
    }

    public function store(VendedorRequest $request): JsonResponse
    {
        Gate::authorize('create', Vendedor::class);

        $tenant = $request->user()->tenant;

        $validated = $request->validated();
        $validated['tenant_id'] = $tenant->id;
        $validated['ativo'] = $request->boolean('ativo', true);

        $vendedor = Vendedor::create($validated);

        return $this->created($vendedor, 'Vendedor criado com sucesso.');
    }

    public function update(VendedorRequest $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $vendedor = Vendedor::where('tenant_id', $tenant->id)->find($id);

        if (!$vendedor) {
            return $this->notFound('Vendedor não encontrado.');
        }

        Gate::authorize('update', $vendedor);

        $validated = $request->validated();
        $validated['ativo'] = $request->boolean('ativo', true);

        $vendedor->update($validated);

        return $this->success($vendedor, 'Vendedor actualizado.');
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $vendedor = Vendedor::where('tenant_id', $tenant->id)->find($id);

        if (!$vendedor) {
            return $this->notFound('Vendedor não encontrado.');
        }

        Gate::authorize('delete', $vendedor);

        $vendedor->delete();

        return $this->success(null, 'Vendedor removido.');
    }

    public function toggleAtivo(Request $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $vendedor = Vendedor::where('tenant_id', $tenant->id)->find($id);

        if (!$vendedor) {
            return $this->notFound('Vendedor não encontrado.');
        }

        Gate::authorize('update', $vendedor);

        $vendedor->update(['ativo' => !$vendedor->ativo]);

        return $this->success([
            'id' => $vendedor->id,
            'ativo' => $vendedor->ativo,
        ], $vendedor->ativo ? 'Vendedor activado.' : 'Vendedor desactivado.');
    }
}
