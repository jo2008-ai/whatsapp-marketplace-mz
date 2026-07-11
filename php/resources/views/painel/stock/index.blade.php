@extends('layouts.painel')
@section('title', 'Gestão de Stock')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Gestão de Stock</h2>
</div>

<!-- Resumo -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-5">
        <div class="text-sm text-gray-500">Total Produtos</div>
        <div class="text-3xl font-bold text-gray-800">{{ $relatorio['total_produtos'] }}</div>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <div class="text-sm text-gray-500">Stock Baixo</div>
        <div class="text-3xl font-bold {{ $relatorio['stock_baixo'] > 0 ? 'text-yellow-600' : 'text-green-600' }}">{{ $relatorio['stock_baixo'] }}</div>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <div class="text-sm text-gray-500">Sem Stock</div>
        <div class="text-3xl font-bold {{ $relatorio['sem_stock'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $relatorio['sem_stock'] }}</div>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <div class="text-sm text-gray-500">Valor Inventário</div>
        <div class="text-3xl font-bold text-blue-600">{{ number_format($relatorio['valor_inventario'], 2, ',', '.') }} MZN</div>
    </div>
</div>

<!-- Alertas de Stock Baixo -->
@if($produtosStockBaixo->count() > 0)
<div class="bg-red-50 border border-red-200 rounded-xl p-5 mb-6">
    <h3 class="text-lg font-semibold text-red-800 mb-3">⚠️ {{ $produtosStockBaixo->count() }} {{ Str::plural('produto', $produtosStockBaixo->count()) }} com stock baixo</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-red-700 border-b border-red-200">
                    <th class="pb-2">Produto</th>
                    <th class="pb-2">Stock Actual</th>
                    <th class="pb-2">Stock Mínimo</th>
                    <th class="pb-2">Unidade</th>
                    <th class="pb-2">Acção</th>
                </tr>
            </thead>
            <tbody>
                @foreach($produtosStockBaixo as $produto)
                <tr class="border-b border-red-100">
                    <td class="py-2 font-medium">{{ $produto->nome }}</td>
                    <td class="py-2 text-red-600 font-bold">{{ $produto->stock }}</td>
                    <td class="py-2">{{ $produto->stock_minimo }}</td>
                    <td class="py-2">{{ $produto->unidade }}</td>
                    <td class="py-2">
                        <button onclick="abrirModalRepor({{ $produto->id }}, '{{ addslashes($produto->nome) }}', {{ $produto->stock }})" class="text-blue-600 hover:underline font-medium">Repor</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Últimos Movimentos -->
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">📋 Últimos Movimentos</h3>

    @if($movimentos->isEmpty())
    <p class="text-gray-500 text-center py-4">Sem movimentos registados.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="pb-2">Data</th>
                    <th class="pb-2">Produto</th>
                    <th class="pb-2">Tipo</th>
                    <th class="pb-2">Qtd</th>
                    <th class="pb-2">Stock Anterior</th>
                    <th class="pb-2">Stock Actual</th>
                    <th class="pb-2">Motivo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movimentos as $movimento)
                <tr class="border-b border-gray-100">
                    <td class="py-2 text-gray-600">{{ $movimento->created_at->format('d/m/Y H:i') }}</td>
                    <td class="py-2 font-medium">{{ $movimento->produto->nome ?? '-' }}</td>
                    <td class="py-2">
                        @if($movimento->tipo === 'entrada')
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium">Entrada</span>
                        @elseif($movimento->tipo === 'saida')
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium">Saída</span>
                        @elseif($movimento->tipo === 'ajuste')
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-medium">Ajuste</span>
                        @else
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium">Devolução</span>
                        @endif
                    </td>
                    <td class="py-2 {{ $movimento->tipo === 'entrada' || $movimento->tipo === 'devolucao' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $movimento->tipo === 'saida' || $movimento->tipo === 'ajuste' && $movimento->stock_actual < $movimento->stock_anterior ? '-' : '+' }}{{ $movimento->quantidade }}
                    </td>
                    <td class="py-2">{{ $movimento->stock_anterior }}</td>
                    <td class="py-2 font-medium">{{ $movimento->stock_actual }}</td>
                    <td class="py-2 text-gray-500">{{ $movimento->motivo ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<!-- Produtos com Stock -->
<div class="bg-white rounded-xl shadow p-5">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">📦 Todos os Produtos</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="pb-2">Produto</th>
                    <th class="pb-2">Stock</th>
                    <th class="pb-2">Mínimo</th>
                    <th class="pb-2">Máximo</th>
                    <th class="pb-2">Unidade</th>
                    <th class="pb-2">Custo Unit.</th>
                    <th class="pb-2">Estado</th>
                    <th class="pb-2">Acção</th>
                </tr>
            </thead>
            <tbody>
                @foreach($produtos as $produto)
                <tr class="border-b border-gray-100 {{ $produto->stock <= 0 ? 'bg-red-50' : ($produto->stockBaixo() ? 'bg-yellow-50' : '') }}">
                    <td class="py-2 font-medium">{{ $produto->nome }}</td>
                    <td class="py-2 {{ $produto->stock <= 0 ? 'text-red-600 font-bold' : ($produto->stockBaixo() ? 'text-yellow-600 font-bold' : '') }}">{{ $produto->stock }}</td>
                    <td class="py-2">{{ $produto->stock_minimo }}</td>
                    <td class="py-2">{{ $produto->stock_maximo }}</td>
                    <td class="py-2">{{ $produto->unidade }}</td>
                    <td class="py-2">{{ number_format($produto->custo_unitario, 2, ',', '.') }} MZN</td>
                    <td class="py-2">
                        @if($produto->stock <= 0)
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium">Sem stock</span>
                        @elseif($produto->stockBaixo())
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-medium">Stock baixo</span>
                        @else
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium">Normal</span>
                        @endif
                    </td>
                    <td class="py-2">
                        <button onclick="abrirModalRepor({{ $produto->id }}, '{{ addslashes($produto->nome) }}', {{ $produto->stock }})" class="text-blue-600 hover:underline font-medium">Repor</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Repor Stock -->
<div id="modalRepor" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Repor Stock</h3>
        <form id="formRepor" method="POST" action="">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Produto</label>
                <p id="produtoNome" class="text-gray-900 font-medium"></p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Stock Actual</label>
                <p id="stockActual" class="text-gray-600"></p>
            </div>
            <div class="mb-4">
                <label for="quantidade" class="block text-sm font-medium text-gray-700 mb-1">Quantidade a adicionar *</label>
                <input type="number" name="quantidade" id="quantidade" min="1" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-4">
                <label for="motivo" class="block text-sm font-medium text-gray-700 mb-1">Motivo</label>
                <select name="motivo" id="motivo" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="reposicao">Reposição</option>
                    <option value="contagem_fisica">Contagem física</option>
                    <option value="devolucao">Devolução</option>
                    <option value="outro">Outro</option>
                </select>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="fecharModal()" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Confirmar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function abrirModalRepor(produtoId, nome, stock) {
    document.getElementById('produtoNome').textContent = nome;
    document.getElementById('stockActual').textContent = stock + ' unidades';
    document.getElementById('formRepor').action = '/painel/stock/' + produtoId + '/entrada';
    document.getElementById('modalRepor').classList.remove('hidden');
}

function fecharModal() {
    document.getElementById('modalRepor').classList.add('hidden');
}

document.getElementById('modalRepor').addEventListener('click', function(e) {
    if (e.target === this) fecharModal();
});
</script>
@endpush
