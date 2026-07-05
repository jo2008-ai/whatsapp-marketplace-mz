<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'user_id', 'acao', 'entidade', 'entidade_id',
        'dados_antes', 'dados_depois', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'dados_antes' => 'array',
            'dados_depois' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function registrar(
        ?int $tenantId,
        ?int $userId,
        string $acao,
        string $entidade,
        ?int $entidadeId = null,
        ?array $antes = null,
        ?array $depois = null
    ): self {
        $request = request();
        return self::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'acao' => $acao,
            'entidade' => $entidade,
            'entidade_id' => $entidadeId,
            'dados_antes' => $antes,
            'dados_depois' => $depois,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
