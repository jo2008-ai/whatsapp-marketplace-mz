<?php

namespace App\Http\Controllers;

use App\Services\CategoriaService;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function __construct(
        private CategoriaService $categoriaService
    ) {}

    /** @return \Illuminate\View\View */
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        $categorias = $this->categoriaService->listar($tenant);

        return view('painel.categorias.index', compact('categorias', 'tenant'));
    }

    /** @return \Illuminate\Http\RedirectResponse */
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

        $this->categoriaService->criar($tenant, $validated);

        return redirect('/painel/categorias')->with('success', 'Categoria criada!');
    }

    /** @return \Illuminate\Http\RedirectResponse */
    public function update(Request $request, int $id)
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:255',
            'icone' => 'nullable|string|max:10',
            'ordem' => 'nullable|integer|min:0',
            'ativo' => 'boolean',
        ]);

        $validated['ativo'] = $request->boolean('ativo', true);

        $categoria = $this->categoriaService->actualizar($tenant, $id, $validated);

        if (!$categoria) {
            abort(404);
        }

        return redirect('/painel/categorias')->with('success', 'Categoria actualizada!');
    }

    /** @return \Illuminate\Http\RedirectResponse */
    public function destroy(Request $request, int $id)
    {
        $tenant = $request->user()->tenant;
        $resultado = $this->categoriaService->eliminar($tenant, $id);

        if (!$resultado['success']) {
            return back()->withErrors(['error' => $resultado['message']]);
        }

        return redirect('/painel/categorias')->with('success', $resultado['message']);
    }
}
