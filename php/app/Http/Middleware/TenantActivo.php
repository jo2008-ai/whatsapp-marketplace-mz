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
            return redirect('/login')->with('error', 'Acesso não autorizado.');
        }

        if (!$user->tenant->ativo()) {
            return redirect('/login')->with('error', 'A subscrição expirou. Renova para continuar.');
        }

        return $next($request);
    }
}
