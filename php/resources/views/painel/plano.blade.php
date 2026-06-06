@extends('layouts.painel')
@section('title', 'Plano e Subscrição')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Plano Actual -->
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Plano Actual</h2>
        <div class="flex items-center gap-4">
            <span class="px-4 py-2 rounded-full text-sm font-bold
                {{ $tenant->plano === 'enterprise' ? 'bg-purple-100 text-purple-700' : ($tenant->plano === 'pro' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') }}">
                {{ ucfirst($tenant->plano) }}
            </span>
            <div>
                <p class="text-sm text-gray-600">
                    {{ $tenant->max_produtos }} produtos · {{ $tenant->max_numeros }} número(s)
                </p>
                @if($subscricao)
                    <p class="text-xs text-gray-400">
                        Válido até {{ $subscricao->data_fim->format('d/m/Y') }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    <!-- Planos Disponíveis -->
    <div class="grid md:grid-cols-3 gap-4">
        @foreach($planos as $key => $plano)
        <div class="bg-white rounded-xl shadow p-6 {{ $tenant->plano === $key ? 'ring-2 ring-blue-500' : '' }}">
            <div class="text-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">{{ $plano['nome'] }}</h3>
                <p class="text-sm text-gray-500">{{ $plano['descricao'] }}</p>
                <div class="mt-3">
                    <span class="text-3xl font-bold text-blue-600">{{ number_format($plano['preco'], 0, ',', '.') }}</span>
                    <span class="text-sm text-gray-500"> MZN/mês</span>
                </div>
            </div>
            <ul class="space-y-2 text-sm text-gray-600 mb-6">
                <li>✅ Até {{ $plano['max_produtos'] }} produtos</li>
                <li>✅ {{ $plano['max_numeros'] }} número(s) WhatsApp</li>
                <li>✅ Bot WhatsApp ilimitado</li>
                <li>✅ Suporte por email</li>
            </ul>
            @if($tenant->plano === $key)
                <div class="text-center py-2 bg-gray-100 rounded-lg text-gray-500 text-sm font-medium">
                    Plano actual
                </div>
            @elseif($tenant->plano !== 'enterprise' && ($key === 'pro' || $key === 'enterprise'))
                <button onclick="document.getElementById('upgradeModal').classList.remove('hidden'); document.getElementById('upgradePlano').value='{{ $key }}'; document.getElementById('upgradePreco').textContent='{{ number_format($plano['preco'], 0, ',', '.') }} MZN';"
                        class="w-full py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">
                    Fazer upgrade
                </button>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Histórico de Subscrições -->
    @if($tenant->subscricoes->count() > 0)
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold text-gray-800">Histórico de Subscrições</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left p-3 font-medium text-gray-600">Plano</th>
                    <th class="text-left p-3 font-medium text-gray-600">Preço</th>
                    <th class="text-left p-3 font-medium text-gray-600">Estado</th>
                    <th class="text-left p-3 font-medium text-gray-600">Período</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($tenant->subscricoes()->orderByDesc('created_at')->limit(5)->get() as $sub)
                <tr>
                    <td class="p-3 font-medium">{{ ucfirst($sub->plano) }}</td>
                    <td class="p-3">{{ number_format($sub->preco_mensal, 0, ',', '.') }} MZN</td>
                    <td class="p-3">
                        @php
                            $estadoCores = ['activa' => 'green', 'cancelada' => 'gray', 'pendente_pagamento' => 'yellow'];
                            $c = $estadoCores[$sub->estado] ?? 'gray';
                        @endphp
                        <span class="px-2 py-1 bg-{{ $c }}-100 text-{{ $c }}-700 rounded-full text-xs">
                            {{ ucfirst(str_replace('_', ' ', $sub->estado)) }}
                        </span>
                    </td>
                    <td class="p-3 text-gray-500">
                        {{ $sub->data_inicio->format('d/m/Y') }} — {{ $sub->data_fim->format('d/m/Y') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<!-- Modal de Upgrade -->
<div id="upgradeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Fazer Upgrade</h3>
        <form method="POST" action="/painel/plano/upgrade">
            @csrf
            <input type="hidden" name="plano" id="upgradePlano">

            <div class="mb-4 p-3 bg-blue-50 rounded-lg">
                <p class="text-sm text-blue-800">
                    Plano: <strong id="upgradePlanoNome"></strong> — <span id="upgradePreco"></span>
                </p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Referência M-Pesa *</label>
                <input type="text" name="referencia_pagamento" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                       placeholder="Número da transacção M-Pesa">
                <p class="text-xs text-gray-500 mt-1">
                    Envia o valor para <strong>841234567</strong> e insere o número da referência.
                </p>
                @error('referencia_pagamento')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('upgradeModal').classList.add('hidden')"
                        class="flex-1 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit"
                        class="flex-1 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">
                    Enviar Pedido
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
