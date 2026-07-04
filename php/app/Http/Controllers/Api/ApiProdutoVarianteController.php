<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Produto;
use App\Models\ProdutoVariante;
use App\Services\ProdutoVarianteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApiProdutoVarianteController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ProdutoVarianteService $varianteService
    ) {}

    public function index(Request $request, $produtoId): JsonResponse
    {
        $produto = Produto::find($produtoId);

        if (!$produto) {
            return $this->notFound('Produto não encontrado.');
        }

        $variantes = $this->varianteService->listarVariantes(null, $produto);

        return $this->success($variantes);
    }

    public function store(Request $request, $produtoId): JsonResponse
    {
        $produto = Produto::find($produtoId);

        if (!$produto) {
            return $this->notFound('Produto não encontrado.');
        }

        $validated = $request->validate([
            'sku' => 'nullable|string|max:100',
            'preco_override' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'disponivel' => 'boolean',
            'imagem_url' => 'nullable|string|max:500',
            'atributos' => 'required|array|min:1',
            'atributos.*.atributo_id' => 'required|integer|exists:atributos,id',
            'atributos.*.valor' => 'nullable|string|max:100',
            'atributos.*.valor_id' => 'nullable|integer|exists:atributo_valores,id',
            'atributos.*.valor_hex' => 'nullable|string|max:7',
        ]);

        $variante = $this->varianteService->criarVariante(null, $produto, $validated);

        return $this->created($variante, 'Variante criada.');
    }

    public function update(Request $request, $id): JsonResponse
    {
        $variante = ProdutoVariante::find($id);

        if (!$variante) {
            return $this->notFound('Variante não encontrada.');
        }

        $validated = $request->validate([
            'sku' => 'nullable|string|max:100',
            'preco_override' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'disponivel' => 'boolean',
            'imagem_url' => 'nullable|string|max:500',
            'atributos' => 'nullable|array',
            'atributos.*.atributo_id' => 'required_with:atributos|integer|exists:atributos,id',
            'atributos.*.valor' => 'nullable|string|max:100',
            'atributos.*.valor_id' => 'nullable|integer|exists:atributo_valores,id',
            'atributos.*.valor_hex' => 'nullable|string|max:7',
        ]);

        $variante = $this->varianteService->actualizarVariante(null, $variante, $validated);

        return $this->success($variante, 'Variante actualizada.');
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $variante = ProdutoVariante::find($id);

        if (!$variante) {
            return $this->notFound('Variante não encontrada.');
        }

        $this->varianteService->eliminarVariante(null, $variante);

        return $this->success(null, 'Variante removida.');
    }

    public function toggleDisponivel(Request $request, $id): JsonResponse
    {
        $variante = ProdutoVariante::find($id);

        if (!$variante) {
            return $this->notFound('Variante não encontrada.');
        }

        $variante = $this->varianteService->toggleDisponivel(null, $variante);

        return $this->success($variante, $variante->disponivel ? 'Variante activada.' : 'Variante desactivada.');
    }
}
