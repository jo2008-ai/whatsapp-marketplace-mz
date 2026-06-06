<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Tenant extends Model
{
    protected $fillable = [
        'nome_loja', 'email_dono', 'telefone_dono', 'plano', 'estado',
        'trial_termina_em', 'max_produtos', 'max_numeros', 'logo_url',
        'cor_primaria', 'mensagem_boas_vindas',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            $tenant->uuid = $tenant->uuid ?? (string) Str::uuid();
            if ($tenant->estado === 'trial' && !$tenant->trial_termina_em) {
                $tenant->trial_termina_em = now()->addDays(7);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class);
    }

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class);
    }

    public function vendedores(): HasMany
    {
        return $this->hasMany(Vendedor::class);
    }

    public function encomendas(): HasMany
    {
        return $this->hasMany(Encomenda::class);
    }

    public function subscricoes(): HasMany
    {
        return $this->hasMany(Subscricao::class);
    }

    public function instancias(): HasMany
    {
        return $this->hasMany(InstanciaWhatsApp::class);
    }

    public function instanciaAtiva(): HasOne
    {
        return $this->hasOne(InstanciaWhatsApp::class)->where('estado', 'conectada');
    }

    public function sessoes(): HasMany
    {
        return $this->hasMany(SessaoBot::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LogBot::class);
    }

    public function ativo(): bool
    {
        if ($this->estado === 'activo') {
            return true;
        }
        if ($this->estado === 'trial' && $this->trial_termina_em && $this->trial_termina_em->isFuture()) {
            return true;
        }
        return false;
    }

    public function podeAdicionarProduto(): bool
    {
        return $this->produtos()->count() < $this->max_produtos;
    }

    public function podeAdicionarNumero(): bool
    {
        if ($this->max_numeros >= 99999) {
            return true;
        }
        return $this->instancias()->count() < $this->max_numeros;
    }

    public function subscricaoAtiva(): ?Subscricao
    {
        return $this->subscricoes()
            ->where('estado', 'activa')
            ->where('data_fim', '>=', now())
            ->latest()
            ->first();
    }

    public function trialExpirado(): bool
    {
        return $this->estado === 'trial'
            && $this->trial_termina_em
            && $this->trial_termina_em->isPast();
    }
}
