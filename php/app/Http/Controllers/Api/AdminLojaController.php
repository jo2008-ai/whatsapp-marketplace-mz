<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstanciaWhatsApp;
use App\Models\Subscricao;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminLojaController extends Controller
{
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

        $password = Str::random(16);

        try {
            $tenant = DB::transaction(function () use ($validated, $plano, $diasTrial, $maxProdutos, $password) {
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
                    'tenant_id' => $tenant->id,
                    'name'      => $validated['nome_loja'],
                    'email'     => $validated['email'],
                    'password'  => $password,
                    'role'      => 'admin',
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
                'waha_session'  => "loja_{$tenant->id}",
                'estado'        => 'aguarda_qr',
            ]);

            return response()->json([
                'sucesso' => true,
                'loja' => [
                    'id'        => $tenant->id,
                    'nome'      => $tenant->nome_loja,
                    'tenant_id' => $tenant->id,
                ],
                'credenciais' => [
                    'email'     => $validated['email'],
                    'password'  => $password,
                    'login_url' => env('APP_URL') . '/login',
                ],
                'whatsapp' => [
                    'tenant_id' => $tenant->id,
                    'nota'      => "Use WAHA_URL_{$tenant->id} para ligar o WhatsApp desta loja",
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'sucesso' => false,
                'erro'    => $e->getMessage(),
                'erros'   => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'erro'    => 'Erro ao criar loja: ' . $e->getMessage(),
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
}
