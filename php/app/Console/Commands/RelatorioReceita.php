<?php

namespace App\Console\Commands;

use App\Models\Encomenda;
use App\Models\Subscricao;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RelatorioReceita extends Command
{
    protected $signature = 'marketplace:relatorio-receita {--mes= : Mês (1-12)} {--ano= : Ano (YYYY)}';
    protected $description = 'Gera relatório de receita mensal';

    public function handle(): int
    {
        $mes = $this->option('mes') ?: now()->month;
        $ano = $this->option('ano') ?: now()->year;

        $this->info("=== Relatório de Receita — {$mes}/{$ano} ===\n");

        // Receita por subscrições
        $receitaSubs = Subscricao::where('estado', 'activa')
            ->whereMonth('data_inicio', $mes)
            ->whereYear('data_inicio', $ano)
            ->select('plano', DB::raw('SUM(preco_mensal) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('plano')
            ->get();

        $this->info('Receita por Subscrições:');
        $this->table(
            ['Plano', 'Subscrições', 'Total (MZN)'],
            $receitaSubs->map(fn($r) => [ucfirst($r->plano), $r->count, number_format($r->total, 2)])
        );

        // Top lojas por encomendas
        $topLojas = Tenant::select('tenants.id', 'tenants.nome_loja')
            ->selectSub(
                Encomenda::whereColumn('encomendas.tenant_id', 'tenants.id')
                    ->whereMonth('created_at', $mes)
                    ->whereYear('created_at', $ano)
                    ->where('estado', '!=', 'cancelada')
                    ->select(DB::raw('COUNT(*)'))
            , 'total_encomendas')
            ->selectSub(
                Encomenda::whereColumn('encomendas.tenant_id', 'tenants.id')
                    ->whereMonth('created_at', $mes)
                    ->whereYear('created_at', $ano)
                    ->where('estado', '!=', 'cancelada')
                    ->select(DB::raw('SUM(preco_total)'))
            , 'receita')
            ->orderByDesc('receita')
            ->limit(10)
            ->get();

        $this->info("\nTop 10 Lojas por Receita:");
        $this->table(
            ['Loja', 'Encomendas', 'Receita (MZN)'],
            $topLojas->map(fn($l) => [$l->nome_loja, $l->total_encomendas ?? 0, number_format($l->receita ?? 0, 2)])
        );

        // Totais
        $totalSubs = $receitaSubs->sum('total');
        $totalEncomendas = $topLojas->sum('receita');

        $this->info("\nResumo:");
        $this->info("  Receita subscrições: " . number_format($totalSubs, 2) . " MZN");
        $this->info("  Receita encomendas: " . number_format($totalEncomendas, 2) . " MZN");
        $this->info("  Total: " . number_format($totalSubs + $totalEncomendas, 2) . " MZN");

        return self::SUCCESS;
    }
}
