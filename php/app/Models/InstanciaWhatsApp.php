<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstanciaWhatsApp extends Model
{
    protected $table = 'instancias_whatsapp';

    protected $fillable = [
        'tenant_id', 'numero_whatsapp', 'nome_instancia',
        'evolution_instance_name', 'estado', 'qr_code_url', 'conectada_em',
    ];

    protected function casts(): array
    {
        return [
            'conectada_em' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
