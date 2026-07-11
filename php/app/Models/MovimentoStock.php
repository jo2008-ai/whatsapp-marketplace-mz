<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $produto_id
 * @property string $tipo
 * @property int $quantidade
 * @property int $stock_anterior
 * @property int $stock_actual
 * @property string|null $motivo
 * @property int|null $referencia_id
 * @property string|null $referencia_tipo
 * @property int|null $utilizador_id
 * @property-read Produto $produto
 * @property-read User|null $utilizador
 * @property-read Tenant $tenant
 */
class MovimentoStock extends Model
{
    public const TIPO_ENTRADA = 'entrada';
    public const TIPO_SAIDA = 'saida';
    public const TIPO_AJUSTE = 'ajuste';
    public const TIPO_DEVOLUCAO = 'devolucao';

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    protected $fillable = [
        'tenant_id', 'produto_id', 'tipo', 'quantidade',
        'stock_anterior', 'stock_actual', 'motivo',
        'referencia_id', 'referencia_tipo', 'utilizador_id',
    ];

    /** @return BelongsTo<Produto, $this> */
    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    /** @return BelongsTo<User, $this> */
    public function utilizador(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return Builder<MovimentoStock> */
    public function scopePorTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }

    /** @return Builder<MovimentoStock> */
    public function scopeRecentes(Builder $query, int $limite = 50): Builder
    {
        return $query->orderByDesc('created_at')->limit($limite);
    }

    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            self::TIPO_ENTRADA => 'Entrada',
            self::TIPO_SAIDA => 'Saída',
            self::TIPO_AJUSTE => 'Ajuste',
            self::TIPO_DEVOLUCAO => 'Devolução',
            default => ucfirst($this->tipo),
        };
    }
}
