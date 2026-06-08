<?php
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
        $middleware->trustProxies(at: ['*']);
        $middleware->alias([
            'tenant.activo'    => \App\Http\Middleware\TenantActivo::class,
            'super.admin'      => \App\Http\Middleware\SuperAdmin::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'rate.limit'       => \App\Http\Middleware\RateLimitApi::class,
            'webhook.verify'   => \App\Http\Middleware\VerifyWebhookSignature::class,
            'webhook.rate'     => \App\Http\Middleware\RateLimitWebhook::class,
            'tenant.isolation' => \App\Http\Middleware\TenantDataIsolation::class,
            'force.https'      => \App\Http\Middleware\ForceHttps::class,
            'suspicious'       => \App\Http\Middleware\SuspiciousActivity::class,
        ]);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\SuspiciousActivity::class);
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\RateLimitApi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
