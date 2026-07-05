<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiPainelController extends Controller
{
    public function listarLojas(): JsonResponse
    {
        $lojas = Tenant::select('id', 'nome_loja', 'plano', 'estado', 'activo')
            ->with('instancias')
            ->orderBy('id')
            ->get()
            ->map(function ($l) {
                return [
                    'id'        => $l->id,
                    'nome_loja' => $l->nome_loja,
                    'plano'     => $l->plano,
                    'estado'    => $l->estado,
                    'activo'    => $l->activo,
'waha' => $l->instancias->first()->waha_url ?? '—',
                ];
            });

        return response()->json(['lojas' => $lojas]);
    }

    public function registrar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nome'      => 'required|string|max:255',
            'telefone'  => 'required|string|max:20',
            'tenant_id' => 'required|integer|exists:tenants,id',
        ]);

        $telefone = preg_replace('/[^0-9]/', '', $validated['telefone']);
        $email = $telefone . '@cliente.local';

        $existe = User::where('tenant_id', $validated['tenant_id'])
            ->where('telefone', $telefone)
            ->exists();

        if ($existe) {
            return response()->json([
                'sucesso' => false,
                'erro'    => 'Utilizador ja existe nesta loja.',
            ], 422);
        }

        $user = User::create([
            'tenant_id'  => $validated['tenant_id'],
            'name'       => $validated['nome'],
            'email'      => $email,
            'telefone'   => $telefone,
            'password'   => null,
            'role'       => 'user',
            'login_code' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
        ]);

        return response()->json([
            'sucesso' => true,
            'mensagem' => "Utilizador registado com sucesso.",
            'user' => [
                'id'    => $user->id,
                'nome'  => $user->name,
                'phone' => $user->telefone,
            ],
        ]);
    }
}
