<?php

namespace App\Models;

use App\Observers\CategoriaObserver;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $nome
 * @property string|null $icone
 * @property bool $ativo
 * @property int $ordem
 * @property int $produtos_count
 */
#[ObservedBy(CategoriaObserver::class)]
class Categoria extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    protected $fillable = [
        'tenant_id', 'nome', 'descricao', 'icone', 'ativo', 'ordem',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return HasMany<Produto, $this> */
    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class);
    }

    /** @return HasMany<Produto, $this> */
    public function produtosDisponiveis(): HasMany
    {
        return $this->hasMany(Produto::class)->where('disponivel', true);
    }
}
