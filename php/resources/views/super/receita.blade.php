@extends('layouts.super')
@section('title', 'Receita')

@section('content')
<div class="bg-white rounded-xl shadow p-5">
    <h3 class="font-semibold text-gray-800 mb-4">Receita Mensal por Plano</h3>

    @if($receitaPorMes->isNotEmpty())
    <canvas id="graficoReceita" height="120"></canvas>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left p-3 font-medium text-gray-600">Mês</th>
                    <th class="text-left p-3 font-medium text-gray-600">Basic</th>
                    <th class="text-left p-3 font-medium text-gray-600">Pro</th>
                    <th class="text-left p-3 font-medium text-gray-600">Enterprise</th>
                    <th class="text-right p-3 font-medium text-gray-600">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($receitaPorMes as $mes => $planos)
                @php
                    $basic = $planos->where('plano', 'basic')->sum('total');
                    $pro = $planos->where('plano', 'pro')->sum('total');
                    $enterprise = $planos->where('plano', 'enterprise')->sum('total');
                @endphp
                <tr>
                    <td class="p-3 font-medium">{{ $mes }}</td>
                    <td class="p-3">{{ number_format($basic, 0) }} MZN</td>
                    <td class="p-3">{{ number_format($pro, 0) }} MZN</td>
                    <td class="p-3">{{ number_format($enterprise, 0) }} MZN</td>
                    <td class="p-3 text-right font-bold">{{ number_format($basic + $pro + $enterprise, 0) }} MZN</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-gray-400">Sem dados de receita ainda.</p>
    @endif
</div>
@endsection

@push('scripts')
@if($receitaPorMes->isNotEmpty())
<script>
    const meses = @json($receitaPorMes->keys());
    const dadosBasic = @json($receitaPorMes->map(fn($p) => $p->where('plano', 'basic')->sum('total')));
    const dadosPro = @json($receitaPorMes->map(fn($p) => $p->where('plano', 'pro')->sum('total')));
    const dadosEnterprise = @json($receitaPorMes->map(fn($p) => $p->where('plano', 'enterprise')->sum('total')));

    new Chart(document.getElementById('graficoReceita'), {
        type: 'bar',
        data: {
            labels: meses,
            datasets: [
                { label: 'Basic', data: Object.values(dadosBasic), backgroundColor: '#9CA3AF' },
                { label: 'Pro', data: Object.values(dadosPro), backgroundColor: '#3B82F6' },
                { label: 'Enterprise', data: Object.values(dadosEnterprise), backgroundColor: '#8B5CF6' },
            ]
        },
        options: {
            responsive: true,
            scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
        }
    });
</script>
@endif
@endpush
