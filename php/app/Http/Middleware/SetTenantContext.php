<?php

namespace App\Http\Middleware;

use App\Context\TenantContext;
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
        } else {
            $context = TenantContext::empty();
            App::instance(TenantContext::class, $context);
        }

        return $next($request);
    }
}
