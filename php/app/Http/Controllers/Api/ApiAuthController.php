<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success(null, 'Sessão encerrada.');
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
                'plano' => $user->tenant->plano,
                'estado' => $user->tenant->estado,
                'cor_primaria' => $user->tenant->cor_primaria,
            ] : null,
        ]);
    }
}
