<?php

namespace App\Models;

use App\Observers\EncomendaObserver;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string|null $nome_cliente
 * @property string|null $cor_escolhida
 * @property string|null $tamanho_escolhido
 * @property float $preco_total
 * @property string $estado
 * @property string $created_at
 * @property-read Produto $produto
 * @property-read Vendedor|null $vendedor
 */
#[ObservedBy(EncomendaObserver::class)]
class Encomenda extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    protected $fillable = [
        'tenant_id', 'numero_cliente', 'nome_cliente', 'produto_id',
        'variante_id', 'cor_escolhida', 'tamanho_escolhido',
        'vendedor_id', 'quantidade', 'preco_total', 'estado', 'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'preco_total' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Produto, $this> */
    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    /** @return BelongsTo<ProdutoVariante, $this> */
    public function variante(): BelongsTo
    {
        return $this->belongsTo(ProdutoVariante::class, 'variante_id');
    }

    /** @return BelongsTo<Vendedor, $this> */
    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class);
    }

    public function descricaoVariante(): string
    {
        $partes = array_filter([
            $this->cor_escolhida,
            $this->tamanho_escolhido,
        ]);

        if (!empty($partes)) {
            return implode(' · ', $partes);
        }

        /** @var ProdutoVariante|null $variante */
        $variante = $this->variante;
        if ($variante) {
            return $variante->descricaoVariantes();
        }

        return '';
    }
}
