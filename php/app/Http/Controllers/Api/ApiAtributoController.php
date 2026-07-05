<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Atributo;
use App\Services\AtributoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApiAtributoController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AtributoService $atributoService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $atributos = $this->atributoService->listar();

        return $this->success($atributos);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $atributo = $this->atributoService->obterPorId(null, $id);

        if (!$atributo) {
            return $this->notFound('Atributo não encontrado.');
        }

        return $this->success($atributo);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50',
            'nome' => 'required|string|max:100',
            'tipo' => 'required|in:cor,tamanho,material,peso,custom',
            'is_filterable' => 'boolean',
            'is_configurable' => 'boolean',
            'swatch_type' => 'nullable|string|max:20',
            'ordem' => 'nullable|integer|min:0',
        ]);

        $atributo = $this->atributoService->criar(null, $validated);

        return $this->created($atributo, 'Atributo criado.');
    }

    public function update(Request $request, $id): JsonResponse
    {
        $atributo = $this->atributoService->obterPorId(null, $id);

        if (!$atributo) {
            return $this->notFound('Atributo não encontrado.');
        }

        $validated = $request->validate([
            'nome' => 'nullable|string|max:100',
            'is_filterable' => 'boolean',
            'is_configurable' => 'boolean',
            'swatch_type' => 'nullable|string|max:20',
            'ordem' => 'nullable|integer|min:0',
        ]);

        $atributo = $this->atributoService->actualizar(null, $id, $validated);

        return $this->success($atributo, 'Atributo actualizado.');
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $atributo = $this->atributoService->obterPorId(null, $id);

        if (!$atributo) {
            return $this->notFound('Atributo não encontrado.');
        }

        $this->atributoService->eliminar(null, $id);

        return $this->success(null, 'Atributo removido.');
    }

    public function adicionarValor(Request $request, $id): JsonResponse
    {
        $atributo = $this->atributoService->obterPorId(null, $id);

        if (!$atributo) {
            return $this->notFound('Atributo não encontrado.');
        }

        $validated = $request->validate([
            'codigo' => 'required|string|max:50',
            'nome' => 'required|string|max:100',
            'valor' => 'nullable|string|max:100',
            'swatch_url' => 'nullable|string|max:500',
            'ordem' => 'nullable|integer|min:0',
        ]);

        $valor = $this->atributoService->adicionarValor(null, $id, $validated);

        return $this->created($valor, 'Valor adicionado.');
    }

    public function actualizarValor(Request $request, $valorId): JsonResponse
    {
        $valor = $this->atributoService->actualizarValor(null, $valorId, $request->all());

        if (!$valor) {
            return $this->notFound('Valor não encontrado.');
        }

        return $this->success($valor, 'Valor actualizado.');
    }

    public function eliminarValor(Request $request, $valorId): JsonResponse
    {
        $resultado = $this->atributoService->eliminarValor(null, $valorId);

        if (!$resultado) {
            return $this->notFound('Valor não encontrado.');
        }

        return $this->success(null, 'Valor removido.');
    }
}
