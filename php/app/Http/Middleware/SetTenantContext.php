<?php

namespace App\Http\Middleware;

use App\Context\TenantContext;
use App\Utilities\TenantOverrider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->tenant) {
            $context = TenantContext::make($user->tenant, $request);
            App::instance(TenantContext::class, $context);

            TenantOverrider::load($user->tenant);
        } else {
            $context = TenantContext::empty();
            App::instance(TenantContext::class, $context);
        }

        return $next($request);
    }
}
