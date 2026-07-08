<?php

namespace App\Models;

use App\Observers\TenantObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[ObservedBy(TenantObserver::class)]
/**
 * @property string $trial_termina_em
 * @property string|null $banner_promo_expira_em
 * @property bool $activo
 * @property bool $banner_promo_activo
 * @property string|null $banner_promo_titulo
 * @property bool $banner_global_activo
 * @property string|null $banner_global_titulo
 * @property string $nome_loja
 * @property string $estado
 * @property string|null $mensagem_boas_vindas
 * @property string|null $mensagem_erro_menu
 * @property string|null $mensagem_categoria_vazia
 * @property string|null $mensagem_pesquisa_vazia
 * @property string|null $mensagem_pedido_sucesso
 * @property string|null $mensagem_pedido_cancelado
 * @property string|null $mensagem_vendedores_indisponivel
 * @property string|null $mensagem_transferencia
 */
class Tenant extends Model
    {
        protected $fillable = [
            'nome_loja', 'email_dono', 'telefone_dono',
            'plano', 'estado', 'trial_termina_em',
            'max_produtos', 'max_numeros',
            'trial_aviso_3d', 'trial_aviso_1d',
            'instancia_whatsapp', 'activo',
            'logo_url', 'cor_primaria', 'timezone', 'moeda', 'idioma', 'mensagem_boas_vindas',
            'mensagem_erro_menu', 'mensagem_categoria_vazia', 'mensagem_pesquisa_vazia',
            'mensagem_pedido_sucesso', 'mensagem_pedido_cancelado',
            'mensagem_vendedores_indisponivel', 'mensagem_transferencia',
            'banner_promo_activo', 'banner_promo_titulo', 'banner_promo_texto',
            'banner_promo_cor', 'banner_promo_expira_em',
            'banner_global_activo', 'banner_global_titulo', 'banner_global_texto', 'banner_global_cor',
            'usar_typebot', 'typebot_bot_id', 'typebot_api_url',
        ];

        protected function casts(): array
        {
            return [
                'trial_termina_em' => 'date',
                'banner_promo_expira_em' => 'date',
                'activo' => 'boolean',
                'banner_promo_activo' => 'boolean',
                'banner_global_activo' => 'boolean',
            ];
        }

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            $tenant->uuid = $tenant->uuid ?? (string) Str::uuid();
        });
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Produto, $this> */
    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class);
    }

    /** @return HasMany<Categoria, $this> */
    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class);
    }

    /** @return HasMany<Vendedor, $this> */
    public function vendedores(): HasMany
    {
        return $this->hasMany(Vendedor::class);
    }

    /** @return HasMany<Encomenda, $this> */
    public function encomendas(): HasMany
    {
        return $this->hasMany(Encomenda::class);
    }

    /** @return HasMany<InstanciaWhatsApp, $this> */
    public function instancias(): HasMany
    {
        return $this->hasMany(InstanciaWhatsApp::class);
    }

    /** @return HasOne<InstanciaWhatsApp, $this> */
    public function instanciaAtiva(): HasOne
    {
        return $this->hasOne(InstanciaWhatsApp::class)->where('estado', 'conectada');
    }

    /** @return HasMany<SessaoBot, $this> */
    public function sessoes(): HasMany
    {
        return $this->hasMany(SessaoBot::class);
    }

    /** @return HasMany<LogBot, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(LogBot::class);
    }

    /** @return HasMany<Subscricao, $this> */
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
        if ($this->estado !== 'trial' || !$this->trial_termina_em) {
            return false;
        }

        $data = Carbon::parse($this->trial_termina_em);
        return $data->isFuture();
    }

    public function diasRestantesTrial(): int
    {
        if (!$this->estaEmTrial()) {
            return 0;
        }

        $data = Carbon::parse($this->trial_termina_em);
        return max(0, (int) now()->diffInDays($data, false));
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

        if ($this->banner_promo_expira_em) {
            $data = Carbon::parse($this->banner_promo_expira_em);
            if ($data->isPast()) {
                return false;
            }
        }

        return true;
    }

    public function bannerGlobalActivo(): bool
    {
        return $this->banner_global_activo && $this->banner_global_titulo;
    }
}
