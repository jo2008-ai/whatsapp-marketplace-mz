<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->tenant) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Acesso não autorizado.'], 401);
            }
            return redirect('/login')->with('error', 'Acesso não autorizado.');
        }

        if (!$user->tenant->activo) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Loja temporariamente indisponível.'], 403);
            }
            return redirect('/login')->with('error', 'Loja temporariamente indisponível.');
        }

        return $next($request);
    }
}
