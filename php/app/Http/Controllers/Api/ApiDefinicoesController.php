<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApiDefinicoesController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        return $this->success([
            'nome_loja' => $tenant->nome_loja,
            'logo_url' => $tenant->logo_url,
            'cor_primaria' => $tenant->cor_primaria,
            'mensagem_boas_vindas' => $tenant->mensagem_boas_vindas,
            'activo' => $tenant->activo,
        ]);
    }

    public function guardar(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'nome_loja' => 'required|string|max:255',
            'cor_primaria' => 'nullable|string|max:7',
            'mensagem_boas_vindas' => 'nullable|string|max:500',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            if ($tenant->logo_url) {
                Storage::disk('public')->delete($tenant->logo_url);
            }

            $path = $request->file('logo')->store('logos', 'public');
            $validated['logo_url'] = Storage::disk('public')->url($path);
            unset($validated['logo']);
        }

        unset($validated['logo']);

        $tenant->update($validated);

        return $this->success([
            'nome_loja' => $tenant->nome_loja,
            'logo_url' => $tenant->logo_url,
            'cor_primaria' => $tenant->cor_primaria,
            'mensagem_boas_vindas' => $tenant->mensagem_boas_vindas,
        ], 'Definições guardadas com sucesso.');
    }
}
