<?php

namespace App\Models;

use App\Observers\ProdutoObserver;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ProdutoObserver::class)]
class Produto extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

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

    public function variantes(): HasMany
    {
        return $this->hasMany(ProdutoVariante::class);
    }

    public function variantesDisponiveis(): HasMany
    {
        return $this->hasMany(ProdutoVariante::class)->where('disponivel', true)->where('stock', '>', 0);
    }

    public function temVariantes(): bool
    {
        return $this->variantes()->count() > 0 || !empty($this->cores) || !empty($this->tamanhos);
    }

    public function temVariantesNovas(): bool
    {
        return $this->variantes()->count() > 0;
    }

    public function temCores(): bool
    {
        if ($this->temVariantesNovas()) {
            return $this->variantesDisponiveis()
                ->whereJsonContainsPath('atributos', '$[*].nome', 'like', '%cor%')
                ->count() > 0;
        }

        return !empty($this->cores);
    }

    public function temTamanhos(): bool
    {
        if ($this->temVariantesNovas()) {
            return $this->variantesDisponiveis()
                ->whereJsonContainsPath('atributos', '$[*].nome', 'like', '%tamanho%')
                ->count() > 0;
        }

        return !empty($this->tamanhos);
    }

    public function obterCoresDisponiveis(): array
    {
        if ($this->temVariantesNovas()) {
            $cores = [];
            foreach ($this->variantesDisponiveis()->get() as $variante) {
                foreach ($variante->atributos as $atributo) {
                    if (isset($atributo['nome']) && strtolower($atributo['nome']) === 'cor') {
                        $cores[] = $atributo['valor'];
                    }
                }
            }
            return array_unique($cores);
        }

        return $this->cores ?? [];
    }

    public function obterTamanhosDisponiveis(): array
    {
        if ($this->temVariantesNovas()) {
            $tamanhos = [];
            foreach ($this->variantesDisponiveis()->get() as $variante) {
                foreach ($variante->atributos as $atributo) {
                    if (isset($atributo['nome']) && strtolower($atributo['nome']) === 'tamanho') {
                        $tamanhos[] = $atributo['valor'];
                    }
                }
            }
            return array_unique($tamanhos);
        }

        return $this->tamanhos ?? [];
    }

    public function obterVariante(?string $cor = null, ?string $tamanho = null): ?ProdutoVariante
    {
        if ($this->temVariantesNovas()) {
            $query = $this->variantesDisponiveis();

            if ($cor) {
                $query->whereJsonContains('atributos', ['nome' => 'Cor', 'valor' => $cor]);
            }

            if ($tamanho) {
                $query->whereJsonContains('atributos', ['nome' => 'Tamanho', 'valor' => $tamanho]);
            }

            return $query->first();
        }

        return null;
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
        if ($this->temVariantesNovas()) {
            return $this->variantesDisponiveis()->sum('stock') > 0;
        }

        return $this->stock > 0;
    }
}
