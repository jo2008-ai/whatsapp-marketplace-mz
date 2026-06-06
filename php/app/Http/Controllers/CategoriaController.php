<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        $categorias = $tenant->categorias()->orderBy('ordem')->withCount('produtos')->get();

        return view('painel.categorias.index', compact('categorias', 'tenant'));
    }

    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:255',
            'icone' => 'nullable|string|max:10',
            'ordem' => 'nullable|integer|min:0',
        ]);

        $validated['tenant_id'] = $tenant->id;

        Categoria::create($validated);

        return redirect('/painel/categorias')->with('success', 'Categoria criada!');
    }

    public function update(Request $request, Categoria $categoria)
    {
        if ($categoria->tenant_id !== $request->user()->tenant_id) {
            abort(403);
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

        return redirect('/painel/categorias')->with('success', 'Categoria actualizada!');
    }

    public function destroy(Request $request, Categoria $categoria)
    {
        if ($categoria->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $categoria->delete();

        return redirect('/painel/categorias')->with('success', 'Categoria removida.');
    }
}
