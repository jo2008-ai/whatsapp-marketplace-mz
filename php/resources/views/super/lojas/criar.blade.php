@extends('layouts.super')
@section('title', 'Criar Loja')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold text-gray-800">Criar Nova Loja</h2>
            <p class="text-sm text-gray-500 mt-1">Cria uma loja com utilizador admin e subscrição inicial.</p>
        </div>

        <form method="POST" action="/super/lojas/guardar" class="p-6 space-y-5">
            @csrf

            <!-- Nome da Loja -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome da Loja *</label>
                <input type="text" name="nome_loja" value="{{ old('nome_loja') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Ex: Loja do Manel">
                @error('nome_loja')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email do Dono -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email do Dono *</label>
                <input type="email" name="email_dono" value="{{ old('email_dono') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="email@exemplo.com">
                @error('email_dono')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Telefone -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                <input type="text" name="telefone_dono" value="{{ old('telefone_dono') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="+258841234567">
                @error('telefone_dono')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                <input type="password" name="password" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Mínimo 8 caracteres, 1 maiúscula, 1 minúscula, 1 número">
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirmar Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar Password *</label>
                <input type="password" name="password_confirmation" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Repetir password">
            </div>

            <!-- Plano -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Plano Inicial *</label>
                <select name="plano"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="basic" {{ old('plano') === 'basic' ? 'selected' : '' }}>Basic — 500 MZN/mês (50 produtos, 1 número)</option>
                    <option value="pro" {{ old('plano') === 'pro' ? 'selected' : '' }}>Pro — 1.500 MZN/mês (500 produtos, 3 números)</option>
                    <option value="enterprise" {{ old('plano') === 'enterprise' ? 'selected' : '' }}>Enterprise — 5.000 MZN/mês (ilimitado)</option>
                </select>
                @error('plano')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Dias de Trial -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dias de Trial</label>
                <input type="number" name="dias_trial" value="{{ old('dias_trial', 7) }}" min="0" max="30"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="7">
                <p class="text-xs text-gray-500 mt-1">0 = activar directamente sem trial</p>
                @error('dias_trial')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit -->
            <div class="flex gap-3 pt-4">
                <a href="/super/lojas"
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">
                    Criar Loja
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
