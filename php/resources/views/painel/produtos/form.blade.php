@extends('layouts.painel')
@section('title', isset($produto) ? 'Editar Produto' : 'Novo Produto')

@section('content')
<div class="bg-white rounded-xl shadow p-6 max-w-2xl">
    <form method="POST" action="{{ isset($produto) ? '/painel/produtos/' . $produto->id : '/painel/produtos' }}" enctype="multipart/form-data">
        @csrf
        @if(isset($produto)) @method('PUT') @endif

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                <input type="text" name="nome" value="{{ old('nome', $produto->nome ?? '') }}" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                @error('nome') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                <textarea name="descricao" rows="3"
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">{{ old('descricao', $produto->descricao ?? '') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Preço (MZN) *</label>
                    <input type="number" name="preco" value="{{ old('preco', $produto->preco ?? '') }}" step="0.01" min="0.01" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    @error('preco') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock *</label>
                    <input type="number" name="stock" value="{{ old('stock', $produto->stock ?? 0) }}" min="0" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                    <select name="categoria_id" class="w-full px-4 py-2 border rounded-lg">
                        <option value="">Sem categoria</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}" {{ old('categoria_id', $produto->categoria_id ?? '') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->icone }} {{ $cat->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendedor</label>
                    <select name="vendedor_id" class="w-full px-4 py-2 border rounded-lg">
                        <option value="">Sem vendedor</option>
                        @foreach($vendedores as $v)
                            <option value="{{ $v->id }}" {{ old('vendedor_id', $produto->vendedor_id ?? '') == $v->id ? 'selected' : '' }}>
                                {{ $v->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Foto frente {{ isset($produto) ? '' : '*' }}</label>
                <input type="file" name="imagem" id="inputImagem" accept="image/jpeg,image/png,image/webp"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                       onchange="previewImagem(this, 'previewImagem')">
                @error('imagem') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <div class="mt-2">
                    @if(isset($produto) && $produto->imagem_url)
                        <img id="previewImagem" src="{{ $produto->imagem_url }}" alt="Foto frente"
                             class="w-32 h-32 object-cover rounded-lg border">
                    @else
                        <img id="previewImagem" src="" alt="" class="w-32 h-32 object-cover rounded-lg border hidden">
                    @endif
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Foto trás (opcional)</label>
                <input type="file" name="imagem2" id="inputImagem2" accept="image/jpeg,image/png,image/webp"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                       onchange="previewImagem(this, 'previewImagem2')">
                @error('imagem2') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <div class="mt-2">
                    @if(isset($produto) && ($produto->imagem2_url ?? null))
                        <img id="previewImagem2" src="{{ $produto->imagem2_url }}" alt="Foto trás"
                             class="w-32 h-32 object-cover rounded-lg border">
                    @else
                        <img id="previewImagem2" src="" alt="" class="w-32 h-32 object-cover rounded-lg border hidden">
                    @endif
                </div>
            </div>

            <div class="flex gap-6">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="disponivel" value="1" {{ old('disponivel', $produto->disponivel ?? true) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-700">Disponível</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="destaque" value="1" {{ old('destaque', $produto->destaque ?? false) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-700">Destaque ⭐</span>
                </label>
            </div>

            <div class="border rounded-lg p-4 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Variantes (opcional)</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cores disponíveis</label>
                    <div id="coresChips" class="flex flex-wrap gap-2 mb-2">
                        @if(isset($produto) && $produto->cores)
                            @foreach($produto->cores as $cor)
                                <span class="inline-flex items-center gap-1 bg-indigo-100 text-indigo-700 text-sm px-3 py-1 rounded-full">
                                    {{ $cor }}
                                    <button type="button" onclick="removerCor(this)" class="font-bold text-indigo-500 hover:text-indigo-700">&times;</button>
                                </span>
                            @endforeach
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <input type="text" id="inputNovaCor" placeholder="Nova cor..." class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none text-sm">
                        <button type="button" onclick="adicionarCor()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">+</button>
                    </div>
                    <input type="hidden" name="cores_json" id="coresJson" value="{{ old('cores_json', isset($produto) && $produto->cores ? json_encode($produto->cores) : '[]') }}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tamanhos disponíveis</label>
                    <div id="tamanhosToggles" class="flex flex-wrap gap-2 mb-2">
                        @php
                            $tamanhosDisponiveis = ['S', 'M', 'L', 'XL'];
                            $tamanhosSelecionados = old('tamanhos_selecionados', isset($produto) && $produto->tamanhos ? $produto->tamanhos : []);
                        @endphp
                        @foreach($tamanhosDisponiveis as $t)
                            <button type="button"
                                    onclick="toggleTamanho('{{ $t }}', this)"
                                    class="tamanho-btn px-4 py-2 border rounded-lg text-sm font-medium transition-all {{ in_array($t, $tamanhosSelecionados) ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-300 text-gray-700 hover:border-blue-400' }}"
                                    data-tamanho="{{ $t }}">
                                {{ $t }}
                            </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="tamanhos_json" id="tamanhosJson" value="{{ old('tamanhos_json', isset($produto) && $produto->tamanhos ? json_encode($produto->tamanhos) : '[]') }}">
                </div>
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                {{ isset($produto) ? 'Actualizar' : 'Criar Produto' }}
            </button>
            <a href="/painel/produtos" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Cancelar</a>
        </div>
    </form>
</div>

<script>
function previewImagem(input, previewId) {
    var preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.src = '';
        preview.classList.add('hidden');
    }
}

function getCoresArray() {
    var json = document.getElementById('coresJson').value;
    try { return JSON.parse(json); } catch { return []; }
}

function setCoresArray(arr) {
    document.getElementById('coresJson').value = JSON.stringify(arr);
}

function adicionarCor() {
    var input = document.getElementById('inputNovaCor');
    var cor = input.value.trim();
    if (!cor) return;
    var cores = getCoresArray();
    if (cores.includes(cor)) { alert('Essa cor já foi adicionada.'); return; }
    if (cores.length >= 10) { alert('Máximo de 10 cores.'); return; }
    cores.push(cor);
    setCoresArray(cores);
    var chip = document.createElement('span');
    chip.className = 'inline-flex items-center gap-1 bg-indigo-100 text-indigo-700 text-sm px-3 py-1 rounded-full';
    chip.innerHTML = cor + ' <button type="button" onclick="removerCor(this)" class="font-bold text-indigo-500 hover:text-indigo-700">&times;</button>';
    document.getElementById('coresChips').appendChild(chip);
    input.value = '';
}

function removerCor(btn) {
    var chip = btn.parentElement;
    var cor = chip.childNodes[0].textContent.trim();
    chip.remove();
    var cores = getCoresArray().filter(function(c) { return c !== cor; });
    setCoresArray(cores);
}

function getTamanhosArray() {
    var json = document.getElementById('tamanhosJson').value;
    try { return JSON.parse(json); } catch { return []; }
}

function setTamanhosArray(arr) {
    document.getElementById('tamanhosJson').value = JSON.stringify(arr);
}

function toggleTamanho(tamanho, btn) {
    var tamanhos = getTamanhosArray();
    var idx = tamanhos.indexOf(tamanho);
    if (idx >= 0) {
        tamanhos.splice(idx, 1);
        btn.classList.remove('bg-blue-600', 'border-blue-600', 'text-white');
        btn.classList.add('bg-white', 'border-gray-300', 'text-gray-700');
    } else {
        if (tamanhos.length >= 10) { alert('Máximo de 10 tamanhos.'); return; }
        tamanhos.push(tamanho);
        btn.classList.add('bg-blue-600', 'border-blue-600', 'text-white');
        btn.classList.remove('bg-white', 'border-gray-300', 'text-gray-700');
    }
    setTamanhosArray(tamanhos);
}
</script>
@endsection
