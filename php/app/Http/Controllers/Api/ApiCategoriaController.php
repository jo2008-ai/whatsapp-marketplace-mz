<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiCategoriaController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $categorias = Categoria::where('tenant_id', $tenant->id)
            ->where('ativo', true)
            ->orderBy('ordem')
            ->withCount('produtos')
            ->get();

        return $this->success($categorias);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $categoria = Categoria::where('tenant_id', $tenant->id)
            ->withCount('produtos')
            ->find($id);

        if (!$categoria) {
            return $this->notFound('Categoria não encontrada.');
        }

        return $this->success($categoria);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:255',
            'icone' => 'nullable|string|max:10',
            'ordem' => 'nullable|integer|min:0',
            'ativo' => 'boolean',
        ]);

        $validated['tenant_id'] = $tenant->id;
        $validated['ativo'] = $request->boolean('ativo', true);

        $categoria = Categoria::create($validated);

        return $this->created($categoria, 'Categoria criada.');
    }

    public function update(Request $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $categoria = Categoria::where('tenant_id', $tenant->id)->find($id);

        if (!$categoria) {
            return $this->notFound('Categoria não encontrada.');
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:255',
            'icone' => 'nullable|string|max:10',
            'ordem' => 'nullable|integer|min:0',
            'ativo' => 'boolean',
        ]);

        $validated['ativo'] = $request->boolean('ativo', true);

        $categoria->update($validated);

        return $this->success($categoria, 'Categoria actualizada.');
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $categoria = Categoria::where('tenant_id', $tenant->id)->find($id);

        if (!$categoria) {
            return $this->notFound('Categoria não encontrada.');
        }

        if ($categoria->produtos()->count() > 0) {
            return $this->error(
                'Não é possível remover uma categoria com produtos.',
                422
            );
        }

        $categoria->delete();

        return $this->success(null, 'Categoria removida.');
    }
}
