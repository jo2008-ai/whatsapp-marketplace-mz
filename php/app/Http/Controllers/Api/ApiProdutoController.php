<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Produto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiProdutoController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $query = Produto::where('tenant_id', $tenant->id)
            ->with(['categoria:id,nome,icone', 'vendedor:id,nome']);

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->filled('pesquisa')) {
            $pesquisa = $request->pesquisa;
            $query->where(fn($q) => $q->where('nome', 'ILIKE', "%{$pesquisa}%")->orWhere('descricao', 'ILIKE', "%{$pesquisa}%"));
        }

        if ($request->has('disponivel')) {
            $query->where('disponivel', $request->boolean('disponivel'));
        }

        $produtos = $query->orderByDesc('created_at')->paginate(20);

        return $this->success($produtos);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $produto = Produto::where('tenant_id', $tenant->id)
            ->with(['categoria:id,nome,icone', 'vendedor:id,nome,numero_whatsapp'])
            ->find($id);

        if (!$produto) {
            return $this->notFound('Produto não encontrado.');
        }

        return $this->success($produto);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'nome' => 'required|string|max:100',
            'descricao' => 'nullable|string',
            'preco' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'categoria_id' => 'required|integer|exists:categorias,id',
            'vendedor_id' => 'required|integer|exists:vendedores,id',
            'imagem' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'imagem2' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'imagem_url' => 'nullable|url',
            'imagem2_url' => 'nullable|url',
            'disponivel' => 'boolean',
            'destaque' => 'boolean',
            'cores' => 'nullable|array|max:10',
            'cores.*' => 'string|max:30',
            'tamanhos' => 'nullable|array|max:10',
            'tamanhos.*' => 'string|max:10',
        ]);

        $validated['tenant_id'] = $tenant->id;
        $validated['disponivel'] = $request->boolean('disponivel', true);
        $validated['destaque'] = $request->boolean('destaque', false);

        if ($request->hasFile('imagem')) {
            $validated['imagem_url'] = $this->guardarImagem($request->file('imagem'));
        }

        if ($request->hasFile('imagem2')) {
            $validated['imagem2_url'] = $this->guardarImagem($request->file('imagem2'));
        }

        unset($validated['imagem'], $validated['imagem2']);

        $produto = Produto::create($validated);
        $produto->load(['categoria:id,nome,icone', 'vendedor:id,nome']);

        return $this->created($produto, 'Produto criado com sucesso.');
    }

    public function update(Request $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $produto = Produto::where('tenant_id', $tenant->id)->find($id);

        if (!$produto) {
            return $this->notFound('Produto não encontrado.');
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:100',
            'descricao' => 'nullable|string',
            'preco' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'categoria_id' => 'required|integer|exists:categorias,id',
            'vendedor_id' => 'required|integer|exists:vendedores,id',
            'imagem' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'imagem2' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'imagem_url' => 'nullable|url',
            'imagem2_url' => 'nullable|url',
            'disponivel' => 'boolean',
            'destaque' => 'boolean',
            'cores' => 'nullable|array|max:10',
            'cores.*' => 'string|max:30',
            'tamanhos' => 'nullable|array|max:10',
            'tamanhos.*' => 'string|max:10',
        ]);

        $validated['disponivel'] = $request->boolean('disponivel', true);
        $validated['destaque'] = $request->boolean('destaque', false);

        if ($request->hasFile('imagem')) {
            $validated['imagem_url'] = $this->guardarImagem($request->file('imagem'));
        } elseif ($request->input('imagem_url') === null && !$request->has('imagem_url')) {
            unset($validated['imagem_url']);
        }

        if ($request->hasFile('imagem2')) {
            $validated['imagem2_url'] = $this->guardarImagem($request->file('imagem2'));
        } elseif ($request->input('imagem2_url') === null && !$request->has('imagem2_url')) {
            unset($validated['imagem2_url']);
        }

        unset($validated['imagem'], $validated['imagem2']);

        $produto->update($validated);
        $produto->load(['categoria:id,nome,icone', 'vendedor:id,nome']);

        return $this->success($produto, 'Produto actualizado.');
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $produto = Produto::where('tenant_id', $tenant->id)->find($id);

        if (!$produto) {
            return $this->notFound('Produto não encontrado.');
        }

        $produto->delete();

        return $this->success(null, 'Produto removido.');
    }

    public function toggleDisponivel(Request $request, $id): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $produto = Produto::where('tenant_id', $tenant->id)->find($id);

        if (!$produto) {
            return $this->notFound('Produto não encontrado.');
        }

        $produto->update(['disponivel' => !$produto->disponivel]);

        return $this->success([
            'id' => $produto->id,
            'disponivel' => $produto->disponivel,
        ], $produto->disponivel ? 'Produto activado.' : 'Produto desactivado.');
    }

    private function guardarImagem($file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->storeAs('public/produtos', $filename);
        return url('storage/produtos/' . $filename);
    }
}
