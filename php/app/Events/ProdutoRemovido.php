<?php

namespace App\Events;

use App\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProdutoRemovido
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $produtoId,
        public string $produtoNome,
        public Tenant $tenant,
    ) {}
}
