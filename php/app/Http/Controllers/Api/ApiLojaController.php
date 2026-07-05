<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Encomenda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiLojaController extends Controller
{
    use ApiResponse;

    public function dashboard(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (!$tenant) {
            return $this->error('Sem loja associada.', 403);
        }

        $totalProdutos = $tenant->produtos()->count();
        $produtosDisponiveis = $tenant->produtos()->where('disponivel', true)->count();
        $encomendasHoje = $tenant->encomendas()->whereDate('created_at', today())->count();
        $encomendasPendentes = $tenant->encomendas()->where('estado', 'pendente')->count();
        $receitaMes = $tenant->encomendas()
            ->where('estado', '!=', 'cancelada')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('preco_total');

        $encomendasRecentes = $tenant->encomendas()
            ->with('produto:id,nome,preco')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'cliente' => $e->nome_cliente ?? $e->numero_cliente,
                'produto' => $e->produto?->nome,
                'total' => (float) $e->preco_total,
                'estado' => $e->estado,
                'data' => \Illuminate\Support\Carbon::parse($e->created_at)->format('d/m/Y H:i'),
            ]);

        return $this->success([
            'total_produtos' => $totalProdutos,
            'produtos_disponiveis' => $produtosDisponiveis,
            'encomendas_hoje' => $encomendasHoje,
            'encomendas_pendentes' => $encomendasPendentes,
            'receita_mes' => (float) $receitaMes,
            'encomendas_recentes' => $encomendasRecentes,
        ]);
    }
}
