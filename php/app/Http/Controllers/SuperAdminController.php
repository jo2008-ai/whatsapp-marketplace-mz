<?php

namespace App\Http\Controllers;

use App\Models\InstanciaWhatsApp;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Subscricao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $lojas = Tenant::withCount(['produtos', 'encomendas'])->get();
        $instanciasLigadas = InstanciaWhatsApp::where('estado', 'conectada')->count();
        $totalInstancias = InstanciaWhatsApp::count();

        return view('super.dashboard', compact('lojas', 'instanciasLigadas', 'totalInstancias'));
    }

    public function lojas()
    {
        $lojas = Tenant::withCount(['produtos', 'encomendas'])
            ->with('instancias')
            ->orderBy('id')
            ->get();

        return view('super.lojas.index', compact('lojas'));
    }

    public function criar()
    {
        return view('super.lojas.criar');
    }

    public function criarRapido(Request $request)
    {
        $validated = $request->validate([
            'nome_loja' => 'required|string|max:255',
            'nome_dono' => 'required|string|max:255',
            'telefone' => 'required|string|max:20',
        ]);

        $telefone = preg_replace('/[^0-9]/', '', $validated['telefone']);
        $email = $telefone . '@loja.local';

        $tenant = Tenant::create([
            'uuid' => (string) Str::uuid(),
            'nome_loja' => $validated['nome_loja'],
            'email_dono' => $email,
            'telefone_dono' => $validated['telefone'],
            'plano' => 'basic',
            'estado' => 'trial',
            'trial_termina_em' => now()->addDays(7),
            'max_produtos' => 50,
            'max_numeros' => 1,
            'activo' => true,
        ]);

        $loginCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['nome_dono'],
            'email' => $email,
            'telefone' => $validated['telefone'],
            'password' => null,
            'role' => 'admin',
            'login_code' => $loginCode,
        ]);

        Subscricao::create([
            'tenant_id' => $tenant->id,
            'plano' => 'basic',
            'preco_mensal' => 500,
            'data_inicio' => now(),
            'data_fim' => now()->addDays(7),
            'estado' => 'activa',
            'metodo_pagamento' => 'trial',
        ]);

        return redirect('/super/lojas/' . $tenant->id)
            ->with('success', "Loja criada! Login Code: {$loginCode}");
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['users', 'instancias']);
        $tenant->loadCount(['produtos', 'encomendas', 'categorias']);

        $encomendasRecentes = $tenant->encomendas()
            ->with('produto')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('super.lojas.detalhe', compact('tenant', 'encomendasRecentes'));
    }

    public function toggleActivo(Tenant $tenant)
    {
        $tenant->update(['activo' => !$tenant->activo]);

        $estado = $tenant->activo ? 'activada' : 'desactivada';
        return back()->with('success', "Loja \"{$tenant->nome_loja}\" {$estado}.");
    }

    public function gerarCodigo(Tenant $tenant)
    {
        $user = $tenant->users()->first();

        if (!$user) {
            return back()->with('error', 'Utilizador não encontrado.');
        }

        $loginCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update(['login_code' => $loginCode]);

        return back()->with('success', "Novo código gerado: {$loginCode}");
    }
}
