<?php

namespace App\Events;

use App\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CategoriaRemovida
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $categoriaId,
        public string $categoriaNome,
        public ?Tenant $tenant,
    ) {}
}
