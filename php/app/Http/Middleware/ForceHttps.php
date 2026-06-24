<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            // Render envia X-Forwarded-Proto: https
            $isSecure = $request->secure()
                || $request->header('X-Forwarded-Proto') === 'https'
                || $request->header('X-Forwarded-SSL') === 'on';

            if (!$isSecure) {
                return redirect()->secure($request->getRequestUri(), 301);
            }
        }

        return $next($request);
    }
}
