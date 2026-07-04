<?php

namespace App\Providers;

use App\Context\TenantContext;
use App\Models\Categoria;
use App\Models\Encomenda;
use App\Models\Produto;
use App\Models\Vendedor;
use App\Policies\CategoriaPolicy;
use App\Policies\EncomendaPolicy;
use App\Policies\ProdutoPolicy;
use App\Policies\VendedorPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class, function () {
            return TenantContext::empty();
        });
    }

    public function boot(): void
    {
        Gate::policy(Produto::class,   ProdutoPolicy::class);
        Gate::policy(Encomenda::class, EncomendaPolicy::class);
        Gate::policy(Categoria::class, CategoriaPolicy::class);
        Gate::policy(Vendedor::class,  VendedorPolicy::class);
    }
}
