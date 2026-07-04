<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtributoValor extends Model
{
    protected $table = 'atributo_valores';

    protected $fillable = [
        'atributo_id', 'codigo', 'nome', 'valor', 'swatch_url', 'ordem',
    ];

    protected function casts(): array
    {
        return [
            'ordem' => 'integer',
        ];
    }

    public function atributo(): BelongsTo
    {
        return $this->belongsTo(Atributo::class);
    }
}
