@extends('layouts.painel')
@section('title', 'Produtos')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
    <form method="GET" class="flex gap-2 flex-wrap">
        <select name="categoria_id" onchange="this.form.submit()" class="px-3 py-2 border rounded-lg text-sm">
            <option value="">Todas categorias</option>
            @foreach($categorias as $cat)
                <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>{{ $cat->nome }}</option>
            @endforeach
        </select>
        <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Pesquisar..."
               class="px-3 py-2 border rounded-lg text-sm">
        <button class="px-4 py-2 bg-gray-200 rounded-lg text-sm hover:bg-gray-300">Filtrar</button>
    </form>
    <a href="/painel/produtos/create" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 whitespace-nowrap">
        + Novo Produto
    </a>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left p-3 font-medium text-gray-600">Produto</th>
                    <th class="text-left p-3 font-medium text-gray-600 hidden sm:table-cell">Categoria</th>
                    <th class="text-left p-3 font-medium text-gray-600">Preço</th>
                    <th class="text-left p-3 font-medium text-gray-600">Stock</th>
                    <th class="text-left p-3 font-medium text-gray-600 hidden md:table-cell">Estado</th>
                    <th class="text-right p-3 font-medium text-gray-600">Acções</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($produtos as $p)
                <tr class="hover:bg-gray-50">
                    <td class="p-3">
                        <div class="font-medium text-gray-800">{{ $p->nome }}</div>
                        @if($p->vendedor)<div class="text-xs text-gray-500">{{ $p->vendedor->nome }}</div>@endif
                    </td>
                    <td class="p-3 hidden sm:table-cell text-gray-600">{{ $p->categoria->nome ?? '—' }}</td>
                    <td class="p-3 font-semibold">{{ number_format($p->preco, 2) }} MZN</td>
                    <td class="p-3">
                        <span class="{{ $p->stock < 3 ? 'text-red-600 font-bold' : 'text-gray-700' }}">{{ $p->stock }}</span>
                    </td>
                    <td class="p-3 hidden md:table-cell">
                        @if($p->disponivel)
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Disponível</span>
                        @else
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">Indisponível</span>
                        @endif
                        @if($p->destaque) <span class="text-yellow-500 ml-1">⭐</span> @endif
                    </td>
                    <td class="p-3 text-right">
                        <a href="/painel/produtos/{{ $p->id }}/edit" class="text-blue-600 hover:underline text-sm">Editar</a>
                        <form method="POST" action="/painel/produtos/{{ $p->id }}" class="inline" onsubmit="return confirm('Remover?')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline text-sm ml-2">Remover</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="p-8 text-center text-gray-400">Nenhum produto encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $produtos->links() }}</div>
</div>
@endsection
