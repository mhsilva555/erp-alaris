<?php

namespace App\Http\Controllers;

use App\Models\Tecnico;
use Illuminate\Http\Request;

class TecnicoController extends Controller
{
    public function index(Request $request)
    {
        $query = Tecnico::query();

        if ($busca = $request->get('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%")
                    ->orWhere('especialidade', 'like', "%{$busca}%");
            });
        }

        $tecnicos = $query->orderBy('nome')->paginate(15);

        return view('tecnicos.index', compact('tecnicos'));
    }

    public function create()
    {
        return view('tecnicos.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'especialidade' => 'nullable|string|max:255',
            'valor_diaria_padrao' => 'nullable|numeric|min:0',
            'ativo' => 'boolean',
        ]);

        $validated['ativo'] = $request->has('ativo');
        $validated['valor_diaria_padrao'] = $validated['valor_diaria_padrao'] ?? 50.00;

        Tecnico::create($validated);

        return redirect()->route('tecnicos.index')
            ->with('sucesso', 'Técnico cadastrado com sucesso.');
    }

    public function edit(Tecnico $tecnico)
    {
        return view('tecnicos.edit', compact('tecnico'));
    }

    public function update(Request $request, Tecnico $tecnico)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'especialidade' => 'nullable|string|max:255',
            'valor_diaria_padrao' => 'nullable|numeric|min:0',
            'ativo' => 'boolean',
        ]);

        $validated['ativo'] = $request->has('ativo');
        $validated['valor_diaria_padrao'] = $validated['valor_diaria_padrao'] ?? 50.00;

        $tecnico->update($validated);

        return redirect()->route('tecnicos.index')
            ->with('sucesso', 'Técnico atualizado com sucesso.');
    }

    public function destroy(Tecnico $tecnico)
    {
        $tecnico->delete();

        return redirect()->route('tecnicos.index')
            ->with('sucesso', 'Técnico removido com sucesso.');
    }
}
