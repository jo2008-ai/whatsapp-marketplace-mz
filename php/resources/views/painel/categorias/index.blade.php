@extends('layouts.painel')
@section('title', 'Categorias')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Formulário -->
    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-semibold text-gray-800 mb-3">Nova Categoria</h3>
        <form method="POST" action="/painel/categorias" class="space-y-3">
            @csrf
            <input type="text" name="nome" placeholder="Nome" required
                   class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            <input type="text" name="descricao" placeholder="Descrição (opcional)"
                   class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            <div class="grid grid-cols-2 gap-2">
                <input type="text" name="icone" placeholder="Emoji 🍎" maxlength="10"
                       class="px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <input type="number" name="ordem" placeholder="Ordem" min="0" value="0"
                       class="px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            <button class="w-full py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Criar</button>
        </form>
    </div>

    <!-- Lista -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left p-3 font-medium text-gray-600">Categoria</th>
                    <th class="text-left p-3 font-medium text-gray-600">Produtos</th>
                    <th class="text-left p-3 font-medium text-gray-600">Estado</th>
                    <th class="text-right p-3 font-medium text-gray-600">Acções</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($categorias as $cat)
                <tr class="hover:bg-gray-50">
                    <td class="p-3">
                        <span class="mr-1">{{ $cat->icone }}</span>
                        <span class="font-medium">{{ $cat->nome }}</span>
                        @if($cat->descricao)<div class="text-xs text-gray-500">{{ $cat->descricao }}</div>@endif
                    </td>
                    <td class="p-3 text-gray-600">{{ $cat->produtos_count }}</td>
                    <td class="p-3">
                        @if($cat->ativo)
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Activa</span>
                        @else
                            <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded-full text-xs">Inactiva</span>
                        @endif
                    </td>
                    <td class="p-3 text-right">
                        <form method="POST" action="/painel/categorias/{{ $cat->id }}" class="inline">
                            @csrf @method('PUT')
                            <input type="hidden" name="nome" value="{{ $cat->nome }}">
                            <input type="hidden" name="ativo" value="{{ $cat->ativo ? 0 : 1 }}">
                            <button class="text-sm {{ $cat->ativo ? 'text-yellow-600' : 'text-green-600' }} hover:underline">
                                {{ $cat->ativo ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                        <form method="POST" action="/painel/categorias/{{ $cat->id }}" class="inline" onsubmit="return confirm('Remover?')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline text-sm ml-2">Remover</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="p-8 text-center text-gray-400">Nenhuma categoria.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
