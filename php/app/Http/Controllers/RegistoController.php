<?php

namespace App\Http\Controllers;

use App\Jobs\EnviarEmailJob;
use App\Mail\BoasVindasMail;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Subscricao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegistoController extends Controller
{
    public function show()
    {
        return view('publico.registar');
    }

    public function criar(Request $request)
    {
        $validated = $request->validate([
            'nome_loja' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'telefone' => 'required|string|max:20',
            'password' => ['required', 'string', 'min:8', 'confirmed', new \App\Rules\StrongPassword],
            'plano' => 'required|in:basic,pro,enterprise',
        ]);

        $planos = [
            'basic' => ['preco' => 500, 'max_produtos' => 50, 'max_numeros' => 1],
            'pro' => ['preco' => 1500, 'max_produtos' => 500, 'max_numeros' => 3],
            'enterprise' => ['preco' => 5000, 'max_produtos' => 99999, 'max_numeros' => 99999],
        ];

        $plano = $planos[$validated['plano']];

        $tenant = Tenant::create([
            'nome_loja' => $validated['nome_loja'],
            'email_dono' => $validated['email'],
            'telefone_dono' => $validated['telefone'],
            'plano' => $validated['plano'],
            'estado' => 'trial',
            'trial_termina_em' => now()->addDays(7),
            'max_produtos' => $plano['max_produtos'],
            'max_numeros' => $plano['max_numeros'],
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['nome_loja'] . ' Admin',
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
        ]);

        Subscricao::create([
            'tenant_id' => $tenant->id,
            'plano' => $validated['plano'],
            'preco_mensal' => $plano['preco'],
            'data_inicio' => now(),
            'data_fim' => now()->addDays(7),
            'estado' => 'activa',
        ]);

        // Email de boas-vindas (em background)
        try {
            EnviarEmailJob::dispatch($user->email, new BoasVindasMail($user, $tenant));
        } catch (\Exception $e) {
            // Não falha o registo se o email não enviar
        }

        auth()->login($user);

        return redirect('/painel')->with('success', 'Conta criada! Tens 7 dias de trial gratuito.');
    }
}
