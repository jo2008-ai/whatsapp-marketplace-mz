<?php

use App\Models\Categoria;
use App\Models\Encomenda;
use App\Models\Produto;
use App\Models\Vendedor;
use App\Policies\CategoriaPolicy;
use App\Policies\EncomendaPolicy;
use App\Policies\ProdutoPolicy;
use App\Policies\VendedorPolicy;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        channels: __DIR__.'/../routes/channels.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tenant.activo' => \App\Http\Middleware\TenantActivo::class,
            'super.admin' => \App\Http\Middleware\SuperAdmin::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'rate.limit' => \App\Http\Middleware\RateLimitApi::class,
            'webhook.verify' => \App\Http\Middleware\VerifyWebhookSignature::class,
            'webhook.rate' => \App\Http\Middleware\RateLimitWebhook::class,
            'tenant.isolation' => \App\Http\Middleware\TenantDataIsolation::class,
            'force.https' => \App\Http\Middleware\ForceHttps::class,
            'suspicious' => \App\Http\Middleware\SuspiciousActivity::class,
        ]);

        // Security headers global
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Detectar atividade suspeita globalmente
        $middleware->append(\App\Http\Middleware\SuspiciousActivity::class);

        // CORS para API
        $middleware->api(prepend: [
            \App\Http\Middleware\RateLimitApi::class,
        ]);

        // Webhook rate limiting
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // Registar políticas
        \Illuminate\Support\Facades\Gate::policy(Produto::class, ProdutoPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(Encomenda::class, EncomendaPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(Categoria::class, CategoriaPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(Vendedor::class, VendedorPolicy::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
