<?php

namespace App\Http\Controllers;

use App\Models\CategoriaEquipamento;
use App\Models\Equipamento;
use App\Models\EquipamentoComodato;
use Illuminate\Http\Request;

class EquipamentoController extends Controller
{
    public function index(Request $request)
    {
        $query = Equipamento::with(['categoria'])->withCount('comodatos');

        if ($busca = $request->get('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('modelo', 'like', "%{$busca}%")
                    ->orWhere('descricao', 'like', "%{$busca}%");
            });
        }

        if ($tipo = $request->get('tipo')) {
            $query->where('tipo', $tipo);
        }

        if ($request->boolean('disponiveis')) {
            $query->where('quantidade_disponivel', '>', 0);
        }

        $equipamentos = $query->orderBy('nome')->paginate(15)->withQueryString();

        // Indicadores do almoxarifado
        $totalEquipamentos = Equipamento::count();
        $totalEstoque = (int) Equipamento::sum('quantidade_total');
        $totalDisponivel = (int) Equipamento::sum('quantidade_disponivel');
        $totalComodato = (int) EquipamentoComodato::sum('quantidade');

        $categorias = CategoriaEquipamento::withCount('equipamentos')->orderBy('nome')->get();
        $tipos = $categorias->pluck('nome', 'slug')->toArray();
        if (empty($tipos)) {
            $tipos = Equipamento::TIPOS;
        }

        return view('equipamentos.index', compact(
            'equipamentos',
            'totalEquipamentos',
            'totalEstoque',
            'totalDisponivel',
            'totalComodato',
            'tipos',
            'categorias'
        ));
    }

    public function create()
    {
        $categorias = CategoriaEquipamento::ativos()->orderBy('nome')->get();
        $tipos = $categorias->pluck('nome', 'slug')->toArray();
        if (empty($tipos)) {
            $tipos = Equipamento::TIPOS;
        }

        return view('equipamentos.create', compact('tipos', 'categorias'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'tipo' => 'required|string|max:100',
            'quantidade_total' => 'required|integer|min:0',
            'quantidade_disponivel' => 'nullable|integer|min:0',
            'valor_unitario' => 'nullable|numeric|min:0',
            'descricao' => 'nullable|string',
            'ativo' => 'boolean',
        ]);

        $validated['ativo'] = $request->has('ativo');
        $validated['valor_unitario'] = (float) ($validated['valor_unitario'] ?? 0);

        if (! isset($validated['quantidade_disponivel']) || $validated['quantidade_disponivel'] === null || $validated['quantidade_disponivel'] === '') {
            $validated['quantidade_disponivel'] = $validated['quantidade_total'];
        }

        Equipamento::create($validated);

        return redirect()->route('equipamentos.index')
            ->with('sucesso', "Equipamento '{$validated['nome']}' cadastrado com sucesso.");
    }

    public function edit(Equipamento $equipamento)
    {
        $categorias = CategoriaEquipamento::ativos()->orderBy('nome')->get();
        $tipos = $categorias->pluck('nome', 'slug')->toArray();
        if (empty($tipos)) {
            $tipos = Equipamento::TIPOS;
        }

        return view('equipamentos.edit', compact('equipamento', 'tipos', 'categorias'));
    }

    public function update(Request $request, Equipamento $equipamento)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'tipo' => 'required|string|max:100',
            'quantidade_total' => 'required|integer|min:0',
            'quantidade_disponivel' => 'required|integer|min:0',
            'valor_unitario' => 'nullable|numeric|min:0',
            'descricao' => 'nullable|string',
            'ativo' => 'boolean',
        ]);

        $validated['ativo'] = $request->has('ativo');
        $validated['valor_unitario'] = (float) ($validated['valor_unitario'] ?? 0);

        $equipamento->update($validated);

        return redirect()->route('equipamentos.index')
            ->with('sucesso', "Equipamento '{$equipamento->nome}' atualizado com sucesso.");
    }

    public function destroy(Equipamento $equipamento)
    {
        if ($equipamento->comodatos()->exists()) {
            return redirect()->route('equipamentos.index')
                ->with('erro', "Não é possível excluir o equipamento '{$equipamento->nome}' porque existem contratos de comodato ativos vinculados a ele.");
        }

        $equipamento->delete();

        return redirect()->route('equipamentos.index')
            ->with('sucesso', "Equipamento '{$equipamento->nome}' removido com sucesso.");
    }
}
