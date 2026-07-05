<x-filament-panels::page
    @if($record)
        :headerActions="[
            \Filament\Actions\Action::make('voltar')
                ->label('Voltar')
                ->url(route('filament.admin.resources.produtos.edit', $record))
                ->icon('heroicon-o-arrow-left')
        ]"
    @endif
>
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                Matriz de Variantes: {{ $record->nome ?? '' }}
            </h2>

            <div class="mb-4">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Cores</h3>
                <div class="flex flex-wrap gap-2 mb-2">
                    @foreach($cores as $index => $cor)
                        <div class="flex items-center gap-1">
                            <input
                                type="text"
                                value="{{ $cor }}"
                                wire:change="actualizarCor({{ $index }}, $event.target.value)"
                                placeholder="Cor {{ $index + 1 }}"
                                class="px-3 py-1 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            >
                            <button
                                type="button"
                                wire:click="removerCor({{ $index }})"
                                class="text-red-500 hover:text-red-700"
                                @if(count($cores) <= 1) disabled @endif
                            >
                                &times;
                            </button>
                        </div>
                    @endforeach
                </div>
                <button
                    type="button"
                    wire:click="adicionarCor"
                    class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
                >
                    + Adicionar Cor
                </button>
            </div>

            <div class="mb-4">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Tamanhos</h3>
                <div class="flex flex-wrap gap-2 mb-2">
                    @foreach($tamanhos as $index => $tamanho)
                        <div class="flex items-center gap-1">
                            <input
                                type="text"
                                value="{{ $tamanho }}"
                                wire:change="actualizarTamanho({{ $index }}, $event.target.value)"
                                placeholder="Tamanho {{ $index + 1 }}"
                                class="px-3 py-1 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            >
                            <button
                                type="button"
                                wire:click="removerTamanho({{ $index }})"
                                class="text-red-500 hover:text-red-700"
                                @if(count($tamanhos) <= 1) disabled @endif
                            >
                                &times;
                            </button>
                        </div>
                    @endforeach
                </div>
                <button
                    type="button"
                    wire:click="adicionarTamanho"
                    class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
                >
                    + Adicionar Tamanho
                </button>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cor / Tamanho
                        </th>
                        @foreach($tamanhos as $tamanho)
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ $tamanho ?: 'S/T' }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($cores as $cor)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                {{ $cor ?: 'S/C' }}
                            </td>
                            @foreach($tamanhos as $tamanho)
                                @php
                                    $chave = md5("{$cor}-{$tamanho}");
                                    $celula = $matriz[$chave] ?? null;
                                @endphp
                                <td class="px-2 py-2">
                                    <div class="bg-gray-50 rounded-lg p-2 space-y-1 min-w-[140px]">
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs text-gray-500 w-10">Stock:</span>
                                            <input
                                                type="number"
                                                value="{{ $celula['stock'] ?? 0 }}"
                                                wire:change="actualizarCelula('{{ $chave }}', 'stock', $event.target.value)"
                                                min="0"
                                                class="w-16 px-2 py-1 border rounded text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                            >
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs text-gray-500 w-10">Preço:</span>
                                            <input
                                                type="number"
                                                value="{{ $celula['preco_override'] ?? '' }}"
                                                wire:change="actualizarCelula('{{ $chave }}', 'preco_override', $event.target.value)"
                                                step="0.01"
                                                min="0"
                                                placeholder="Herda"
                                                class="w-16 px-2 py-1 border rounded text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                            >
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs text-gray-500 w-10">SKU:</span>
                                            <input
                                                type="text"
                                                value="{{ $celula['sku'] ?? '' }}"
                                                wire:change="actualizarCelula('{{ $chave }}', 'sku', $event.target.value)"
                                                class="w-full px-2 py-1 border rounded text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                            >
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <input
                                                type="checkbox"
                                                {{ ($celula['disponivel'] ?? true) ? 'checked' : '' }}
                                                wire:change="actualizarCelula('{{ $chave }}', 'disponivel', $event.target.checked)"
                                                class="rounded border-gray-300"
                                            >
                                            <span class="text-xs text-gray-600">Activo</span>
                                        </div>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end">
            <button
                type="button"
                wire:click="guardar"
                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium"
            >
                Guardar Variantes
            </button>
        </div>
    </div>
</x-filament-panels::page>
