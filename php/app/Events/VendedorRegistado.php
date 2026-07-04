<?php

namespace App\Events;

use App\Models\Tenant;
use App\Models\Vendedor;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VendedorRegistado
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Vendedor $vendedor,
        public Tenant $tenant,
    ) {}
}
