<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public function ativo(): bool
    {
        return $this->activo === true;
    }
}
