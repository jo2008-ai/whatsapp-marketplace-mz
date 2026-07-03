<?php

namespace App\Http\Controllers;

use App\Services\ProdutoService;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    public function __construct(
        private ProdutoService $produtoService
    ) {}

    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        $produtos = $this->produtoService->listar($tenant, $request);
        $categorias = $tenant->categorias()->where('ativo', true)->orderBy('nome')->get();

        return view('painel.produtos.index', compact('produtos', 'categorias', 'tenant'));
    }

    public function create(Request $request)
    {
        $tenant = $request->user()->tenant;
        $categorias = $tenant->categorias()->where('ativo', true)->orderBy('nome')->get();
        $vendedores = $tenant->vendedores()->where('ativo', true)->orderBy('nome')->get();

        return view('painel.produtos.form', compact('categorias', 'vendedores', 'tenant'));
    }

    public function store(Request $request)
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'preco' => 'required|numeric|min:0.01',
            'stock' => 'required|integer|min:0',
            'categoria_id' => 'nullable|exists:categorias,id',
            'vendedor_id' => 'nullable|exists:vendedores,id',
            'imagem' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            'imagem2' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'disponivel' => 'boolean',
            'destaque' => 'boolean',
        ]);

        $validated['disponivel'] = $request->boolean('disponivel', true);
        $validated['destaque'] = $request->boolean('destaque');

        $validated['cores'] = $this->parseJsonArray($request->input('cores_json'));
        $validated['tamanhos'] = $this->parseJsonArray($request->input('tamanhos_json'));

        $this->produtoService->criar(
            $tenant,
            $validated,
            $request->file('imagem'),
            $request->file('imagem2')
        );

        return redirect('/painel/produtos')->with('success', 'Produto criado com sucesso!');
    }

    public function edit(Request $request, int $id)
    {
        $tenant = $request->user()->tenant;
        $produto = $this->produtoService->obterPorId($tenant, $id);

        if (!$produto) {
            abort(404);
        }

        $categorias = $tenant->categorias()->where('ativo', true)->orderBy('nome')->get();
        $vendedores = $tenant->vendedores()->where('ativo', true)->orderBy('nome')->get();

        return view('painel.produtos.form', compact('produto', 'categorias', 'vendedores', 'tenant'));
    }

    public function update(Request $request, int $id)
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'preco' => 'required|numeric|min:0.01',
            'stock' => 'required|integer|min:0',
            'categoria_id' => 'nullable|exists:categorias,id',
            'vendedor_id' => 'nullable|exists:vendedores,id',
            'imagem' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'imagem2' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'disponivel' => 'boolean',
            'destaque' => 'boolean',
        ]);

        $validated['disponivel'] = $request->boolean('disponivel', true);
        $validated['destaque'] = $request->boolean('destaque');

        $validated['cores'] = $this->parseJsonArray($request->input('cores_json'));
        $validated['tamanhos'] = $this->parseJsonArray($request->input('tamanhos_json'));

        $produto = $this->produtoService->actualizar(
            $tenant,
            $id,
            $validated,
            $request->file('imagem'),
            $request->file('imagem2')
        );

        if (!$produto) {
            abort(404);
        }

        return redirect('/painel/produtos')->with('success', 'Produto actualizado!');
    }

    public function destroy(Request $request, int $id)
    {
        $tenant = $request->user()->tenant;
        $this->produtoService->eliminar($tenant, $id);

        return redirect('/painel/produtos')->with('success', 'Produto removido.');
    }

    private function parseJsonArray(?string $json): ?array
    {
        if (empty($json) || $json === '[]') {
            return null;
        }

        $decoded = json_decode($json, true);

        if (!is_array($decoded) || empty($decoded)) {
            return null;
        }

        return array_values(array_filter(array_map('trim', $decoded)));
    }
}
