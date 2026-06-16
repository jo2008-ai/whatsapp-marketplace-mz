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
        $expected = config('services.admin.key', env('ADMIN_API_KEY', ''));

        if (!$provided || !$expected || !hash_equals($expected, $provided)) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Chave de administracao invalida.',
            ], 401);
        }

        return $next($request);
    }
}
