@extends('layouts.super')
@section('title', 'Criar Loja')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="p-6 border-b bg-blue-50">
            <h2 class="text-lg font-semibold text-gray-800">Criar Loja Rapido</h2>
            <p class="text-sm text-gray-600 mt-1">Apenas 3 campos. Login code gerado automaticamente. Instancia WAHA criada automaticamente.</p>
        </div>

        <form method="POST" action="/super/lojas/criar-rapido" class="p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome da Loja *</label>
                <input type="text" name="nome_loja" value="{{ old('nome_loja') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                       placeholder="Ex: Loja do Manel">
                @error('nome_loja')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Dono *</label>
                <input type="text" name="nome_dono" value="{{ old('nome_dono') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                       placeholder="Ex: Manel">
                @error('nome_dono')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Telefone (WhatsApp) *</label>
                <input type="text" name="telefone" value="{{ old('telefone') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                       placeholder="841234567">
                @error('telefone')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Plano *</label>
                <select name="plano" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="basic" {{ old('plano') === 'basic' ? 'selected' : '' }}>Basic — 500 MZN/mês (50 produtos, 1 nº)</option>
                    <option value="pro" {{ old('plano') === 'pro' ? 'selected' : '' }}>Pro — 1.500 MZN/mês (500 produtos, 3 nºs)</option>
                    <option value="enterprise" {{ old('plano') === 'enterprise' ? 'selected' : '' }}>Enterprise — 5.000 MZN/mês (ilimitado)</option>
                </select>
                @error('plano')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 pt-4">
                <a href="/super/lojas"
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700">
                    Criar Loja
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
