<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DefinicoesController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        return view('painel.definicoes.index', compact('tenant'));
    }

    public function guardar(Request $request)
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'nome_loja' => 'required|string|max:255',
            'logo_url' => 'nullable|url|max:500',
            'cor_primaria' => 'required|string|max:7',
            'mensagem_boas_vindas' => 'nullable|string|max:1000',
        ]);

        $tenant->update($validated);

        return back()->with('success', 'Definições guardadas!');
    }
}
