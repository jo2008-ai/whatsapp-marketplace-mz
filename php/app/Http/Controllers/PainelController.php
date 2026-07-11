<?php

namespace App\Http\Controllers;

use App\Models\Encomenda;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PainelController extends Controller
{
    public function __construct(
        private StockService $stockService,
    ) {}

    /** @return \Illuminate\View\View */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $tenant = $user->tenant;
        if (! $tenant) {
            abort(401);
        }

        $totalProdutos = $tenant->produtos()->count();
        $encomendasHoje = $tenant->encomendas()->whereDate('created_at', today())->count();
        $encomendasPendentes = $tenant->encomendas()->where('estado', 'pendente')->count();
        $produtosStockBaixo = $this->stockService->produtosStockBaixo($tenant->id);
        $stockCritico = $produtosStockBaixo->count();

        // Encomendas últimos 7 dias
        $encomendasPorDia = $tenant->encomendas()
            ->where('created_at', '>=', now()->subDays(7))
            ->select(DB::raw("DATE(created_at) as data"), DB::raw("COUNT(*) as total"))
            ->groupBy('data')
            ->orderBy('data')
            ->get()
            ->pluck('total', 'data');

        $graficoLabels = [];
        $graficoDados = [];
        for ($i = 6; $i >= 0; $i--) {
            $data = now()->subDays($i)->format('Y-m-d');
            $graficoLabels[] = now()->subDays($i)->format('d/m');
            $graficoDados[] = $encomendasPorDia->get($data, 0);
        }

        return view('painel.dashboard', compact(
            'totalProdutos', 'encomendasHoje', 'encomendasPendentes',
            'stockCritico', 'produtosStockBaixo', 'graficoLabels', 'graficoDados', 'tenant'
        ));
    }

    /** @return \Illuminate\View\View */
    public function stock(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $tenant = $user->tenant;
        if (! $tenant) {
            abort(401);
        }

        $relatorio = $this->stockService->relatorioStock($tenant->id);
        $produtosStockBaixo = $this->stockService->produtosStockBaixo($tenant->id);
        $produtos = $tenant->produtos()->orderBy('nome')->get();
        $movimentos = $this->stockService->historicoGeral($tenant->id, null, 20);

        return view('painel.stock.index', compact(
            'relatorio', 'produtosStockBaixo', 'produtos', 'movimentos'
        ));
    }

    public function stockEntrada(Request $request, int $produtoId)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $tenant = $user->tenant;
        if (! $tenant) {
            abort(401);
        }

        $produto = $tenant->produtos()->find($produtoId);
        if (! $produto) {
            return redirect()->route('painel.stock')->with('error', 'Produto não encontrado.');
        }

        $request->validate([
            'quantidade' => 'required|integer|min:1',
            'motivo' => 'nullable|string|max:255',
        ]);

        $this->stockService->registarEntrada(
            $produto,
            $request->input('quantidade'),
            $request->input('motivo', 'reposicao'),
            $user->id,
        );

        return redirect()->route('painel.stock')->with('success', "Stock de \"{$produto->nome}\" actualizado com sucesso.");
    }
}
