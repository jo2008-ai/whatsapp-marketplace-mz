<?php

namespace App\Providers;

use App\Events\EncomendaActualizada;
use App\Events\NovaEncomenda;
use App\Listeners\NotificarClienteWhatsApp;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        NovaEncomenda::class => [],
        EncomendaActualizada::class => [
            NotificarClienteWhatsApp::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
