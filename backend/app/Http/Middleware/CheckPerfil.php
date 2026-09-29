<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPerfil
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$perfis
     */
    public function handle(Request $request, Closure $next, ...$perfis): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Nao autenticado.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!empty($perfis) && !in_array($user->perfil, $perfis)) {
            return response()->json([
                'message' => 'Acesso negado. Voce nao possui permissao para acessar este recurso.'
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
