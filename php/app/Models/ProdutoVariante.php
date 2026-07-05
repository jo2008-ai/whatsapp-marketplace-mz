<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property float|null $preco_override
 * @property int $stock
 * @property bool $disponivel
 * @property array<int, array{nome: string, valor: string}> $atributos
 */
class ProdutoVariante extends Model
{
    protected $table = 'produto_variantes';

    protected $fillable = [
        'produto_id', 'sku', 'preco_override', 'stock', 'disponivel',
        'imagem_url', 'atributos',
    ];

    protected function casts(): array
    {
        return [
            'preco_override' => 'decimal:2',
            'stock' => 'integer',
            'disponivel' => 'boolean',
            'atributos' => 'array',
        ];
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function precoFinal(): float
    {
        if ($this->preco_override !== null) {
            return (float) $this->preco_override;
        }

        /** @var Produto $produto */
        $produto = $this->produto;
        return (float) $produto->preco;
    }

    public function temStock(): bool
    {
        return $this->stock > 0;
    }

    public function obterValorAtributo(string $codigo): ?string
    {
        foreach ($this->atributos as $atributo) {
            if (isset($atributo['nome']) && strtolower($atributo['nome']) === strtolower($codigo)) {
                return $atributo['valor'] ?? null;
            }
        }

        return null;
    }

    public function descricaoVariantes(): string
    {
        $partes = [];
        foreach ($this->atributos as $atributo) {
            if (isset($atributo['valor'])) {
                $partes[] = $atributo['valor'];
            }
        }

        return implode(' · ', $partes);
    }
}
