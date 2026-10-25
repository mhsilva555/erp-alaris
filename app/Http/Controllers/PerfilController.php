<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PerfilController extends Controller
{
    public function index(Request $request)
    {
        $query = Perfil::withCount(['usuarios', 'permissoes']);

        if ($busca = $request->get('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('slug', 'like', "%{$busca}%")
                    ->orWhere('descricao', 'like', "%{$busca}%");
            });
        }

        $perfis = $query->orderByDesc('is_sistema')
            ->orderBy('nome')
            ->get();

        $totalPerfis = Perfil::count();
        $totalUsuariosVinculados = User::whereNotNull('perfil_id')->count();
        $totalPermissoes = Permissao::count();

        return view('perfis.index', compact('perfis', 'totalPerfis', 'totalUsuariosVinculados', 'totalPermissoes'));
    }

    public function create()
    {
        $catalogo = Permissao::catalogoModulos();
        $permissoesAgrupadas = Permissao::all()->groupBy('modulo');

        return view('perfis.create', compact('catalogo', 'permissoesAgrupadas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:100', 'unique:perfis,nome'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'permissoes' => ['nullable', 'array'],
            'permissoes.*' => ['string'],
        ]);

        $slug = Str::slug($validated['nome'], '_');
        $contador = 1;
        while (Perfil::where('slug', $slug)->exists()) {
            $slug = Str::slug($validated['nome'], '_').'_'.$contador++;
        }

        $perfil = Perfil::create([
            'nome' => $validated['nome'],
            'slug' => $slug,
            'descricao' => $validated['descricao'] ?? null,
            'is_sistema' => false,
        ]);

        if (! empty($validated['permissoes'])) {
            $perfil->sincronizarPermissoes($validated['permissoes']);
        }

        return redirect()->route('perfis.index')
            ->with('sucesso', "Perfil '{$perfil->nome}' cadastrado com sucesso com as permissões atribuídas.");
    }

    public function edit(Perfil $perfil)
    {
        $perfil->load('permissoes');
        $catalogo = Permissao::catalogoModulos();
        $permissoesAgrupadas = Permissao::all()->groupBy('modulo');
        $chavesAtivas = $perfil->permissoes->pluck('chave')->toArray();

        return view('perfis.edit', compact('perfil', 'catalogo', 'permissoesAgrupadas', 'chavesAtivas'));
    }

    public function update(Request $request, Perfil $perfil)
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:100', Rule::unique('perfis', 'nome')->ignore($perfil->id)],
            'descricao' => ['nullable', 'string', 'max:255'],
            'permissoes' => ['nullable', 'array'],
            'permissoes.*' => ['string'],
        ]);

        // Não altera o nome de perfis essenciais do sistema para não quebrar compatibilidade
        if (! $perfil->is_sistema) {
            $perfil->nome = $validated['nome'];
        }

        $perfil->descricao = $validated['descricao'] ?? null;
        $perfil->save();

        // Se for perfil 'admin', mantém sempre todas as permissões
        if ($perfil->is_sistema && $perfil->slug === 'admin') {
            $todasIds = Permissao::pluck('id')->toArray();
            $perfil->permissoes()->sync($todasIds);
        } else {
            $chaves = $validated['permissoes'] ?? [];
            $perfil->sincronizarPermissoes($chaves);
        }

        return redirect()->route('perfis.index')
            ->with('sucesso', "Perfil '{$perfil->nome}' atualizado com sucesso.");
    }

    public function destroy(Perfil $perfil)
    {
        if ($perfil->is_sistema) {
            return back()->withErrors(['error' => 'Perfis nativos do sistema não podem ser excluídos.']);
        }

        if ($perfil->usuarios()->count() > 0) {
            return back()->withErrors(['error' => "O perfil '{$perfil->nome}' possui {$perfil->usuarios()->count()} usuário(s) vinculado(s). Reatribua-os antes de excluir."]);
        }

        $nome = $perfil->nome;
        $perfil->permissoes()->detach();
        $perfil->delete();

        return redirect()->route('perfis.index')
            ->with('sucesso', "Perfil '{$nome}' removido com sucesso.");
    }
}
