<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 1. Gate interceptor: admin tem acesso irrestrito, ou checa a permissão granular
        Gate::before(function ($user, string $ability) {
            if ($user->isAdmin()) {
                return true;
            }

            return $user->temPermissao($ability) ? true : null;
        });

        // 2. Diretiva Blade @permissao('clientes.criar')
        Blade::if('permissao', function (string $chave) {
            $user = auth()->user();

            return $user && $user->temPermissao($chave);
        });

        // 3. Diretiva Blade @algumapermissao(['clientes.ver', 'visitas.ver'])
        Blade::if('algumapermissao', function (array $chaves) {
            $user = auth()->user();

            return $user && $user->temAlgumaPermissao($chaves);
        });
    }
}
