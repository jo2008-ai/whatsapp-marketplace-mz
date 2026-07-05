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
        $ip = $request->ip();
        $key = "webhook:{$ip}";
        $maxAttempts = 60;
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            logger()->channel('security')->warning('Rate limit webhook excedido', [
                'ip' => $ip,
                'retry_after' => $seconds,
            ]);

            return response()->json([
                'status' => 'rate_limited',
                'retry_after' => $seconds,
            ], 429)->header('Retry-After', (string) $seconds);
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
