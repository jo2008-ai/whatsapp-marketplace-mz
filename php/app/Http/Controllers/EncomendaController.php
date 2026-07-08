<?php

namespace App\Http\Controllers;

use App\Models\Encomenda;
use Illuminate\Http\Request;

class EncomendaController extends Controller
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
        $query = $tenant->encomendas()->with(['produto', 'vendedor']);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $encomendas = $query->orderByDesc('created_at')->paginate(20);

        return view('painel.encomendas.index', compact('encomendas', 'tenant'));
    }

    /** @return \Illuminate\Http\RedirectResponse */
    public function atualizarEstado(Request $request, Encomenda $encomenda)
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }
        if ($encomenda->tenant_id !== $user->tenant_id) {
            abort(403);
        }

        $request->validate([
            'estado' => 'required|in:pendente,confirmada,entregue,cancelada',
        ]);

        $encomenda->update(['estado' => $request->estado]);

        return back()->with('success', 'Estado da encomenda actualizado!');
    }
}
