@extends('layouts.painel')
@section('title', 'Encomendas')

@section('content')
<div class="mb-4">
    <form method="GET" class="flex gap-2 flex-wrap">
        <select name="estado" onchange="this.form.submit()" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">Todos estados</option>
            <option value="pendente" {{ request('estado') === 'pendente' ? 'selected' : '' }}>🟡 Pendente</option>
            <option value="confirmada" {{ request('estado') === 'confirmada' ? 'selected' : '' }}>🔵 Confirmada</option>
            <option value="entregue" {{ request('estado') === 'entregue' ? 'selected' : '' }}>🟢 Entregue</option>
            <option value="cancelada" {{ request('estado') === 'cancelada' ? 'selected' : '' }}>🔴 Cancelada</option>
        </select>
    </form>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left p-3 font-medium text-gray-600">#</th>
                    <th class="text-left p-3 font-medium text-gray-600">Cliente</th>
                    <th class="text-left p-3 font-medium text-gray-600">Produto</th>
                    <th class="text-left p-3 font-medium text-gray-600">Total</th>
                    <th class="text-left p-3 font-medium text-gray-600">Estado</th>
                    <th class="text-left p-3 font-medium text-gray-600 hidden md:table-cell">Data</th>
                    <th class="text-right p-3 font-medium text-gray-600">Acção</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($encomendas as $e)
                <tr class="hover:bg-gray-50">
                    <td class="p-3 text-gray-500">{{ $e->id }}</td>
                    <td class="p-3">
                        <div class="font-medium text-gray-800">{{ $e->nome_cliente ?? '—' }}</div>
                        <div class="text-xs text-gray-500">{{ $e->numero_cliente }}</div>
                    </td>
                    <td class="p-3 text-gray-700">
                        {{ $e->produto->nome ?? '—' }}
                        @php
                            $variantePartes = array_filter([
                                $e->cor_escolhida,
                                $e->tamanho_escolhido,
                            ]);
                        @endphp
                        @if(!empty($variantePartes))
                            <span class="text-xs text-gray-500"> — {{ implode(' · ', $variantePartes) }}</span>
                        @endif
                    </td>
                    <td class="p-3 font-semibold">{{ number_format($e->preco_total, 2) }} MZN</td>
                    <td class="p-3">
                        @php
                            $cores = ['pendente' => 'yellow', 'confirmada' => 'blue', 'entregue' => 'green', 'cancelada' => 'red'];
                            $c = $cores[$e->estado] ?? 'gray';
                        @endphp
                        <span class="px-2 py-1 bg-{{ $c }}-100 text-{{ $c }}-700 rounded-full text-xs">{{ ucfirst($e->estado) }}</span>
                    </td>
                    <td class="p-3 text-gray-500 hidden md:table-cell">{{ $e->created_at->format('d/m/Y H:i') }}</td>
                    <td class="p-3 text-right">
                        @if($e->estado === 'pendente')
                        <div class="flex gap-1 justify-end">
                            <form method="POST" action="/painel/encomendas/{{ $e->id }}/estado">
                                @csrf @method('PATCH')
                                <input type="hidden" name="estado" value="confirmada">
                                <button class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs hover:bg-blue-200">Confirmar</button>
                            </form>
                            <form method="POST" action="/painel/encomendas/{{ $e->id }}/estado">
                                @csrf @method('PATCH')
                                <input type="hidden" name="estado" value="cancelada">
                                <button class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs hover:bg-red-200">Cancelar</button>
                            </form>
                        </div>
                        @elseif($e->estado === 'confirmada')
                        <form method="POST" action="/painel/encomendas/{{ $e->id }}/estado">
                            @csrf @method('PATCH')
                            <input type="hidden" name="estado" value="entregue">
                            <button class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs hover:bg-green-200">Entregue</button>
                        </form>
                        @else
                        <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="p-8 text-center text-gray-400">Nenhuma encomenda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $encomendas->links() }}</div>
</div>
@endsection
