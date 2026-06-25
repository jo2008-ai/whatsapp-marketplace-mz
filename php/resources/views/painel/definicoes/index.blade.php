@extends('layouts.painel')
@section('title', 'Personalizar Bot')

@section('content')
<div class="bg-white rounded-xl shadow p-6 max-w-2xl">
    <h2 class="text-xl font-bold text-gray-800 mb-6">Personalizar Mensagens do Bot</h2>
    
    <form method="POST" action="/painel/definicoes" class="space-y-6">
        @csrf

        {{-- Informações da Loja --}}
        <div class="border-b pb-4">
            <h3 class="font-semibold text-gray-700 mb-3">Informações da Loja</h3>
            
            <div class="space-y-4">
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
            </div>
        </div>

        {{-- Mensagens do Bot --}}
        <div class="border-b pb-4">
            <h3 class="font-semibold text-gray-700 mb-3">Mensagens do Bot</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mensagem de Boas-vindas</label>
                    <textarea name="mensagem_boas_vindas" rows="3"
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                              placeholder="Olá! 👋 Bem-vindo à [Nome da Loja]!...">{{ old('mensagem_boas_vindas', $tenant->mensagem_boas_vindas) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Mensagem enviada quando o cliente inicia conversa.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mensagem de Erro no Menu</label>
                    <textarea name="mensagem_erro_menu" rows="2"
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                              placeholder="Não entendi 😅 Escreve o número da opção...">{{ old('mensagem_erro_menu', $tenant->mensagem_erro_menu) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Quando o cliente envia opção inválida.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoria Sem Produtos</label>
                    <textarea name="mensagem_categoria_vazia" rows="2"
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                              placeholder="Ainda não há produtos nesta categoria.">{{ old('mensagem_categoria_vazia', $tenant->mensagem_categoria_vazia) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Quando uma categoria não tem produtos.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pesquisa Sem Resultados</label>
                    <textarea name="mensagem_pesquisa_vazia" rows="2"
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                              placeholder="Nenhum produto encontrado para [termo].">{{ old('mensagem_pesquisa_vazia', $tenant->mensagem_pesquisa_vazia) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Quando a pesquisa não encontra produtos.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pedido Confirmado</label>
                    <textarea name="mensagem_pedido_sucesso" rows="2"
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                              placeholder="✅ Encomenda feita com sucesso! Obrigado pela preferência!">{{ old('mensagem_pedido_sucesso', $tenant->mensagem_pedido_sucesso) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Mensagem após criar encomenda.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pedido Cancelado</label>
                    <textarea name="mensagem_pedido_cancelado" rows="2"
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                              placeholder="✅ Encomenda cancelada com sucesso.">{{ old('mensagem_pedido_cancelado', $tenant->mensagem_pedido_cancelado) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Mensagem após cancelar encomenda.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendedores Indisponíveis</label>
                    <textarea name="mensagem_vendedores_indisponivel" rows="2"
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                              placeholder="Ainda não há vendedores disponíveis.">{{ old('mensagem_vendedores_indisponivel', $tenant->mensagem_vendedores_indisponivel) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Quando não há vendedores activos.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mensagem de Transferência</label>
                    <textarea name="mensagem_transferencia" rows="2"
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                              placeholder="✅ A sua conversa foi encaminhada para [vendedor].">{{ old('mensagem_transferencia', $tenant->mensagem_transferencia) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Quando a conversa é transferida para vendedor.</p>
                </div>
            </div>
        </div>

        {{-- Estado da Loja --}}
        <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-medium text-gray-700 mb-2">Estado da Loja</h4>
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ $tenant->activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                {{ $tenant->activo ? 'Activa' : 'Inactiva' }}
            </span>
        </div>

        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Guardar Definições
        </button>
    </form>
</div>
@endsection
