<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscricao extends Model
{
    protected $table = 'subscricoes';

    protected $fillable = [
        'tenant_id', 'plano', 'preco_mensal', 'data_inicio', 'data_fim',
        'estado', 'metodo_pagamento', 'referencia_pagamento',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'preco_mensal' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
