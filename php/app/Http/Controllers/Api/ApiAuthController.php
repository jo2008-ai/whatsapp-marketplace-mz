<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Subscricao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ApiAuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return $this->unauthorized('Email ou password incorretos.');
        }

        $user = Auth::user();
        $token = $user->createToken('mobile-app')->plainTextToken;

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'tenant_id' => $user->tenant_id,
                'tenant_nome' => $user->tenant?->nome_loja,
            ],
            'token' => $token,
        ], 'Login efectuado com sucesso');
    }

    public function loginByCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = User::where('login_code', $validated['code'])->first();

        if (!$user) {
            return $this->unauthorized('Código inválido.');
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'tenant_id' => $user->tenant_id,
                'tenant_nome' => $user->tenant?->nome_loja,
            ],
            'token' => $token,
        ], 'Login efectuado com sucesso');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        return $this->success(null, 'Sessão encerrada.');
    }

    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'nome_loja' => 'required|string|max:255',
            ]);

            $planos = [
                'basic' => ['preco' => 500, 'max_produtos' => 50, 'max_numeros' => 1],
                'pro' => ['preco' => 1500, 'max_produtos' => 500, 'max_numeros' => 3],
                'enterprise' => ['preco' => 5000, 'max_produtos' => 99999, 'max_numeros' => 99999],
            ];

            $planoKey = $request->input('plano', 'basic');
            $plano = $planos[$planoKey] ?? $planos['basic'];

            $tenant = Tenant::create([
                'nome_loja' => $validated['nome_loja'],
                'email_dono' => $validated['email'],
                'telefone_dono' => $request->input('telefone', ''),
                'plano' => $planoKey,
                'estado' => 'trial',
                'trial_termina_em' => now()->addDays(7),
                'max_produtos' => $plano['max_produtos'],
                'max_numeros' => $plano['max_numeros'],
                'activo' => true,
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'admin',
            ]);

            Subscricao::create([
                'tenant_id' => $tenant->id,
                'plano' => $planoKey,
                'preco_mensal' => $plano['preco'],
                'data_inicio' => now(),
                'data_fim' => now()->addDays(7),
                'estado' => 'activa',
                'metodo_pagamento' => 'trial',
            ]);

            $token = $user->createToken('mobile-app')->plainTextToken;

            return $this->created([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'tenant_id' => $user->tenant_id,
                    'tenant_nome' => $tenant->nome_loja,
                ],
                'token' => $token,
            ], 'Conta criada com sucesso. Tens 7 dias de trial gratuito.');

        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erro ao criar conta', ['error' => $e->getMessage()]);
            return $this->error('Erro interno ao criar conta.', 500);
        }
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'tenant_id' => $user->tenant_id,
            'tenant' => $user->tenant ? [
                'id' => $user->tenant->id,
                'nome_loja' => $user->tenant->nome_loja,
                'activo' => $user->tenant->activo,
                'cor_primaria' => $user->tenant->cor_primaria,
            ] : null,
        ]);
    }
}
