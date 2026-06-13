<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $provided = $request->header('X-Admin-Key');

        if (!$provided || !hash_equals(env('ADMIN_API_KEY', ''), $provided)) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Chave de administração inválida.',
            ], 401);
        }

        return $next($request);
    }
}
