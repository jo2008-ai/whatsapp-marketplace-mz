<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function valores(): HasMany
    {
        return $this->hasMany(AtributoValor::class);
    }
}
