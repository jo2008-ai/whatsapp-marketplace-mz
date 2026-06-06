@extends('layouts.painel')
@section('title', 'Definições')

@section('content')
<div class="bg-white rounded-xl shadow p-6 max-w-2xl">
    <form method="POST" action="/painel/definicoes" class="space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nome da Loja</label>
            <input type="text" name="nome_loja" value="{{ old('nome_loja', $tenant->nome_loja) }}" required
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">URL do Logo</label>
            <input type="url" name="logo_url" value="{{ old('logo_url', $tenant->logo_url) }}"
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                   placeholder="https://...">
            @if($tenant->logo_url)
                <img src="{{ $tenant->logo_url }}" alt="Logo" class="mt-2 h-12 rounded">
            @endif
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cor Primária</label>
            <div class="flex items-center gap-3">
                <input type="color" name="cor_primaria" value="{{ old('cor_primaria', $tenant->cor_primaria) }}" class="h-10 w-16 cursor-pointer">
                <input type="text" value="{{ old('cor_primaria', $tenant->cor_primaria) }}" readonly class="px-3 py-2 border rounded-lg text-sm bg-gray-50">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mensagem de Boas-vindas do Bot</label>
            <textarea name="mensagem_boas_vindas" rows="5"
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                      placeholder="Olá! 👋 Bem-vindo à [Nome da Loja]!...">{{ old('mensagem_boas_vindas', $tenant->mensagem_boas_vindas) }}</textarea>
            <p class="text-xs text-gray-500 mt-1">Deixa vazio para usar a mensagem padrão do bot.</p>
        </div>

        <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-medium text-gray-700 mb-2">Plano Actual</h4>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded-full text-sm font-medium
                    {{ $tenant->plano === 'enterprise' ? 'bg-purple-100 text-purple-700' : ($tenant->plano === 'pro' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') }}">
                    {{ ucfirst($tenant->plano) }}
                </span>
                <span class="text-sm text-gray-500">Até {{ $tenant->max_produtos >= 99999 ? 'ilimitados' : $tenant->max_produtos }} produtos</span>
            </div>
        </div>

        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Guardar Definições
        </button>
    </form>
</div>
@endsection
