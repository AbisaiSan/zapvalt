<?php

namespace App\Http\Middleware;

use App\Support\AppLock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Com a senha ativa, nada abre sem desbloquear: páginas vão para /desbloquear,
 * a API e a mídia respondem 423.
 */
class EnsureUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('unlock', 'api.unlock') || AppLock::isUnlocked($request->session())) {
            return $next($request);
        }

        if ($request->is('api/*', 'media/*') || ($request->expectsJson() && ! $request->header('X-Inertia'))) {
            return response()->json(['message' => 'O ZapVault está bloqueado. Digite a senha para continuar.'], 423);
        }

        return redirect()->route('unlock');
    }
}
