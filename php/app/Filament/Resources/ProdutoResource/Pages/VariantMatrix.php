<?php

namespace App\Filament\Resources\ProdutoResource\Pages;

use App\Filament\Resources\ProdutoResource;
use App\Models\Produto;
use App\Models\ProdutoVariante;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class VariantMatrix extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ProdutoResource::class;

    protected static string $view = 'filament.resources.produto-resource.pages.variant-matrix';

    public ?Produto $record = null;

    public array $cores = [];

    public array $tamanhos = [];

    public array $matriz = [];

    public function mount(int|string $record): void
    {
        $this->record = Produto::findOrFail($record);
        $this->carregarDados();
    }

    public function carregarDados(): void
    {
        $this->cores = $this->record->obterCoresDisponiveis();
        $this->tamanhos = $this->record->obterTamanhosDisponiveis();

        if (empty($this->cores)) {
            $this->cores = [''];
        }
        if (empty($this->tamanhos)) {
            $this->tamanhos = [''];
        }

        $this->construirMatriz();
    }

    public function construirMatriz(): void
    {
        $this->matriz = [];

        foreach ($this->cores as $cor) {
            foreach ($this->tamanhos as $tamanho) {
                $chave = $this->obterChave($cor, $tamanho);
                $variante = $this->obterVarianteExistente($cor, $tamanho);

                $this->matriz[$chave] = [
                    'cor' => $cor,
                    'tamanho' => $tamanho,
                    'stock' => $variante?->stock ?? 0,
                    'preco_override' => $variante?->preco_override ?? '',
                    'sku' => $variante?->sku ?? $this->gerarSku($cor, $tamanho),
                    'disponivel' => $variante?->disponivel ?? true,
                    'variante_id' => $variante?->id ?? null,
                ];
            }
        }
    }

    private function obterChave(string $cor, string $tamanho): string
    {
        return md5("{$cor}-{$tamanho}");
    }

    private function obterVarianteExistente(?string $cor, ?string $tamanho): ?ProdutoVariante
    {
        $query = $this->record->variantes();

        if ($cor) {
            $query->whereJsonContains('atributos', [
                'nome' => 'Cor',
                'valor' => $cor,
            ]);
        }

        if ($tamanho) {
            $query->whereJsonContains('atributos', [
                'nome' => 'Tamanho',
                'valor' => $tamanho,
            ]);
        }

        return $query->first();
    }

    public function gerarSku(?string $cor, ?string $tamanho): string
    {
        $base = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $this->record->nome), 0, 6));
        $corCode = $cor ? strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $cor), 0, 3)) : 'XX';
        $tamanhoCode = $tamanho ? strtoupper($tamanho) : 'UN';

        return "{$base}-{$corCode}-{$tamanhoCode}";
    }

    public function adicionarCor(): void
    {
        $this->cores[] = '';
        $this->construirMatriz();
    }

    public function removerCor(int $index): void
    {
        if (count($this->cores) > 1) {
            array_splice($this->cores, $index, 1);
            $this->construirMatriz();
        }
    }

    public function adicionarTamanho(): void
    {
        $this->tamanhos[] = '';
        $this->construirMatriz();
    }

    public function removerTamanho(int $index): void
    {
        if (count($this->tamanhos) > 1) {
            array_splice($this->tamanhos, $index, 1);
            $this->construirMatriz();
        }
    }

    public function actualizarCor(int $index, string $valor): void
    {
        $this->cores[$index] = $valor;
        $this->construirMatriz();
    }

    public function actualizarTamanho(int $index, string $valor): void
    {
        $this->tamanhos[$index] = $valor;
        $this->construirMatriz();
    }

    public function actualizarCelula(string $chave, string $campo, $valor): void
    {
        if (isset($this->matriz[$chave])) {
            $this->matriz[$chave][$campo] = $valor;
        }
    }

    public function guardar(): void
    {
        foreach ($this->matriz as $chave => $celula) {
            if (empty($celula['cor']) && empty($celula['tamanho'])) {
                continue;
            }

            $atributos = [];
            if (!empty($celula['cor'])) {
                $atributos[] = [
                    'codigo' => 'cor',
                    'nome' => 'Cor',
                    'tipo' => 'cor',
                    'valor' => $celula['cor'],
                ];
            }
            if (!empty($celula['tamanho'])) {
                $atributos[] = [
                    'codigo' => 'tamanho',
                    'nome' => 'Tamanho',
                    'tipo' => 'tamanho',
                    'valor' => $celula['tamanho'],
                ];
            }

            if (empty($atributos)) {
                continue;
            }

            $dados = [
                'sku' => $celula['sku'],
                'stock' => (int) $celula['stock'],
                'preco_override' => $celula['preco_override'] !== '' ? (float) $celula['preco_override'] : null,
                'disponivel' => $celula['disponivel'],
                'atributos' => $atributos,
            ];

            if ($celula['variante_id']) {
                ProdutoVariante::where('id', $celula['variante_id'])
                    ->where('produto_id', $this->record->id)
                    ->update($dados);
            } else {
                $this->record->variantes()->create($dados);
            }
        }

        Notification::make()
            ->title('Variantes guardadas com sucesso!')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
