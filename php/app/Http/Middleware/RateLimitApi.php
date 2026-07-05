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
        $isLogin = $request->is('api/auth/login');
        $tipo = $isLogin ? 'login' : 'geral';
        $key = 'api:' . $tipo . ':' . ($request->user()->id ?? $request->ip());
        $maxAttempts = $isLogin ? 5 : 200;
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Demasiadas tentativas. Tenta novamente em {$seconds} segundos.",
            ], 429)->header('Retry-After', (string) $seconds);
        }

        RateLimiter::hit($key, $decayMinutes * 60);
        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', RateLimiter::remaining($key, $maxAttempts));
        return $response;
    }
}
