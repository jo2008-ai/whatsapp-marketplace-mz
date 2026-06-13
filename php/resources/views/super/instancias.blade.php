@extends('layouts.super')
@section('title', 'Instâncias WhatsApp')

@section('content')
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left p-3 font-medium text-gray-600">Instância</th>
                    <th class="text-left p-3 font-medium text-gray-600">Loja</th>
                    <th class="text-left p-3 font-medium text-gray-600">Número</th>
                    <th class="text-left p-3 font-medium text-gray-600">Estado</th>
                    <th class="text-left p-3 font-medium text-gray-600 hidden md:table-cell">Actualizado</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($instancias as $inst)
                <tr class="hover:bg-gray-50">
                    <td class="p-3">
                        <div class="font-mono text-xs text-gray-600">{{ $inst->evolution_instance_name }}</div>
                    </td>
                    <td class="p-3">
                        <a href="/super/lojas/{{ $inst->tenant_id }}" class="text-blue-600 hover:underline">
                            {{ $inst->tenant->nome_loja ?? '—' }}
                        </a>
                    </td>
                    <td class="p-3 text-gray-700">{{ $inst->numero_whatsapp ?? '—' }}</td>
                    <td class="p-3">
                        @if($inst->estado === 'conectada')
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">🟢 Conectada</span>
                        @elseif($inst->estado === 'aguarda_qr')
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">🟡 QR</span>
                        @else
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">🔴 Desconectada</span>
                        @endif
                    </td>
                    <td class="p-3 text-gray-500 text-xs hidden md:table-cell">{{ $inst->updated_at?->format('d/m/Y H:i') ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="p-8 text-center text-gray-400">Nenhuma instância.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $instancias->links() }}</div>
</div>
@endsection
