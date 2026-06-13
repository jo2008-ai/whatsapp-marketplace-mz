<?php

namespace App\Models;

use App\Observers\EncomendaObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(EncomendaObserver::class)]
class Encomenda extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'numero_cliente', 'nome_cliente', 'produto_id',
        'cor_escolhida', 'tamanho_escolhido',
        'vendedor_id', 'quantidade', 'preco_total', 'estado', 'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'preco_total' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class);
    }
}
