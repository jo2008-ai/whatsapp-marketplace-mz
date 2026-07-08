<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $codigo
 * @property string $nome
 * @property string $tipo
 * @property bool $is_filterable
 * @property bool $is_configurable
 * @property string|null $swatch_type
 * @property int $ordem
 */
class Atributo extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    protected $fillable = [
        'tenant_id', 'codigo', 'nome', 'tipo',
        'is_filterable', 'is_configurable', 'swatch_type', 'ordem',
    ];

    protected function casts(): array
    {
        return [
            'is_filterable' => 'boolean',
            'is_configurable' => 'boolean',
            'ordem' => 'integer',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return HasMany<AtributoValor, $this> */
    public function valores(): HasMany
    {
        return $this->hasMany(AtributoValor::class);
    }
}
