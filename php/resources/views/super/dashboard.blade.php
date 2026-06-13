@extends('layouts.super')
@section('title', 'Dashboard — Super Admin')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-5">
        <div class="text-sm text-gray-500">Total Lojas</div>
        <div class="text-3xl font-bold text-gray-800">{{ $lojas->count() }}</div>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <div class="text-sm text-gray-500">Lojas Activas</div>
        <div class="text-3xl font-bold text-green-600">{{ $lojas->where('activo', true)->count() }}</div>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <div class="text-sm text-gray-500">Total Produtos</div>
        <div class="text-3xl font-bold text-blue-600">{{ $lojas->sum('produtos_count') }}</div>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <div class="text-sm text-gray-500">Instâncias Ligadas</div>
        <div class="text-3xl font-bold text-purple-600">{{ $instanciasLigadas }} / {{ $totalInstancias }}</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow p-5">
    <h3 class="font-semibold text-gray-800 mb-3">Acesso Rápido</h3>
    <div class="space-y-2">
        <a href="/super/lojas" class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 text-gray-700">Gerir Lojas</a>
        <a href="/super/instancias" class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 text-gray-700">Instâncias WhatsApp</a>
    </div>
</div>
@endsection
