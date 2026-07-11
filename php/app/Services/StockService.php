<?php

namespace App\Services;

use App\Models\MovimentoStock;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockService
{
    public function registarEntrada(
        Produto $produto,
        int $quantidade,
        string $motivo = 'reposicao',
        ?int $utilizadorId = null,
    ): MovimentoStock {
        $stockAnterior = $produto->stock;

        $movimento = DB::transaction(function () use ($produto, $quantidade, $motivo, $utilizadorId, $stockAnterior) {
            $produto->increment('stock', $quantidade);
            $produto->refresh();

            return MovimentoStock::create([
                'tenant_id' => $produto->tenant_id,
                'produto_id' => $produto->id,
                'tipo' => MovimentoStock::TIPO_ENTRADA,
                'quantidade' => $quantidade,
                'stock_anterior' => $stockAnterior,
                'stock_actual' => $produto->stock,
                'motivo' => $motivo,
                'utilizador_id' => $utilizadorId,
            ]);
        });

        Log::info('Entrada de stock registada', [
            'produto_id' => $produto->id,
            'quantidade' => $quantidade,
            'stock_anterior' => $stockAnterior,
            'stock_actual' => $produto->fresh()->stock,
        ]);

        return $movimento;
    }

    public function registarSaida(
        Produto $produto,
        int $quantidade,
        ?int $encomendaId = null,
        string $motivo = 'venda',
    ): MovimentoStock {
        $stockAnterior = $produto->stock;

        $movimento = DB::transaction(function () use ($produto, $quantidade, $encomendaId, $motivo, $stockAnterior) {
            $produto->decrement('stock', $quantidade);
            $produto->refresh();

            return MovimentoStock::create([
                'tenant_id' => $produto->tenant_id,
                'produto_id' => $produto->id,
                'tipo' => MovimentoStock::TIPO_SAIDA,
                'quantidade' => $quantidade,
                'stock_anterior' => $stockAnterior,
                'stock_actual' => $produto->stock,
                'motivo' => $motivo,
                'referencia_id' => $encomendaId,
                'referencia_tipo' => $encomendaId ? 'encomenda' : null,
            ]);
        });

        Log::info('Saída de stock registada', [
            'produto_id' => $produto->id,
            'quantidade' => $quantidade,
            'stock_anterior' => $stockAnterior,
            'stock_actual' => $produto->fresh()->stock,
        ]);

        return $movimento;
    }

    public function registarDevolucao(
        Produto $produto,
        int $quantidade,
        ?int $encomendaId = null,
        string $motivo = 'devolucao_cliente',
    ): MovimentoStock {
        $stockAnterior = $produto->stock;

        $movimento = DB::transaction(function () use ($produto, $quantidade, $encomendaId, $motivo, $stockAnterior) {
            $produto->increment('stock', $quantidade);
            $produto->refresh();

            return MovimentoStock::create([
                'tenant_id' => $produto->tenant_id,
                'produto_id' => $produto->id,
                'tipo' => MovimentoStock::TIPO_DEVOLUCAO,
                'quantidade' => $quantidade,
                'stock_anterior' => $stockAnterior,
                'stock_actual' => $produto->stock,
                'motivo' => $motivo,
                'referencia_id' => $encomendaId,
                'referencia_tipo' => $encomendaId ? 'encomenda' : null,
            ]);
        });

        Log::info('Devolução de stock registada', [
            'produto_id' => $produto->id,
            'quantidade' => $quantidade,
            'stock_anterior' => $stockAnterior,
            'stock_actual' => $produto->fresh()->stock,
        ]);

        return $movimento;
    }

    public function ajustarStock(
        Produto $produto,
        int $novaQuantidade,
        string $motivo,
        ?int $utilizadorId = null,
    ): MovimentoStock {
        $stockAnterior = $produto->stock;
        $diferenca = $novaQuantidade - $stockAnterior;

        $movimento = DB::transaction(function () use ($produto, $novaQuantidade, $motivo, $utilizadorId, $stockAnterior, $diferenca) {
            $produto->update(['stock' => $novaQuantidade]);
            $produto->refresh();

            return MovimentoStock::create([
                'tenant_id' => $produto->tenant_id,
                'produto_id' => $produto->id,
                'tipo' => MovimentoStock::TIPO_AJUSTE,
                'quantidade' => abs($diferenca),
                'stock_anterior' => $stockAnterior,
                'stock_actual' => $novaQuantidade,
                'motivo' => $motivo,
                'utilizador_id' => $utilizadorId,
            ]);
        });

        Log::info('Ajuste de stock registado', [
            'produto_id' => $produto->id,
            'stock_anterior' => $stockAnterior,
            'stock_actual' => $novaQuantidade,
            'motivo' => $motivo,
        ]);

        return $movimento;
    }

    public function historico(
        int $produtoId,
        ?string $tipo = null,
        int $limite = 50,
    ): Collection {
        $query = MovimentoStock::where('produto_id', $produtoId)
            ->with(['utilizador:id,name', 'produto:id,nome,unidade']);

        if ($tipo) {
            $query->porTipo($tipo);
        }

        return $query->recentes($limite)->get();
    }

    public function historicoGeral(
        int $tenantId,
        ?string $tipo = null,
        int $limite = 50,
    ): Collection {
        $query = MovimentoStock::where('tenant_id', $tenantId)
            ->with(['utilizador:id,name', 'produto:id,nome,unidade']);

        if ($tipo) {
            $query->porTipo($tipo);
        }

        return $query->recentes($limite)->get();
    }

    public function relatorioStock(int $tenantId): array
    {
        $produtos = DB::table('produtos')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->select(
                DB::raw('COUNT(*) as total_produtos'),
                DB::raw('SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) as sem_stock'),
                DB::raw('SUM(CASE WHEN stock > 0 AND stock <= stock_minimo THEN 1 ELSE 0 END) as stock_baixo'),
                DB::raw('SUM(stock * custo_unitario) as valor_inventario')
            )
            ->first();

        return [
            'total_produtos' => (int) ($produtos->total_produtos ?? 0),
            'sem_stock' => (int) ($produtos->sem_stock ?? 0),
            'stock_baixo' => (int) ($produtos->stock_baixo ?? 0),
            'valor_inventario' => (float) ($produtos->valor_inventario ?? 0),
        ];
    }

    public function produtosStockBaixo(int $tenantId): Collection
    {
        return Produto::where('tenant_id', $tenantId)
            ->where('disponivel', true)
            ->where('alerta_stock_baixo', true)
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->where('stock', '>', 0)
            ->orderBy('stock')
            ->get();
    }

    public function produtosSemStock(int $tenantId): Collection
    {
        return Produto::where('tenant_id', $tenantId)
            ->where('disponivel', true)
            ->where('stock', '<=', 0)
            ->orderBy('nome')
            ->get();
    }
}
