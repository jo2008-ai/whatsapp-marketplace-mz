<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TenantDataIsolation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->is('api/*') && $response instanceof Response) {
            $user = $request->user();
            if ($user && $user->tenant_id) {
                if ($request->isMethod('delete') || $request->isMethod('put')) {
                    try {
                        Log::channel('audit')->info('Tenant action', [
                            'user_id' => $user->id,
                            'tenant_id' => $user->tenant_id,
                            'method' => $request->method(),
                            'path' => $request->path(),
                            'ip' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                            'timestamp' => now()->toIso8601String(),
                        ]);
                    } catch (\Exception $e) {
                        // Silently fail if audit log channel is unavailable
                    }
                }

                if ($request->isMethod('delete')) {
                    try {
                        Log::channel('security')->info('Delete action', [
                            'user_id' => $user->id,
                            'tenant_id' => $user->tenant_id,
                            'path' => $request->path(),
                            'ip' => $request->ip(),
                            'timestamp' => now()->toIso8601String(),
                        ]);
                    } catch (\Exception $e) {
                        // Silently fail if security log channel is unavailable
                    }
                }
            }
        }

        return $response;
    }
}
