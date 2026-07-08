<?php

namespace App\Events;

use App\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VendedorRemovido
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $vendedorId,
        public string $vendedorNome,
        public ?Tenant $tenant,
    ) {}
}
