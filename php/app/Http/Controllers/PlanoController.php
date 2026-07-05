<?php

namespace App\Http\Controllers;

use App\Models\Subscricao;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PlanoController extends Controller
{
    /** @return \Illuminate\View\View */
    public function index()
    {
        $tenant = auth()->user()->tenant;
        $subscricao = $tenant->subscricaoAtiva();

        $planos = [
            'basic' => [
                'nome' => 'Basic',
                'preco' => 500,
                'max_produtos' => 50,
                'max_numeros' => 1,
                'descricao' => 'Ideal para lojas pequenas',
            ],
            'pro' => [
                'nome' => 'Pro',
                'preco' => 1500,
                'max_produtos' => 500,
                'max_numeros' => 3,
                'descricao' => 'Para lojas em crescimento',
            ],
            'enterprise' => [
                'nome' => 'Enterprise',
                'preco' => 5000,
                'max_produtos' => 99999,
                'max_numeros' => 99999,
                'descricao' => 'Para grandes operações',
            ],
        ];

        return view('painel.plano', compact('tenant', 'subscricao', 'planos'));
    }

    /** @return \Illuminate\Http\RedirectResponse */
    public function upgrade(Request $request)
    {
        $tenant = auth()->user()->tenant;

        $validated = $request->validate([
            'plano' => 'required|in:basic,pro,enterprise',
            'referencia_pagamento' => 'required|string|max:255',
        ]);

        $planos = [
            'basic' => ['nome' => 'Basic', 'max_produtos' => 50, 'max_numeros' => 1, 'preco' => 500],
            'pro' => ['nome' => 'Pro', 'max_produtos' => 500, 'max_numeros' => 3, 'preco' => 1500],
            'enterprise' => ['nome' => 'Enterprise', 'max_produtos' => 99999, 'max_numeros' => 99999, 'preco' => 5000],
        ];

        $planoInfo = $planos[$validated['plano']];

        if ($validated['plano'] === $tenant->plano) {
            return back()->with('error', 'Já estás neste plano.');
        }

        Subscricao::create([
            'tenant_id' => $tenant->id,
            'plano' => $validated['plano'],
            'preco_mensal' => $planoInfo['preco'],
            'data_inicio' => now(),
            'data_fim' => now()->addMonth(),
            'estado' => 'pendente_pagamento',
            'metodo_pagamento' => 'mpesa',
            'referencia_pagamento' => $validated['referencia_pagamento'],
        ]);

        $this->notificarSuperAdmin($tenant, $validated['plano'], $validated['referencia_pagamento']);

        return back()->with('success',
            "Pedido de upgrade para {$planoInfo['nome']} enviado! " .
            "O pagamento será validado em breve pelo administrador."
        );
    }

    private function notificarSuperAdmin(Tenant $tenant, string $plano, string $referencia): void
    {
        $admins = User::where('role', 'super_admin')->get();

        foreach ($admins as $admin) {
            try {
                Mail::to($admin->email)->send(
                    new \App\Mail\PedidoUpgradeMail($tenant, $plano, $referencia)
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error(
                    "Erro ao notificar super admin sobre upgrade: " . $e->getMessage()
                );
            }
        }
    }
}
