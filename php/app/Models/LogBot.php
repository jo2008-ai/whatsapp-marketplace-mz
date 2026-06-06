<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogBot extends Model
{
    protected $table = 'logs_bot';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'numero_whatsapp', 'direcao', 'mensagem', 'estado_bot', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
