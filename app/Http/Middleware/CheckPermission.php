<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  $permissao  Chave da permissão requerida (ex: 'clientes.ver' ou 'visitas.criar')
     */
    public function handle(Request $request, Closure $next, string $permissao): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Administrador possui super-acesso irrestrito
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Se a permissão contiver vírgula ou pipe (|), aceita se tiver qualquer uma
        if (str_contains($permissao, '|')) {
            $permissoes = explode('|', $permissao);
            if ($user->temAlgumaPermissao($permissoes)) {
                return $next($request);
            }
        } elseif ($user->temPermissao($permissao)) {
            return $next($request);
        }

        abort(403, "Acesso não autorizado. Seu perfil de acesso não possui a permissão: {$permissao}.");
    }
}
