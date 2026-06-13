<?php

namespace App\Http\Controllers;

use App\Models\Vendedor;
use Illuminate\Http\Request;

class VendedorController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        $vendedores = $tenant->vendedores()->withCount('produtos')->orderBy('nome')->get();

        return view('painel.vendedores.index', compact('vendedores', 'tenant'));
    }

    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'numero_whatsapp' => 'required|string|max:20',
            'descricao' => 'nullable|string|max:255',
        ]);

        $validated['tenant_id'] = $tenant->id;

        Vendedor::create($validated);

        return redirect('/painel/vendedores')->with('success', 'Vendedor adicionado!');
    }

    public function update(Request $request, Vendedor $vendedor)
    {
        if ($vendedor->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'numero_whatsapp' => 'required|string|max:20',
            'descricao' => 'nullable|string|max:255',
            'ativo' => 'boolean',
        ]);

        $validated['ativo'] = $request->boolean('ativo', true);

        $vendedor->update($validated);

        return redirect('/painel/vendedores')->with('success', 'Vendedor actualizado!');
    }

    public function destroy(Request $request, Vendedor $vendedor)
    {
        if ($vendedor->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $vendedor->delete();

        return redirect('/painel/vendedores')->with('success', 'Vendedor removido.');
    }
}
