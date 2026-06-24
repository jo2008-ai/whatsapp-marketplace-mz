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
            'banner_promo' => [
                'activo' => $tenant->banner_promo_activo,
                'titulo' => $tenant->banner_promo_titulo,
                'texto' => $tenant->banner_promo_texto,
                'cor' => $tenant->banner_promo_cor,
                'expira_em' => $tenant->banner_promo_expira_em?->toIso8601String(),
            ],
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
            'banner_promo_activo' => 'nullable|boolean',
            'banner_promo_titulo' => 'nullable|string|max:100',
            'banner_promo_texto' => 'nullable|string|max:255',
            'banner_promo_cor' => 'nullable|string|max:7',
            'banner_promo_expira_em' => 'nullable|date|after:now',
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
            'banner_promo' => [
                'activo' => $tenant->banner_promo_activo,
                'titulo' => $tenant->banner_promo_titulo,
                'texto' => $tenant->banner_promo_texto,
                'cor' => $tenant->banner_promo_cor,
                'expira_em' => $tenant->banner_promo_expira_em?->toIso8601String(),
            ],
        ], 'Definições guardadas com sucesso.');
    }

    public function bannerPromo(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'activo' => 'required|boolean',
            'titulo' => 'required_if:activo,true|nullable|string|max:100',
            'texto' => 'nullable|string|max:255',
            'cor' => 'nullable|string|max:7',
            'expira_em' => 'nullable|date|after:now',
        ]);

        if ($validated['activo'] && empty($validated['titulo'])) {
            return $this->error('Título é obrigatório quando o banner está activo.', 422);
        }

        $tenant->update([
            'banner_promo_activo' => $validated['activo'],
            'banner_promo_titulo' => $validated['titulo'] ?? null,
            'banner_promo_texto' => $validated['texto'] ?? null,
            'banner_promo_cor' => $validated['cor'] ?? '#FF6B35',
            'banner_promo_expira_em' => $validated['expira_em'] ?? null,
        ]);

        return $this->success([
            'activo' => $tenant->banner_promo_activo,
            'titulo' => $tenant->banner_promo_titulo,
            'texto' => $tenant->banner_promo_texto,
            'cor' => $tenant->banner_promo_cor,
            'expira_em' => $tenant->banner_promo_expira_em?->toIso8601String(),
        ], 'Banner de promoção actualizado com sucesso.');
    }
}
