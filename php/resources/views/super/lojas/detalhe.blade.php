@extends('layouts.super')
@section('title', $tenant->nome_loja)

@section('content')
@if(session('success'))
<div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg">
    {{ session('success') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-semibold text-gray-800 mb-3">Informações</h3>
        <div class="space-y-2 text-sm">
            <p><strong>Nome:</strong> {{ $tenant->nome_loja }}</p>
            <p><strong>Email:</strong> {{ $tenant->email_dono }}</p>
            <p><strong>Telefone:</strong> {{ $tenant->telefone_dono ?? '—' }}</p>
            <p><strong>Instância WhatsApp:</strong> <code class="text-xs bg-gray-100 px-1 rounded">{{ $tenant->instancia_whatsapp ?? '—' }}</code></p>
            <p><strong>UUID:</strong> <code class="text-xs bg-gray-100 px-1 rounded">{{ $tenant->uuid }}</code></p>
            <p><strong>Criado:</strong> {{ $tenant->created_at->format('d/m/Y H:i') }}</p>
            <p><strong>Produtos:</strong> {{ $tenant->produtos_count }}</p>
            <p><strong>Categorias:</strong> {{ $tenant->categorias_count }}</p>
            <p><strong>Encomendas:</strong> {{ $tenant->encomendas_count }}</p>
        </div>

        <hr class="my-4">

        <h4 class="font-medium text-gray-700 mb-2">Estado</h4>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ $tenant->activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                {{ $tenant->activo ? 'Activo' : 'Inactivo' }}
            </span>
            <form method="POST" action="/super/lojas/{{ $tenant->id }}/toggle">
                @csrf @method('PATCH')
                <button class="px-4 py-2 {{ $tenant->activo ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg text-sm">
                    {{ $tenant->activo ? 'Desactivar' : 'Activar' }}
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-semibold text-gray-800 mb-3">Login Code</h3>
        @php $user = $tenant->users->first(); @endphp
        @if($user && $user->login_code)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <div class="text-3xl font-mono font-bold text-blue-600 tracking-widest">{{ $user->login_code }}</div>
            <p class="text-sm text-gray-600 mt-2">Envie este código ao cliente para login em <strong>/login</strong></p>
        </div>
        @else
        <p class="text-sm text-gray-400">Sem código gerado.</p>
        @endif

        <hr class="my-4">

        <h4 class="font-medium text-gray-700 mb-2">Instâncias WhatsApp</h4>
        @forelse($tenant->instancias as $inst)
        <div class="border rounded-lg p-3 mb-2">
            <div class="flex justify-between">
                <span class="text-sm font-medium">{{ $inst->nome_instancia }}</span>
                @if($inst->estado === 'conectada')
                    <span class="text-green-600 text-xs">Conectada</span>
                @else
                    <span class="text-red-500 text-xs">Desconectada</span>
                @endif
            </div>
            <div class="text-xs text-gray-500">{{ $inst->numero_whatsapp ?? 'Sem número' }}</div>
        </div>
        @empty
        <p class="text-sm text-gray-400">Sem instâncias.</p>
        @endforelse
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-semibold text-gray-800 mb-3">Utilizadores</h3>
        @foreach($tenant->users as $user)
        <div class="border rounded-lg p-3 mb-2">
            <div class="text-sm font-medium">{{ $user->name }}</div>
            <div class="text-xs text-gray-500">{{ $user->email }} — {{ ucfirst($user->role) }}</div>
        </div>
        @endforeach
    </div>
</div>

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
