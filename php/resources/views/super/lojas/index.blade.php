@extends('layouts.super')
@section('title', 'Todas as Lojas')

@section('content')
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="p-4 border-b flex items-center justify-between">
        <h2 class="font-semibold text-gray-800">Lojas</h2>
        <a href="/super/lojas/criar"
           class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
            ➕ Criar Loja
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left p-3 font-medium text-gray-600">Loja</th>
                    <th class="text-left p-3 font-medium text-gray-600">Plano</th>
                    <th class="text-left p-3 font-medium text-gray-600">Estado</th>
                    <th class="text-left p-3 font-medium text-gray-600 hidden md:table-cell">Produtos</th>
                    <th class="text-left p-3 font-medium text-gray-600 hidden md:table-cell">Encomendas</th>
                    <th class="text-left p-3 font-medium text-gray-600 hidden lg:table-cell">WhatsApp</th>
                    <th class="text-right p-3 font-medium text-gray-600">Acção</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($lojas as $loja)
                <tr class="hover:bg-gray-50">
                    <td class="p-3">
                        <div class="font-medium text-gray-800">{{ $loja->nome_loja }}</div>
                        <div class="text-xs text-gray-500">{{ $loja->email_dono }}</div>
                    </td>
                    <td class="p-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium
                            {{ $loja->plano === 'enterprise' ? 'bg-purple-100 text-purple-700' : ($loja->plano === 'pro' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') }}">
                            {{ ucfirst($loja->plano) }}
                        </span>
                    </td>
                    <td class="p-3">
                        @php
                            $estadoCores = ['activo' => 'green', 'trial' => 'yellow', 'suspenso' => 'red', 'cancelado' => 'gray'];
                            $c = $estadoCores[$loja->estado] ?? 'gray';
                        @endphp
                        <span class="px-2 py-1 bg-{{ $c }}-100 text-{{ $c }}-700 rounded-full text-xs">{{ ucfirst($loja->estado) }}</span>
                    </td>
                    <td class="p-3 text-gray-600 hidden md:table-cell">{{ $loja->produtos_count }}</td>
                    <td class="p-3 text-gray-600 hidden md:table-cell">{{ $loja->encomendas_count }}</td>
                    <td class="p-3 hidden lg:table-cell">
                        @php $ligadas = $loja->instancias->where('estado', 'conectada')->count(); @endphp
                        <span class="{{ $ligadas > 0 ? 'text-green-600' : 'text-gray-400' }}">{{ $ligadas }}/{{ $loja->instancias->count() }}</span>
                    </td>
                    <td class="p-3 text-right">
                        <a href="/super/lojas/{{ $loja->id }}" class="text-blue-600 hover:underline text-sm">Ver</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="p-8 text-center text-gray-400">Nenhuma loja registada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $lojas->links() }}</div>
</div>
@endsection
