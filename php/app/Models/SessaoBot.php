<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessaoBot extends Model
{
    protected $table = 'sessoes_bot';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'numero_whatsapp', 'estado', 'dados', 'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'dados' => 'array',
            'updated_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function obter(int $tenantId, string $numero): self
    {
        return self::firstOrCreate(
            ['tenant_id' => $tenantId, 'numero_whatsapp' => $numero],
            ['estado' => 'inicio', 'dados' => []]
        );
    }

    public function atualizarEstado(string $estado, array $dados = []): void
    {
        $this->update([
            'estado' => $estado,
            'dados' => $dados,
            'updated_at' => now(),
        ]);
    }
}
