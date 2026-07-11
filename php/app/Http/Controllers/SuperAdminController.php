<?php

namespace App\Http\Controllers;

use App\Models\InstanciaWhatsApp;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Subscricao;
use App\Services\EvolutionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    private EvolutionService $evolutionService;

    public function __construct(EvolutionService $evolutionService)
    {
        $this->evolutionService = $evolutionService;
    }

    /** @return \Illuminate\View\View */
    public function dashboard()
    {
        $lojas = Tenant::withCount(['produtos', 'encomendas'])->get();
        $instanciasLigadas = InstanciaWhatsApp::where('estado', 'conectada')->count();
        $totalInstancias = InstanciaWhatsApp::count();

        return view('super.dashboard', compact('lojas', 'instanciasLigadas', 'totalInstancias'));
    }

    /** @return \Illuminate\View\View */
    public function lojas()
    {
        $lojas = Tenant::withCount(['produtos', 'encomendas'])
            ->with('instancias')
            ->orderBy('id')
            ->get();

        return view('super.lojas.index', compact('lojas'));
    }

    /** @return \Illuminate\View\View */
    public function criar()
    {
        return view('super.lojas.criar');
    }

    /** @return \Illuminate\Http\RedirectResponse */
    public function criarRapido(Request $request)
    {
        $validated = $request->validate([
            'nome_loja' => 'required|string|max:255',
            'nome_dono' => 'required|string|max:255',
            'telefone' => 'required|string|max:20',
            'plano' => 'required|in:basic,pro,enterprise',
        ]);

        $telefone = preg_replace('/[^0-9]/', '', $validated['telefone']);
        $email = $telefone . '@loja.local';

        $planos = [
            'basic' => ['preco' => 500, 'max_produtos' => 50, 'max_numeros' => 1],
            'pro' => ['preco' => 1500, 'max_produtos' => 500, 'max_numeros' => 3],
            'enterprise' => ['preco' => 5000, 'max_produtos' => 99999, 'max_numeros' => 99999],
        ];

        $plano = $planos[$validated['plano']];

        $tenant = Tenant::create([
            'uuid' => (string) Str::uuid(),
            'nome_loja' => $validated['nome_loja'],
            'email_dono' => $email,
            'telefone_dono' => $validated['telefone'],
            'plano' => $validated['plano'],
            'estado' => 'trial',
            'trial_termina_em' => now()->addDays(7),
            'max_produtos' => $plano['max_produtos'],
            'max_numeros' => $plano['max_numeros'],
            'activo' => true,
        ]);

        $loginCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

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
            'plano' => $validated['plano'],
            'preco_mensal' => $plano['preco'],
            'data_inicio' => now(),
            'data_fim' => now()->addDays(7),
            'estado' => 'activa',
            'metodo_pagamento' => 'trial',
        ]);

        InstanciaWhatsApp::create([
            'tenant_id'     => $tenant->id,
            'nome_instancia'=> "loja_{$tenant->id}",
            'waha_session'  => "loja-{$tenant->id}",
            'waha_url'      => config('services.evolution.url'),
            'estado'        => 'aguarda_qr',
        ]);

        try {
            $this->evolutionService->criarInstancia($tenant->id, config('services.evolution.url'));
        } catch (\Exception $e) {
            Log::warning("Erro ao criar instancia Evolution no criarRapido", [
                'tenant_id' => $tenant->id,
                'erro' => $e->getMessage(),
            ]);
        }

        return redirect('/super/lojas/' . $tenant->id)
            ->with('success', "Loja criada! Login Code: {$loginCode}");
    }

    /** @return \Illuminate\View\View */
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

    /** @return \Illuminate\Http\RedirectResponse */
    public function toggleActivo(Tenant $tenant)
    {
        $tenant->update(['activo' => !$tenant->activo]);

        $estado = $tenant->activo ? 'activada' : 'desactivada';
        return back()->with('success', "Loja \"{$tenant->nome_loja}\" {$estado}.");
    }

    /** @return \Illuminate\Http\RedirectResponse */
    public function gerarCodigo(Tenant $tenant)
    {
        $user = $tenant->users()->first();

        if (!$user) {
            return back()->with('error', 'Utilizador não encontrado.');
        }

        $loginCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update(['login_code' => $loginCode]);

        return back()->with('success', "Novo código gerado: {$loginCode}");
    }
}
