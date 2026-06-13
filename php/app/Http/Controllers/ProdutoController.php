<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProdutoController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        $query = $tenant->produtos()->with(['categoria', 'vendedor']);

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(fn($q) => $q->where('nome', 'ILIKE', "%{$busca}%")->orWhere('descricao', 'ILIKE', "%{$busca}%"));
        }

        $produtos = $query->orderByDesc('created_at')->paginate(20);
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

        $validated['tenant_id'] = $tenant->id;
        $validated['disponivel'] = $request->boolean('disponivel', true);
        $validated['destaque'] = $request->boolean('destaque');

        if ($request->hasFile('imagem')) {
            $validated['imagem_url'] = $this->guardarImagem($request->file('imagem'));
        }

        if ($request->hasFile('imagem2')) {
            $validated['imagem2_url'] = $this->guardarImagem($request->file('imagem2'));
        }

        unset($validated['imagem'], $validated['imagem2']);

        $validated['cores'] = $this->parseJsonArray($request->input('cores_json'));
        $validated['tamanhos'] = $this->parseJsonArray($request->input('tamanhos_json'));

        Produto::create($validated);

        return redirect('/painel/produtos')->with('success', 'Produto criado com sucesso!');
    }

    public function edit(Request $request, Produto $produto)
    {
        $tenant = $request->user()->tenant;

        if ($produto->tenant_id !== $tenant->id) {
            abort(403);
        }

        $categorias = $tenant->categorias()->where('ativo', true)->orderBy('nome')->get();
        $vendedores = $tenant->vendedores()->where('ativo', true)->orderBy('nome')->get();

        return view('painel.produtos.form', compact('produto', 'categorias', 'vendedores', 'tenant'));
    }

    public function update(Request $request, Produto $produto)
    {
        $tenant = $request->user()->tenant;

        if ($produto->tenant_id !== $tenant->id) {
            abort(403);
        }

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

        if ($request->hasFile('imagem')) {
            $validated['imagem_url'] = $this->guardarImagem($request->file('imagem'));
        }

        if ($request->hasFile('imagem2')) {
            $validated['imagem2_url'] = $this->guardarImagem($request->file('imagem2'));
        }

        unset($validated['imagem'], $validated['imagem2']);

        $validated['cores'] = $this->parseJsonArray($request->input('cores_json'));
        $validated['tamanhos'] = $this->parseJsonArray($request->input('tamanhos_json'));

        $produto->update($validated);

        return redirect('/painel/produtos')->with('success', 'Produto actualizado!');
    }

    public function destroy(Request $request, Produto $produto)
    {
        if ($produto->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $produto->delete();

        return redirect('/painel/produtos')->with('success', 'Produto removido.');
    }

    private function guardarImagem($file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->storeAs('public/produtos', $filename);
        return url('storage/produtos/' . $filename);
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
