<?php

namespace App\Models;

use App\Observers\CategoriaObserver;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class);
    }

    public function produtosDisponiveis(): HasMany
    {
        return $this->hasMany(Produto::class)->where('disponivel', true);
    }
}
