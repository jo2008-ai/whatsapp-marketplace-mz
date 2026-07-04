<?php

namespace App\Providers;

use App\Events\EncomendaActualizada;
use App\Events\NovaEncomenda;
use App\Events\ProdutoCriado;
use App\Events\ProdutoActualizado;
use App\Events\ProdutoRemovido;
use App\Events\CategoriaCriada;
use App\Events\CategoriaActualizada;
use App\Events\CategoriaRemovida;
use App\Events\VendedorRegistado;
use App\Events\VendedorRemovido;
use App\Events\TenantActivado;
use App\Events\TenantSuspenso;
use App\Listeners\NotificarClienteWhatsApp;
use App\Listeners\LimparCacheProdutos;
use App\Listeners\NotificarAdminNovoProduto;
use App\Listeners\NotificarAdminNovoVendedor;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Encomenda
        NovaEncomenda::class => [],
        EncomendaActualizada::class => [
            NotificarClienteWhatsApp::class,
        ],

        // Produto
        ProdutoCriado::class => [
            LimparCacheProdutos::class,
            NotificarAdminNovoProduto::class,
        ],
        ProdutoActualizado::class => [
            LimparCacheProdutos::class,
        ],
        ProdutoRemovido::class => [
            LimparCacheProdutos::class,
        ],

        // Categoria
        CategoriaCriada::class => [],
        CategoriaActualizada::class => [],
        CategoriaRemovida::class => [],

        // Vendedor
        VendedorRegistado::class => [
            NotificarAdminNovoVendedor::class,
        ],
        VendedorRemovido::class => [],

        // Tenant
        TenantActivado::class => [],
        TenantSuspenso::class => [],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
