<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstanciaWhatsApp;
use App\Models\Subscricao;
use App\Models\Tenant;
use App\Models\User;
use App\Services\WahaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminLojaController extends Controller
{
    private WahaService $wahaService;

    public function __construct(WahaService $wahaService)
    {
        $this->wahaService = $wahaService;
    }

    public function criar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nome_loja'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'telefone'   => 'required|string|max:20',
            'plano'      => 'nullable|string|in:basic,pro,enterprise',
            'dias_trial' => 'nullable|integer|min:1|max:90',
        ]);

        $plano = $validated['plano'] ?? 'basic';
        $diasTrial = $validated['dias_trial'] ?? 7;

        $maxProdutos = match ($plano) {
            'pro'       => 500,
            'enterprise'=> 9999,
            default     => 50,
        };

        $precoMensal = match ($plano) {
            'pro'       => 29.99,
            'enterprise'=> 99.99,
            default     => 9.99,
        };

        $loginCode = $this->generatePin();

        try {
            $tenant = DB::transaction(function () use ($validated, $plano, $diasTrial, $maxProdutos, $loginCode) {
                $tenant = Tenant::create([
                    'nome_loja'         => $validated['nome_loja'],
                    'email_dono'        => $validated['email'],
                    'telefone_dono'     => $validated['telefone'],
                    'plano'             => $plano,
                    'estado'            => 'trial',
                    'trial_termina_em'  => now()->addDays($diasTrial),
                    'max_produtos'      => $maxProdutos,
                    'max_numeros'       => 1,
                    'activo'            => true,
                ]);

                User::create([
                    'tenant_id'   => $tenant->id,
                    'name'        => $validated['nome_loja'],
                    'email'       => $validated['email'],
                    'password'    => Hash::make(Str::random(32)),
                    'role'        => 'admin',
                    'login_code'  => $loginCode,
                ]);

                return $tenant;
            });

            Subscricao::create([
                'tenant_id'         => $tenant->id,
                'plano'             => $plano,
                'preco_mensal'      => $precoMensal,
                'data_inicio'       => now()->toDateString(),
                'data_fim'          => now()->addDays($diasTrial)->toDateString(),
                'estado'            => 'activa',
                'metodo_pagamento'  => 'trial',
            ]);

            InstanciaWhatsApp::create([
                'tenant_id'     => $tenant->id,
                'nome_instancia'=> "loja_{$tenant->id}",
                'waha_session'  => "loja-{$tenant->id}",
                'waha_url'      => config('services.waha.url'),
                'estado'        => 'aguarda_qr',
            ]);

            try {
                $resultado = $this->wahaService->criarInstancia($tenant->id);

                if (!$resultado['sucesso']) {
                    Log::warning("Instancia WAHA nao criada via admin", [
                        'tenant_id' => $tenant->id,
                        'erro' => $resultado['erro'] ?? 'desconhecido',
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Erro ao criar instancia WAHA via admin", [
                    'tenant_id' => $tenant->id,
                    'erro' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'sucesso' => true,
                'loja' => [
                    'id'        => $tenant->id,
                    'nome'      => $tenant->nome_loja,
                    'tenant_id' => $tenant->id,
                ],
                'credenciais' => [
                    'email'      => $validated['email'],
                    'login_code' => $loginCode,
                    'login_url'  => config('app.url') . '/login',
                ],
                'whatsapp' => [
                    'tenant_id' => $tenant->id,
                    'session'   => "loja-{$tenant->id}",
                    'nota'      => "Instancia WAHA criada automaticamente",
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'sucesso' => false,
                'erro'    => 'Dados invalidos.',
                'erros'   => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao criar loja', ['error' => $e->getMessage()]);
            return response()->json([
                'sucesso' => false,
                'erro'    => 'Erro interno ao criar loja.',
            ], 500);
        }
    }

    public function listar(): JsonResponse
    {
        $lojas = Tenant::select('id', 'nome_loja', 'email_dono', 'plano', 'estado', 'created_at')
            ->orderBy('id')
            ->get()
            ->map(function ($l) {
                return [
                    'id'        => $l->id,
                    'nome_loja' => $l->nome_loja,
                    'email_dono'=> $l->email_dono,
                    'plano'     => $l->plano,
                    'estado'    => $l->estado,
                    'criado_em' => $l->created_at->toDateTimeString(),
                ];
            });

        return response()->json([
            'sucesso' => true,
            'lojas'   => $lojas,
        ]);
    }

    public function bannerGlobal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_ids' => 'nullable|array',
            'tenant_ids.*' => 'integer|exists:tenants,id',
            'activo' => 'required|boolean',
            'titulo' => 'required_if:activo,true|nullable|string|max:100',
            'texto' => 'nullable|string|max:255',
            'cor' => 'nullable|string|max:7',
        ]);

        if ($validated['activo'] && empty($validated['titulo'])) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Titulo e obrigatorio quando o banner esta activo.',
            ], 422);
        }

        $query = Tenant::query();

        if (!empty($validated['tenant_ids'])) {
            $query->whereIn('id', $validated['tenant_ids']);
        }

        $tenants = $query->get();

        $actualizados = 0;
        foreach ($tenants as $tenant) {
            $tenant->update([
                'banner_global_activo' => $validated['activo'],
                'banner_global_titulo' => $validated['titulo'] ?? null,
                'banner_global_texto' => $validated['texto'] ?? null,
                'banner_global_cor' => $validated['cor'] ?? '#2563EB',
            ]);
            $actualizados++;
        }

        return response()->json([
            'sucesso' => true,
            'mensagem' => "Banner global actualizado em {$actualizados} loja(s).",
            'lojas_actualizadas' => $actualizados,
        ]);
    }

    private function generatePin(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function eliminar(int $id): JsonResponse
    {
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return response()->json([
                'sucesso' => false,
                'erro'    => 'Loja nao encontrada.',
            ], 404);
        }

        try {
            $this->wahaService->apagarInstancia($tenant->id);
        } catch (\Exception $e) {
            Log::warning("Erro ao apagar instancia WAHA ao eliminar loja", [
                'tenant_id' => $tenant->id,
                'erro' => $e->getMessage(),
            ]);
        }

        $tenant->instancias()->delete();
        $tenant->users()->delete();
        $tenant->subscricoes()->delete();
        $tenant->delete();

        return response()->json([
            'sucesso'   => true,
            'mensagem'  => "Loja \"{$tenant->nome_loja}\" eliminada.",
        ]);
    }

    public function eliminarTodas(): JsonResponse
    {
        $tenants = Tenant::where('nome_loja', '!=', 'mozdv')->get();

        $eliminadas = 0;
        foreach ($tenants as $tenant) {
            try {
                $this->wahaService->apagarInstancia($tenant->id);
            } catch (\Exception $e) {
                Log::warning("Erro ao apagar instancia WAHA", [
                    'tenant_id' => $tenant->id,
                    'erro' => $e->getMessage(),
                ]);
            }

            $tenant->instancias()->delete();
            $tenant->users()->delete();
            $tenant->subscricoes()->delete();
            $tenant->delete();
            $eliminadas++;
        }

        return response()->json([
            'sucesso'   => true,
            'mensagem'  => "{$eliminadas} loja(s) eliminada(s).",
        ]);
    }

    public function criarInstancia(int $id): JsonResponse
    {
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return response()->json([
                'sucesso' => false,
                'erro'    => 'Loja nao encontrada.',
            ], 404);
        }

        $instancia = $tenant->instancias()->first();

        if ($instancia) {
            $instancia->update([
                'waha_url' => config('services.waha.url'),
                'waha_session' => "loja-{$tenant->id}",
            ]);
        } else {
            InstanciaWhatsApp::create([
                'tenant_id'     => $tenant->id,
                'nome_instancia'=> "loja_{$tenant->id}",
                'waha_session'  => "loja-{$tenant->id}",
                'waha_url'      => config('services.waha.url'),
                'estado'        => 'aguarda_qr',
            ]);
        }

        try {
            $resultado = $this->wahaService->criarInstancia($tenant->id);

            if (!$resultado['sucesso']) {
                return response()->json([
                    'sucesso' => false,
                    'erro'    => "Falha ao criar instancia WAHA: {$resultado['erro']}",
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error("Erro ao criar instancia WAHA", [
                'tenant_id' => $tenant->id,
                'erro' => $e->getMessage(),
            ]);

            return response()->json([
                'sucesso' => false,
                'erro'    => 'Erro ao comunicar com WAHA.',
            ], 500);
        }

        return response()->json([
            'sucesso'     => true,
            'mensagem'    => 'Instancia WAHA criada.',
            'instancia_id'=> $instancia?->id,
            'session'     => "loja-{$tenant->id}",
        ]);
    }
}
