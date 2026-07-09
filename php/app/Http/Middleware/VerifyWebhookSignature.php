<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.evolution.webhook_secret', config('services.waha.webhook_secret'));

        if (empty($secret)) {
            Log::error('Webhook secret não configurado — pedidos rejeitados');
            return response()->json(['error' => 'Webhook secret não configurado'], 500);
        }

        $signature = $request->header('X-Hub-Signature-256')
                  ?? $request->header('X-Webhook-Signature');

        if (!$signature) {
            Log::warning('Webhook sem assinatura', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Missing signature'], 401);
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Webhook com assinatura inválida', [
                'ip' => $request->ip(),
                'expected' => substr($expectedSignature, 0, 20) . '...',
                'got' => substr($signature, 0, 20) . '...',
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
