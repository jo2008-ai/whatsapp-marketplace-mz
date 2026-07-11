@extends('layouts.painel')
@section('title', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-5">
        <div class="text-sm text-gray-500">Total Produtos</div>
        <div class="text-3xl font-bold text-gray-800">{{ $totalProdutos }}</div>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <div class="text-sm text-gray-500">Encomendas Hoje</div>
        <div class="text-3xl font-bold text-blue-600">{{ $encomendasHoje }}</div>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <div class="text-sm text-gray-500">Pendentes</div>
        <div class="text-3xl font-bold text-yellow-600">{{ $encomendasPendentes }}</div>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <div class="text-sm text-gray-500">Stock Baixo</div>
        <div class="text-3xl font-bold {{ $stockCritico > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $stockCritico }}</div>
    </div>
</div>

@if(isset($produtosStockBaixo) && $produtosStockBaixo->count() > 0)
<div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold text-red-800">⚠️ {{ $produtosStockBaixo->count() }} {{ Str::plural('produto', $produtosStockBaixo->count()) }} com stock baixo</h3>
        <a href="/painel/stock" class="text-sm text-red-600 hover:underline font-medium">Ver todos</a>
    </div>
    @foreach($produtosStockBaixo->take(5) as $produto)
        <div class="flex items-center justify-between py-1 border-b border-red-100 last:border-0">
            <span class="text-red-700">{{ $produto->nome }}</span>
            <span class="text-red-600 font-bold">{{ $produto->stock }} {{ $produto->unidade }}</span>
        </div>
    @endforeach
</div>
@endif

<div class="bg-white rounded-xl shadow p-5">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Encomendas — Últimos 7 dias</h3>
    <canvas id="graficoEncomendas" height="100"></canvas>
</div>
@endsection

@push('scripts')
<script>
    new Chart(document.getElementById('graficoEncomendas'), {
        type: 'bar',
        data: {
            labels: @json($graficoLabels),
            datasets: [{
                label: 'Encomendas',
                data: @json($graficoDados),
                backgroundColor: '{{ $tenant->cor_primaria ?? "#2563EB" }}',
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
            plugins: { legend: { display: false } }
        }
    });
</script>
@endpush
