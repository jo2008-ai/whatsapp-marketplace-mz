<?php

namespace App\Events;

use App\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantActivado
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
    ) {}
}
