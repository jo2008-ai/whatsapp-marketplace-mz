<?php

namespace App\Http\Controllers;

use App\Models\InstanciaWhatsApp;
use App\Models\Tenant;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $lojas = Tenant::withCount(['produtos', 'encomendas'])->get();
        $instanciasLigadas = InstanciaWhatsApp::where('estado', 'conectada')->count();
        $totalInstancias = InstanciaWhatsApp::count();

        return view('super.dashboard', compact('lojas', 'instanciasLigadas', 'totalInstancias'));
    }

    public function lojas()
    {
        $lojas = Tenant::withCount(['produtos', 'encomendas'])
            ->with('instancias')
            ->orderBy('id')
            ->get();

        return view('super.lojas.index', compact('lojas'));
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['users', 'instancias']);
        $tenant->loadCount(['produtos', 'encomendas', 'categorias']);

        $encomendasRecentes = $tenant->encomendas()
            ->with('produto')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('super.lojas.detalhe', compact('tenant', 'encomendasRecentes'));
    }

    public function toggleActivo(Tenant $tenant)
    {
        $tenant->update(['activo' => !$tenant->activo]);

        $estado = $tenant->activo ? 'activada' : 'desactivada';
        return back()->with('success', "Loja \"{$tenant->nome_loja}\" {$estado}.");
    }
}
