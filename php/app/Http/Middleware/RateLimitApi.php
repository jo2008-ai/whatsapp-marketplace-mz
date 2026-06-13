<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'api:' . ($request->user()?->id ?? $request->ip());
        $maxAttempts = $request->is('api/auth/login') ? 5 : 60;
        $decayMinutes = $request->is('api/auth/login') ? 15 : 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Demasiadas tentativas. Tenta novamente em {$seconds} segundos.",
            ], 429)->header('Retry-After', $seconds);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', RateLimiter::remaining($key, $maxAttempts));

        return $response;
    }
}
