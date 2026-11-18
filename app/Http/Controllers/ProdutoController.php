<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    public function index(Request $request)
    {
        $query = Produto::query();

        if ($busca = $request->get('busca')) {
            $query->where('nome', 'like', "%{$busca}%")
                ->orWhere('descricao', 'like', "%{$busca}%");
        }

        if ($natureza = $request->get('natureza')) {
            $query->where('natureza', $natureza);
        }

        $produtos = $query->orderBy('nome')->paginate(15)->withQueryString();

        return view('produtos.index', compact('produtos'));
    }

    public function create()
    {
        return view('produtos.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'natureza' => 'required|string|in:liquido,solido,unidade',
            'unidade_armazenamento' => 'required|string|max:20',
            'unidade_aplicacao' => 'required|string|max:20',
            'fator_conversao' => 'nullable|numeric|min:0.0001',
            'quantidade_estoque' => 'required|numeric|min:0',
            'estoque_minimo' => 'required|numeric|min:0',
            'preco_custo_unitario' => 'required|numeric|min:0',
            'descricao' => 'nullable|string',
        ]);

        if (empty($validated['fator_conversao']) || (float) $validated['fator_conversao'] <= 0) {
            $validated['fator_conversao'] = Produto::calcularFatorPadrao(
                $validated['natureza'],
                $validated['unidade_armazenamento'],
                $validated['unidade_aplicacao']
            );
        }

        $validated['unidade'] = $validated['unidade_armazenamento'];

        Produto::create($validated);

        return redirect()->route('produtos.index')
            ->with('sucesso', 'Produto cadastrado com sucesso.');
    }

    public function edit(Produto $produto)
    {
        return view('produtos.edit', compact('produto'));
    }

    public function update(Request $request, Produto $produto)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'natureza' => 'required|string|in:liquido,solido,unidade',
            'unidade_armazenamento' => 'required|string|max:20',
            'unidade_aplicacao' => 'required|string|max:20',
            'fator_conversao' => 'nullable|numeric|min:0.0001',
            'quantidade_estoque' => 'required|numeric|min:0',
            'estoque_minimo' => 'required|numeric|min:0',
            'preco_custo_unitario' => 'required|numeric|min:0',
            'descricao' => 'nullable|string',
        ]);

        if (empty($validated['fator_conversao']) || (float) $validated['fator_conversao'] <= 0) {
            $validated['fator_conversao'] = Produto::calcularFatorPadrao(
                $validated['natureza'],
                $validated['unidade_armazenamento'],
                $validated['unidade_aplicacao']
            );
        }

        $validated['unidade'] = $validated['unidade_armazenamento'];

        $produto->update($validated);

        return redirect()->route('produtos.index')
            ->with('sucesso', 'Produto atualizado com sucesso.');
    }

    public function destroy(Produto $produto)
    {
        if ($produto->visitas()->exists()) {
            return back()->with('erro', "O produto '{$produto->nome}' não pode ser excluído porque já foi utilizado em ordem(ns) de serviço.");
        }

        if ($produto->contratos()->withTrashed()->exists()) {
            return back()->with('erro', "O produto '{$produto->nome}' não pode ser excluído porque está vinculado a contrato(s) ou proposta(s).");
        }

        if ($produto->comodatos()->exists()) {
            return back()->with('erro', "O produto '{$produto->nome}' não pode ser excluído porque está vinculado a equipamento(s) em comodato.");
        }

        try {
            $produto->delete();
        } catch (QueryException $e) {
            return back()->with('erro', "O produto '{$produto->nome}' não pode ser excluído porque possui registros vinculados.");
        }

        return redirect()->route('produtos.index')
            ->with('sucesso', 'Produto removido com sucesso.');
    }
}
