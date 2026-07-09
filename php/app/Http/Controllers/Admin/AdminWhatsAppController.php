<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstanciaWhatsApp;
use App\Models\Tenant;
use App\Services\EvolutionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AdminWhatsAppController extends Controller
{
    private EvolutionService $evolutionService;

    public function __construct(EvolutionService $evolutionService)
    {
        $this->evolutionService = $evolutionService;
    }

    public function listar(): JsonResponse
    {
        $instancias = InstanciaWhatsApp::with('tenant:id,nome_loja')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($inst) {
                $estado = $this->evolutionService->obterEstado($inst->tenant_id, $inst->waha_url);

                return [
                    'id' => $inst->id,
                    'tenant_id' => $inst->tenant_id,
                    'tenant_nome' => $inst->tenant->nome_loja ?? 'N/A',
                    'nome_instancia' => $inst->nome_instancia,
                    'waha_session' => $inst->waha_session,
                    'estado_db' => $inst->estado,
                    'estado_evolution' => $estado,
                    'numero_whatsapp' => $inst->numero_whatsapp,
                    'conectada_em' => $inst->conectada_em instanceof Carbon ? $inst->conectada_em->toDateTimeString() : $inst->conectada_em,
                    'actualizado_em' => $inst->updated_at instanceof Carbon ? $inst->updated_at->toDateTimeString() : $inst->updated_at,
                ];
            });

        return response()->json([
            'sucesso' => true,
            'instancias' => $instancias,
        ]);
    }

    public function criar(int $tenantId): JsonResponse
    {
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Loja nao encontrada.',
            ], 404);
        }

        $instancia = $tenant->instancias()->first();

        if (! $instancia) {
            $instancia = InstanciaWhatsApp::create([
                'tenant_id' => $tenant->id,
                'nome_instancia' => "loja_{$tenant->id}",
                'waha_session' => "loja-{$tenant->id}",
                'waha_url' => config('services.evolution.url'),
                'estado' => 'aguarda_qr',
            ]);
        } else {
            $instancia->update([
                'waha_url' => config('services.evolution.url'),
                'waha_session' => "loja-{$tenant->id}",
            ]);
        }

        try {
            $resultado = $this->evolutionService->criarInstancia($tenant->id, $instancia->waha_url);

            if (! $resultado['sucesso']) {
                return response()->json([
                    'sucesso' => false,
                    'erro' => 'Falha ao criar instancia Evolution: '.($resultado['erro'] ?? 'desconhecido'),
                ], 500);
            }

            return response()->json([
                'sucesso' => true,
                'mensagem' => "Instancia Evolution criada para {$tenant->nome_loja}.",
                'instancia_id' => $instancia->id,
                'session' => "loja-{$tenant->id}",
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao criar instancia Evolution via admin', [
                'tenant_id' => $tenant->id,
                'erro' => $e->getMessage(),
            ]);

            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro ao comunicar com Evolution API.',
            ], 500);
        }
    }

    public function apagar(int $tenantId): JsonResponse
    {
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Loja nao encontrada.',
            ], 404);
        }

        try {
            $instancia = $tenant->instancias()->first();
            $this->evolutionService->apagarInstancia($tenant->id, $instancia?->waha_url);
        } catch (\Exception $e) {
            Log::warning('Erro ao apagar instancia Evolution', [
                'tenant_id' => $tenant->id,
                'erro' => $e->getMessage(),
            ]);
        }

        $tenant->instancias()->delete();

        return response()->json([
            'sucesso' => true,
            'mensagem' => "Instancia Evolution apagada para {$tenant->nome_loja}.",
        ]);
    }

    public function estado(int $tenantId): JsonResponse
    {
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Loja nao encontrada.',
            ], 404);
        }

        $instancia = $tenant->instancias()->first();
        $estado = $this->evolutionService->obterEstado($tenant->id, $instancia?->waha_url);

        return response()->json([
            'sucesso' => true,
            'tenant_id' => $tenant->id,
            'tenant_nome' => $tenant->nome_loja,
            'session' => "loja-{$tenant->id}",
            'estado' => $estado,
        ]);
    }
}
