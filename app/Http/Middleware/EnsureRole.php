<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();
        abort_unless($usuario && $usuario->activo && $usuario->tieneRol(...$roles), 403, 'No tienes permisos para esta operación.');
        return $next($request);
    }
}
