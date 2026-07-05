<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoriaRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Categoria;
use App\Services\CategoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApiCategoriaController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CategoriaService $categoriaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $categorias = $this->categoriaService->listar($tenant);

        return $this->success($categorias);
    }

    /**
     * @param int $id
     */
    public function show(Request $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $categoria = $this->categoriaService->obterPorId($tenant, $id);

        if (!$categoria) {
            return $this->notFound('Categoria não encontrada.');
        }

        Gate::authorize('view', $categoria);

        return $this->success($categoria);
    }

    public function store(CategoriaRequest $request): JsonResponse
    {
        Gate::authorize('create', Categoria::class);

        $tenant = $request->user()->tenant;
        $validated = $request->validated();
        $validated['ativo'] = $request->boolean('ativo', true);

        $categoria = $this->categoriaService->criar($tenant, $validated);

        return $this->created($categoria, 'Categoria criada.');
    }

    /**
     * @param int $id
     */
    public function update(CategoriaRequest $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $categoria = $this->categoriaService->obterPorId($tenant, $id);

        if (!$categoria) {
            return $this->notFound('Categoria não encontrada.');
        }

        Gate::authorize('update', $categoria);

        $validated = $request->validated();
        $validated['ativo'] = $request->boolean('ativo', true);

        $categoria = $this->categoriaService->actualizar($tenant, $id, $validated);

        return $this->success($categoria, 'Categoria actualizada.');
    }

    /**
     * @param int $id
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $categoria = $this->categoriaService->obterPorId($tenant, $id);

        if (!$categoria) {
            return $this->notFound('Categoria não encontrada.');
        }

        Gate::authorize('delete', $categoria);

        $resultado = $this->categoriaService->eliminar($tenant, $id);

        if (!$resultado['success']) {
            return $this->error($resultado['message'], 422);
        }

        return $this->success(null, $resultado['message']);
    }
}
