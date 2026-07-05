<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DefinicoesController extends Controller
{
    /** @return \Illuminate\View\View */
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        return view('painel.definicoes.index', compact('tenant'));
    }

    /** @return \Illuminate\Http\RedirectResponse */
    public function guardar(Request $request)
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'nome_loja' => 'required|string|max:255',
            'logo_url' => 'nullable|url|max:500',
            'cor_primaria' => 'required|string|max:7',
            'mensagem_boas_vindas' => 'nullable|string|max:1000',
            'mensagem_erro_menu' => 'nullable|string|max:500',
            'mensagem_categoria_vazia' => 'nullable|string|max:500',
            'mensagem_pesquisa_vazia' => 'nullable|string|max:500',
            'mensagem_pedido_sucesso' => 'nullable|string|max:500',
            'mensagem_pedido_cancelado' => 'nullable|string|max:500',
            'mensagem_vendedores_indisponivel' => 'nullable|string|max:500',
            'mensagem_transferencia' => 'nullable|string|max:500',
            'usar_typebot' => 'boolean',
            'typebot_bot_id' => 'nullable|string|max:255',
            'typebot_api_url' => 'nullable|url|max:500',
        ]);

        $validated['usar_typebot'] = $validated['usar_typebot'] ?? false;

        $tenant->update($validated);

        return back()->with('success', 'Definições guardadas!');
    }
}
