<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProdutoRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Produto;
use App\Services\ProdutoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApiProdutoController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ProdutoService $produtoService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $produtos = $this->produtoService->listar($tenant, $request);

        return $this->success($produtos);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $produto = $this->produtoService->obterPorId($tenant, $id);

        if (!$produto) {
            return $this->notFound('Produto não encontrado.');
        }

        Gate::authorize('view', $produto);

        return $this->success($produto);
    }

    public function store(ProdutoRequest $request): JsonResponse
    {
        Gate::authorize('create', Produto::class);

        $tenant = $request->user()->tenant;
        $validated = $request->validated();

        $validated['disponivel'] = $request->boolean('disponivel', true);
        $validated['destaque'] = $request->boolean('destaque', false);

        $produto = $this->produtoService->criar(
            $tenant,
            $validated,
            $request->file('imagem'),
            $request->file('imagem2')
        );

        return $this->created($produto, 'Produto criado com sucesso.');
    }

    public function update(ProdutoRequest $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $produto = $this->produtoService->obterPorId($tenant, $id);

        if (!$produto) {
            return $this->notFound('Produto não encontrado.');
        }

        Gate::authorize('update', $produto);

        $validated = $request->validated();

        $validated['disponivel'] = $request->boolean('disponivel', true);
        $validated['destaque'] = $request->boolean('destaque', false);

        $produto = $this->produtoService->actualizar(
            $tenant,
            $id,
            $validated,
            $request->file('imagem'),
            $request->file('imagem2')
        );

        return $this->success($produto, 'Produto actualizado.');
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $produto = $this->produtoService->obterPorId($tenant, $id);

        if (!$produto) {
            return $this->notFound('Produto não encontrado.');
        }

        Gate::authorize('delete', $produto);

        $this->produtoService->eliminar($tenant, $id);

        return $this->success(null, 'Produto removido.');
    }

    public function toggleDisponivel(Request $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $produto = $this->produtoService->obterPorId($tenant, $id);

        if (!$produto) {
            return $this->notFound('Produto não encontrado.');
        }

        Gate::authorize('update', $produto);

        $resultado = $this->produtoService->toggleDisponivel($tenant, $id);

        return $this->success($resultado, $resultado['disponivel'] ? 'Produto activado.' : 'Produto desactivado.');
    }
}
