<?php

namespace App\Http\Controllers;

use App\Models\Vendedor;
use Illuminate\Http\Request;

class VendedorController extends Controller
{
    /** @return \Illuminate\View\View */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }
        $tenant = $user->tenant;
        if (!$tenant) {
            abort(401);
        }
        $vendedores = $tenant->vendedores()->withCount('produtos')->orderBy('nome')->get();

        return view('painel.vendedores.index', compact('vendedores', 'tenant'));
    }

    /** @return \Illuminate\Http\RedirectResponse */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }
        if (!$user->isAdmin()) {
            abort(403);
        }

        $tenant = $user->tenant;
        if (!$tenant) {
            abort(401);
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'numero_whatsapp' => 'required|string|max:20',
            'descricao' => 'nullable|string|max:255',
        ]);

        $validated['tenant_id'] = $tenant->id;

        Vendedor::create($validated);

        return redirect('/painel/vendedores')->with('success', 'Vendedor adicionado!');
    }

    /** @return \Illuminate\Http\RedirectResponse */
    public function update(Request $request, Vendedor $vendedor)
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }
        if (!$user->isAdmin()) {
            abort(403);
        }

        if ($vendedor->tenant_id !== $user->tenant_id) {
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

    /** @return \Illuminate\Http\RedirectResponse */
    public function destroy(Request $request, Vendedor $vendedor)
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }
        if (!$user->isAdmin()) {
            abort(403);
        }

        if ($vendedor->tenant_id !== $user->tenant_id) {
            abort(403);
        }

        $vendedor->delete();

        return redirect('/painel/vendedores')->with('success', 'Vendedor removido.');
    }
}
