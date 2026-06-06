@extends('layouts.painel')
@section('title', 'Vendedores')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Formulário -->
    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-semibold text-gray-800 mb-3">Novo Vendedor</h3>
        <form method="POST" action="/painel/vendedores" class="space-y-3">
            @csrf
            <input type="text" name="nome" placeholder="Nome" required
                   class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            <input type="text" name="numero_whatsapp" placeholder="+25884XXXXXXX" required
                   class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            <input type="text" name="descricao" placeholder="Descrição (opcional)"
                   class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            <button class="w-full py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Adicionar</button>
        </form>
    </div>

    <!-- Lista -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left p-3 font-medium text-gray-600">Vendedor</th>
                    <th class="text-left p-3 font-medium text-gray-600">WhatsApp</th>
                    <th class="text-left p-3 font-medium text-gray-600">Produtos</th>
                    <th class="text-left p-3 font-medium text-gray-600">Estado</th>
                    <th class="text-right p-3 font-medium text-gray-600">Acções</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($vendedores as $v)
                <tr class="hover:bg-gray-50">
                    <td class="p-3 font-medium text-gray-800">{{ $v->nome }}</td>
                    <td class="p-3 text-gray-600">{{ $v->numero_whatsapp }}</td>
                    <td class="p-3 text-gray-600">{{ $v->produtos_count }}</td>
                    <td class="p-3">
                        @if($v->ativo)
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Activo</span>
                        @else
                            <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded-full text-xs">Inactivo</span>
                        @endif
                    </td>
                    <td class="p-3 text-right">
                        <form method="POST" action="/painel/vendedores/{{ $v->id }}" class="inline">
                            @csrf @method('PUT')
                            <input type="hidden" name="nome" value="{{ $v->nome }}">
                            <input type="hidden" name="numero_whatsapp" value="{{ $v->numero_whatsapp }}">
                            <input type="hidden" name="ativo" value="{{ $v->ativo ? 0 : 1 }}">
                            <button class="text-sm {{ $v->ativo ? 'text-yellow-600' : 'text-green-600 }} hover:underline">
                                {{ $v->ativo ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                        <form method="POST" action="/painel/vendedores/{{ $v->id }}" class="inline" onsubmit="return confirm('Remover?')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline text-sm ml-2">Remover</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="p-8 text-center text-gray-400">Nenhum vendedor.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
