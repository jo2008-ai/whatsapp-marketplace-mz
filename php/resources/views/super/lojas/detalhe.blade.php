@extends('layouts.super')
@section('title', $tenant->nome_loja)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Info -->
    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-semibold text-gray-800 mb-3">Informações</h3>
        <div class="space-y-2 text-sm">
            <p><strong>Email:</strong> {{ $tenant->email_dono }}</p>
            <p><strong>Telefone:</strong> {{ $tenant->telefone_dono ?? '—' }}</p>
            <p><strong>UUID:</strong> <code class="text-xs bg-gray-100 px-1 rounded">{{ $tenant->uuid }}</code></p>
            <p><strong>Criado:</strong> {{ $tenant->created_at->format('d/m/Y H:i') }}</p>
            <p><strong>Produtos:</strong> {{ $tenant->produtos_count }}</p>
            <p><strong>Categorias:</strong> {{ $tenant->categorias_count }}</p>
            <p><strong>Encomendas:</strong> {{ $tenant->encomendas_count }}</p>
        </div>

        <hr class="my-4">

        <!-- Alterar estado -->
        <h4 class="font-medium text-gray-700 mb-2">Alterar Estado</h4>
        <form method="POST" action="/super/lojas/{{ $tenant->id }}/estado" class="flex gap-2">
            @csrf @method('PATCH')
            <select name="estado" class="flex-1 px-3 py-2 border rounded-lg text-sm">
                <option value="activo" {{ $tenant->estado === 'activo' ? 'selected' : '' }}>Activo</option>
                <option value="suspenso" {{ $tenant->estado === 'suspenso' ? 'selected' : '' }}>Suspenso</option>
                <option value="cancelado" {{ $tenant->estado === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
            </select>
            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Salvar</button>
        </form>
    </div>

    <!-- Renovar subscrição -->
    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-semibold text-gray-800 mb-3">Renovar Subscrição</h3>
        <form method="POST" action="/super/lojas/{{ $tenant->id }}/subscricao" class="space-y-3">
            @csrf
            <select name="plano" class="w-full px-3 py-2 border rounded-lg text-sm">
                <option value="basic">Basic — 500 MZN</option>
                <option value="pro">Pro — 1.500 MZN</option>
                <option value="enterprise">Enterprise — 5.000 MZN</option>
            </select>
            <input type="number" name="preco_mensal" placeholder="Preço" step="0.01" class="w-full px-3 py-2 border rounded-lg text-sm">
            <select name="metodo_pagamento" class="w-full px-3 py-2 border rounded-lg text-sm">
                <option value="">Método de pagamento</option>
                <option value="mpesa">M-Pesa</option>
                <option value="transferencia">Transferência</option>
                <option value="manual">Manual</option>
            </select>
            <input type="text" name="referencia_pagamento" placeholder="Referência (opcional)" class="w-full px-3 py-2 border rounded-lg text-sm">
            <button class="w-full py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">Renovar</button>
        </form>
    </div>

    <!-- Instâncias -->
    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-semibold text-gray-800 mb-3">Instâncias WhatsApp</h3>
        @forelse($tenant->instancias as $inst)
        <div class="border rounded-lg p-3 mb-2">
            <div class="flex justify-between">
                <span class="text-sm font-medium">{{ $inst->nome_instancia }}</span>
                @if($inst->estado === 'conectada')
                    <span class="text-green-600 text-xs">🟢</span>
                @else
                    <span class="text-red-500 text-xs">🔴</span>
                @endif
            </div>
            <div class="text-xs text-gray-500">{{ $inst->numero_whatsapp ?? 'Sem número' }}</div>
        </div>
        @empty
        <p class="text-sm text-gray-400">Sem instâncias.</p>
        @endforelse
    </div>
</div>

<!-- Encomendas recentes -->
<div class="mt-4 bg-white rounded-xl shadow overflow-hidden">
    <div class="p-4 border-b">
        <h3 class="font-semibold text-gray-800">Encomendas Recentes</h3>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="text-left p-3 font-medium text-gray-600">#</th>
                <th class="text-left p-3 font-medium text-gray-600">Cliente</th>
                <th class="text-left p-3 font-medium text-gray-600">Produto</th>
                <th class="text-left p-3 font-medium text-gray-600">Total</th>
                <th class="text-left p-3 font-medium text-gray-600">Estado</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($encomendasRecentes as $e)
            <tr>
                <td class="p-3 text-gray-500">{{ $e->id }}</td>
                <td class="p-3">{{ $e->nome_cliente ?? $e->numero_cliente }}</td>
                <td class="p-3">{{ $e->produto->nome ?? '—' }}</td>
                <td class="p-3 font-medium">{{ number_format($e->preco_total, 2) }} MZN</td>
                <td class="p-3"><span class="text-xs">{{ ucfirst($e->estado) }}</span></td>
            </tr>
            @empty
            <tr><td colspan="5" class="p-6 text-center text-gray-400">Sem encomendas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
