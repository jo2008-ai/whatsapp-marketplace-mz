<?php

namespace App\Models;

use App\Observers\VendedorObserver;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $nome
 * @property string $numero_whatsapp
 * @property string|null $descricao
 * @property bool $ativo
 */
#[ObservedBy(VendedorObserver::class)]
class Vendedor extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    protected $table = 'vendedores';

    protected $fillable = [
        'tenant_id', 'nome', 'numero_whatsapp', 'descricao', 'ativo',
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

    /** @return HasMany<Encomenda, $this> */
    public function encomendas(): HasMany
    {
        return $this->hasMany(Encomenda::class);
    }
}
