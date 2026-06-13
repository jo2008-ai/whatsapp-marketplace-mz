<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $instanceName = $request->input('instance_name', 'unknown');
        $numero = $request->input('numero', 'unknown');

        $key = "webhook:{$instanceName}:{$numero}";
        $maxAttempts = 30;
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            logger()->channel('security')->warning('Rate limit webhook excedido', [
                'instance_name' => $instanceName,
                'numero' => $numero,
                'ip' => $request->ip(),
                'retry_after' => $seconds,
            ]);

            return response()->json([
                'status' => 'rate_limited',
                'retry_after' => $seconds,
            ], 429)->header('Retry-After', $seconds);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set(
            'X-RateLimit-Remaining',
            RateLimiter::remaining($key, $maxAttempts)
        );

        return $response;
    }
}
