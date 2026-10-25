<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $tecnicos = Tecnico::where('ativo', true)->orderBy('nome')->get();
        $perfis = Perfil::orderBy('nome')->get();
        $usuarios = $user->isAdmin() ? User::with(['tecnico', 'perfil'])->orderBy('name')->get() : collect();

        return view('perfil.edit', compact('user', 'tecnicos', 'usuarios', 'perfis'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'current_password' => ['nullable', 'required_with:password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if (! empty($validated['current_password'])) {
            if (! Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'A senha atual está incorreta.']);
            }
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('perfil.edit')->with('sucesso', 'Perfil atualizado com sucesso.');
    }

    /**
     * Criação de login para técnico ou administrador por um Admin.
     */
    public function storeUsuario(Request $request)
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'perfil_id' => ['nullable', 'exists:perfis,id'],
            'role' => ['nullable', 'string'],
            'tecnico_id' => ['nullable', 'exists:tecnicos,id'],
        ]);

        // Resolve Perfil e Role
        $perfil = null;
        if (! empty($validated['perfil_id'])) {
            $perfil = Perfil::find($validated['perfil_id']);
        } elseif (! empty($validated['role'])) {
            $perfil = Perfil::where('slug', $validated['role'])->first();
        }

        $role = $perfil ? ($perfil->slug === 'tecnico' ? User::ROLE_TECNICO : ($perfil->slug === 'admin' ? User::ROLE_ADMIN : $perfil->slug)) : ($validated['role'] ?? User::ROLE_ADMIN);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $role,
            'perfil_id' => $perfil?->id,
            'tecnico_id' => ($role === User::ROLE_TECNICO || ($perfil && $perfil->slug === 'tecnico')) ? ($validated['tecnico_id'] ?? null) : null,
        ]);

        return redirect()->route('perfil.edit')->with('sucesso', 'Novo usuário criado com sucesso.');
    }

    /**
     * Exclui um usuário.
     */
    public function destroyUsuario(User $user)
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'Você não pode excluir sua própria conta enquanto logado.']);
        }

        $user->delete();

        return redirect()->route('perfil.edit')->with('sucesso', 'Usuário removido com sucesso.');
    }
}
