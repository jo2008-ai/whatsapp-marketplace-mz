<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produto extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'vendedor_id', 'categoria_id', 'nome', 'descricao',
        'preco', 'stock', 'imagem_url', 'imagem2_url', 'disponivel', 'destaque',
        'cores', 'tamanhos',
    ];

    protected function casts(): array
    {
        return [
            'preco' => 'decimal:2',
            'disponivel' => 'boolean',
            'destaque' => 'boolean',
            'cores' => 'array',
            'tamanhos' => 'array',
        ];
    }

    public function temVariantes(): bool
    {
        return !empty($this->cores) || !empty($this->tamanhos);
    }

    public function temCores(): bool
    {
        return !empty($this->cores);
    }

    public function temTamanhos(): bool
    {
        return !empty($this->tamanhos);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class);
    }

    public function temStock(): bool
    {
        return $this->stock > 0;
    }
}
