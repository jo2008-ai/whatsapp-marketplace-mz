<?php

namespace App\Events;

use App\Models\Categoria;
use App\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CategoriaActualizada
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Categoria $categoria,
        public ?Tenant $tenant,
    ) {}
}
