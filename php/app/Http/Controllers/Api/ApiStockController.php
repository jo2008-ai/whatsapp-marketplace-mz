<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Produto;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiStockController extends Controller
{
    use ApiResponse;

    public function __construct(
        private StockService $stockService,
    ) {}

    public function movimentos(Request $request, int $produtoId): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $tenant = $user->tenant;
        if (! $tenant) {
            return $this->error('Sem loja associada.', 403);
        }

        $produto = $tenant->produtos()->find($produtoId);
        if (! $produto) {
            return $this->error('Produto não encontrado.', 404);
        }

        $tipo = $request->input('tipo');
        $limite = (int) $request->input('limite', 50);

        $movimentos = $this->stockService->historico($produtoId, $tipo, $limite);

        return $this->success($movimentos);
    }

    public function entrada(Request $request, int $produtoId): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $tenant = $user->tenant;
        if (! $tenant) {
            return $this->error('Sem loja associada.', 403);
        }

        $produto = $tenant->produtos()->find($produtoId);
        if (! $produto) {
            return $this->error('Produto não encontrado.', 404);
        }

        $request->validate([
            'quantidade' => 'required|integer|min:1',
            'motivo' => 'nullable|string|max:255',
            'custo_unitario' => 'nullable|numeric|min:0',
        ]);

        if ($request->has('custo_unitario')) {
            $produto->update(['custo_unitario' => $request->input('custo_unitario')]);
        }

        $movimento = $this->stockService->registarEntrada(
            $produto,
            $request->input('quantidade'),
            $request->input('motivo', 'reposicao'),
            $user->id,
        );

        return $this->success([
            'movimento' => $movimento->load(['utilizador:id,name', 'produto:id,nome,stock,unidade']),
            'stock_actual' => $produto->fresh()->stock,
        ], 'Entrada de stock registada com sucesso.');
    }

    public function ajuste(Request $request, int $produtoId): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $tenant = $user->tenant;
        if (! $tenant) {
            return $this->error('Sem loja associada.', 403);
        }

        $produto = $tenant->produtos()->find($produtoId);
        if (! $produto) {
            return $this->error('Produto não encontrado.', 404);
        }

        $request->validate([
            'quantidade_actual' => 'required|integer|min:0',
            'motivo' => 'required|string|max:255',
        ]);

        $movimento = $this->stockService->ajustarStock(
            $produto,
            $request->input('quantidade_actual'),
            $request->input('motivo'),
            $user->id,
        );

        return $this->success([
            'movimento' => $movimento->load(['utilizador:id,name', 'produto:id,nome,stock,unidade']),
            'stock_actual' => $produto->fresh()->stock,
        ], 'Ajuste de stock registado com sucesso.');
    }

    public function alertas(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $tenant = $user->tenant;
        if (! $tenant) {
            return $this->error('Sem loja associada.', 403);
        }

        $produtosStockBaixo = $this->stockService->produtosStockBaixo($tenant->id);
        $produtosSemStock = $this->stockService->produtosSemStock($tenant->id);

        return $this->success([
            'stock_baixo' => $produtosStockBaixo,
            'sem_stock' => $produtosSemStock,
            'total_alertas' => $produtosStockBaixo->count() + $produtosSemStock->count(),
        ]);
    }

    public function relatorio(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $tenant = $user->tenant;
        if (! $tenant) {
            return $this->error('Sem loja associada.', 403);
        }

        $relatorio = $this->stockService->relatorioStock($tenant->id);

        return $this->success($relatorio);
    }

    public function historico(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $tenant = $user->tenant;
        if (! $tenant) {
            return $this->error('Sem loja associada.', 403);
        }

        $tipo = $request->input('tipo');
        $limite = (int) $request->input('limite', 50);

        $movimentos = $this->stockService->historicoGeral($tenant->id, $tipo, $limite);

        return $this->success($movimentos);
    }
}
