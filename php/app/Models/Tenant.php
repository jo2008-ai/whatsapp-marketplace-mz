<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Tenant extends Model
{
    protected $fillable = [
        'nome_loja', 'email_dono', 'telefone_dono',
        'plano', 'estado', 'trial_termina_em',
        'max_produtos', 'max_numeros',
        'trial_aviso_3d', 'trial_aviso_1d',
        'instancia_whatsapp', 'activo',
        'logo_url', 'cor_primaria', 'mensagem_boas_vindas',
        'banner_promo_activo', 'banner_promo_titulo', 'banner_promo_texto',
        'banner_promo_cor', 'banner_promo_expira_em',
        'banner_global_activo', 'banner_global_titulo', 'banner_global_texto', 'banner_global_cor',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            $tenant->uuid = $tenant->uuid ?? (string) Str::uuid();
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

    public function subscricoes(): HasMany
    {
        return $this->hasMany(Subscricao::class);
    }

    public function subscricaoAtiva(): ?Subscricao
    {
        return $this->subscricoes()
            ->where('estado', 'activa')
            ->where('data_fim', '>', now())
            ->latest()
            ->first();
    }

    public function estaEmTrial(): bool
    {
        return $this->estado === 'trial'
            && $this->trial_termina_em
            && $this->trial_termina_em->isFuture();
    }

    public function diasRestantesTrial(): int
    {
        if (!$this->estaEmTrial()) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->trial_termina_em, false));
    }

    public function ativo(): bool
    {
        return $this->activo === true;
    }

    public function bannerPromoActivo(): bool
    {
        if (!$this->banner_promo_activo || !$this->banner_promo_titulo) {
            return false;
        }

        if ($this->banner_promo_expira_em && $this->banner_promo_expira_em->isPast()) {
            return false;
        }

        return true;
    }

    public function bannerGlobalActivo(): bool
    {
        return $this->banner_global_activo && $this->banner_global_titulo;
    }
}
