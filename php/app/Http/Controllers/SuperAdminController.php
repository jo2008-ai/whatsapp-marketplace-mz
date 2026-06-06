<?php

namespace App\Http\Controllers;

use App\Mail\BoasVindasMail;
use App\Models\InstanciaWhatsApp;
use App\Models\Subscricao;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $totalLojas = Tenant::where('estado', '!=', 'cancelado')->count();
        $lojasActivas = Tenant::whereIn('estado', ['activo', 'trial'])->count();
        $receitaMes = Subscricao::where('estado', 'activa')
            ->whereMonth('data_inicio', now()->month)
            ->whereYear('data_inicio', now()->year)
            ->sum('preco_mensal');
        $instanciasLigadas = InstanciaWhatsApp::where('estado', 'conectada')->count();
        $totalInstancias = InstanciaWhatsApp::count();

        $alertas = collect();
        $trialsExpirando = Tenant::where('estado', 'trial')
            ->where('trial_termina_em', '<=', now()->addDays(3))
            ->where('trial_termina_em', '>', now())
            ->count();
        if ($trialsExpirando > 0) {
            $alertas->push("{$trialsExpirando} loja(s) com trial a expirar em 3 dias");
        }

        $lojasSuspensas = Tenant::where('estado', 'suspenso')->count();
        if ($lojasSuspensas > 0) {
            $alertas->push("{$lojasSuspensas} loja(s) suspensa(s)");
        }

        return view('super.dashboard', compact(
            'totalLojas', 'lojasActivas', 'receitaMes',
            'instanciasLigadas', 'totalInstancias', 'alertas'
        ));
    }

    public function lojas()
    {
        $lojas = Tenant::withCount(['produtos', 'encomendas'])
            ->with('instancias')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('super.lojas.index', compact('lojas'));
    }

    public function criar()
    {
        return view('super.lojas.criar');
    }

    public function guardar(Request $request)
    {
        $validated = $request->validate([
            'nome_loja' => 'required|string|max:255',
            'email_dono' => 'required|email|unique:tenants,email_dono',
            'telefone_dono' => 'nullable|string|max:20',
            'password' => ['required', 'string', 'min:8', 'confirmed', new \App\Rules\StrongPassword],
            'plano' => 'required|in:basic,pro,enterprise',
            'dias_trial' => 'nullable|integer|min:0|max:30',
        ]);

        DB::beginTransaction();

        try {
            $diasTrial = $validated['dias_trial'] ?? 7;

            $tenant = Tenant::create([
                'nome_loja' => $validated['nome_loja'],
                'email_dono' => $validated['email_dono'],
                'telefone_dono' => $validated['telefone_dono'] ?? null,
                'plano' => $validated['plano'],
                'estado' => $diasTrial > 0 ? 'trial' : 'activo',
                'trial_termina_em' => $diasTrial > 0 ? now()->addDays($diasTrial) : null,
                'max_produtos' => $this->getMaxProdutos($validated['plano']),
                'max_numeros' => $this->getMaxNumeros($validated['plano']),
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['nome_loja'],
                'email' => $validated['email_dono'],
                'password' => Hash::make($validated['password']),
                'role' => 'admin',
            ]);

            Subscricao::create([
                'tenant_id' => $tenant->id,
                'plano' => $validated['plano'],
                'preco_mensal' => 0,
                'data_inicio' => now(),
                'data_fim' => $diasTrial > 0 ? now()->addDays($diasTrial) : now()->addMonth(),
                'estado' => 'activa',
                'metodo_pagamento' => 'manual',
            ]);

            DB::commit();

            try {
                Mail::to($user->email)->queue(new BoasVindasMail($user, $tenant));
            } catch (\Exception $e) {
                Log::error("Erro ao enviar email de boas-vindas: " . $e->getMessage());
            }

            Log::info("Loja criada pelo super admin", [
                'tenant_id' => $tenant->id,
                'nome' => $tenant->nome_loja,
                'email' => $user->email,
                'plano' => $tenant->plano,
            ]);

            return redirect('/super/lojas/' . $tenant->id)
                ->with('success', "Loja \"{$tenant->nome_loja}\" criada com sucesso!");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao criar loja: " . $e->getMessage());
            return back()->withInput()->with('error', 'Erro ao criar loja. Tenta novamente.');
        }
    }

    public function detalhe(Tenant $tenant)
    {
        $tenant->load(['users', 'instancias', 'subscricoes']);
        $tenant->loadCount(['produtos', 'encomendas', 'categorias']);

        $encomendasRecentes = $tenant->encomendas()
            ->with('produto')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('super.lojas.detalhe', compact('tenant', 'encomendasRecentes'));
    }

    public function alterarEstado(Request $request, Tenant $tenant)
    {
        $request->validate([
            'estado' => 'required|in:activo,suspenso,cancelado',
        ]);

        $tenant->update(['estado' => $request->estado]);

        return back()->with('success', "Estado alterado para {$request->estado}.");
    }

    public function renovarSubscricao(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plano' => 'required|in:basic,pro,enterprise',
            'preco_mensal' => 'required|numeric|min:0',
            'metodo_pagamento' => 'nullable|in:mpesa,transferencia,manual',
            'referencia_pagamento' => 'nullable|string|max:255',
        ]);

        $planos = [
            'basic' => ['max_produtos' => 50, 'max_numeros' => 1],
            'pro' => ['max_produtos' => 500, 'max_numeros' => 3],
            'enterprise' => ['max_produtos' => 99999, 'max_numeros' => 99999],
        ];

        $tenant->subscricoes()->where('estado', 'activa')->update(['estado' => 'cancelada']);

        Subscricao::create([
            'tenant_id' => $tenant->id,
            'plano' => $validated['plano'],
            'preco_mensal' => $validated['preco_mensal'],
            'data_inicio' => now(),
            'data_fim' => now()->addMonth(),
            'estado' => 'activa',
            'metodo_pagamento' => $validated['metodo_pagamento'] ?? null,
            'referencia_pagamento' => $validated['referencia_pagamento'] ?? null,
        ]);

        $planoInfo = $planos[$validated['plano']];
        $tenant->update([
            'plano' => $validated['plano'],
            'estado' => 'activo',
            'max_produtos' => $planoInfo['max_produtos'],
            'max_numeros' => $planoInfo['max_numeros'],
        ]);

        return back()->with('success', 'Subscrição renovada!');
    }

    public function receita()
    {
        $receitaPorMes = Subscricao::where('estado', 'activa')
            ->select(
                DB::raw("TO_CHAR(data_inicio, 'YYYY-MM') as mes"),
                DB::raw("SUM(preco_mensal) as total"),
                'plano'
            )
            ->groupBy('mes', 'plano')
            ->orderBy('mes')
            ->get()
            ->groupBy('mes');

        return view('super.receita', compact('receitaPorMes'));
    }

    public function instancias()
    {
        $instancias = InstanciaWhatsApp::with('tenant')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('super.instancias', compact('instancias'));
    }

    private function getMaxProdutos(string $plano): int
    {
        return match ($plano) {
            'pro' => 500,
            'enterprise' => 99999,
            default => 50,
        };
    }

    private function getMaxNumeros(string $plano): int
    {
        return match ($plano) {
            'pro' => 3,
            'enterprise' => 99999,
            default => 1,
        };
    }
}
