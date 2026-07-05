<?php

namespace App\Http\Controllers;

use App\Models\Encomenda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PainelController extends Controller
{
    /** @return \Illuminate\View\View */
    public function dashboard(Request $request)
    {
        $tenant = $request->user()->tenant;

        $totalProdutos = $tenant->produtos()->count();
        $encomendasHoje = $tenant->encomendas()->whereDate('created_at', today())->count();
        $encomendasPendentes = $tenant->encomendas()->where('estado', 'pendente')->count();
        $stockCritico = $tenant->produtos()->where('stock', '<', 3)->where('disponivel', true)->count();

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
            'stockCritico', 'graficoLabels', 'graficoDados', 'tenant'
        ));
    }
}
