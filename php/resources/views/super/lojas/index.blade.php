@extends('layouts.super')
@section('title', 'Lojas')

@section('content')
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="p-4 border-b flex justify-between items-center">
        <h2 class="font-semibold text-gray-800">Lojas ({{ $lojas->count() }})</h2>
        <a href="/super/lojas/criar" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">
            + Criar Loja
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left p-3 font-medium text-gray-600">Loja</th>
                    <th class="text-left p-3 font-medium text-gray-600">Instância WhatsApp</th>
                    <th class="text-left p-3 font-medium text-gray-600">Estado</th>
                    <th class="text-left p-3 font-medium text-gray-600 hidden md:table-cell">Produtos</th>
                    <th class="text-left p-3 font-medium text-gray-600 hidden md:table-cell">Encomendas</th>
                    <th class="text-left p-3 font-medium text-gray-600 hidden lg:table-cell">WhatsApp</th>
                    <th class="text-right p-3 font-medium text-gray-600">Acção</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($lojas as $loja)
                <tr class="hover:bg-gray-50">
                    <td class="p-3">
                        <div class="font-medium text-gray-800">{{ $loja->nome_loja }}</div>
                        <div class="text-xs text-gray-500">{{ $loja->email_dono }}</div>
                    </td>
                    <td class="p-3 font-mono text-xs text-gray-600">{{ $loja->instancia_whatsapp ?? '—' }}</td>
                    <td class="p-3">
                        @if($loja->activo)
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Activo</span>
                        @else
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">Inactivo</span>
                        @endif
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
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
