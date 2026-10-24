<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        // Garante que exista pelo menos um administrador se a tabela estiver vazia
        if (User::count() === 0) {
            User::create([
                'name' => 'Administrador',
                'email' => 'admin@alaris.com',
                'password' => Hash::make('12345678'),
                'role' => User::ROLE_ADMIN,
            ]);
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if (str_contains(session()->get('url.intended', ''), '/notificacoes')) {
                session()->forget('url.intended');
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'As credenciais informadas não coincidem com nossos registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('sucesso', 'Sessão encerrada com sucesso.');
    }
}
