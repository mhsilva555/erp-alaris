<?php

namespace App\Http\Controllers;

use App\Models\Produto;
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
            'unidade_armazenamento' => 'required|string|max:10',
            'unidade_aplicacao' => 'required|string|max:10',
            'fator_conversao' => 'required|numeric|min:0.0001',
            'quantidade_estoque' => 'required|numeric|min:0',
            'estoque_minimo' => 'required|numeric|min:0',
            'preco_custo_unitario' => 'required|numeric|min:0',
            'descricao' => 'nullable|string',
        ]);

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
            'unidade_armazenamento' => 'required|string|max:10',
            'unidade_aplicacao' => 'required|string|max:10',
            'fator_conversao' => 'required|numeric|min:0.0001',
            'quantidade_estoque' => 'required|numeric|min:0',
            'estoque_minimo' => 'required|numeric|min:0',
            'preco_custo_unitario' => 'required|numeric|min:0',
            'descricao' => 'nullable|string',
        ]);

        $validated['unidade'] = $validated['unidade_armazenamento'];

        $produto->update($validated);

        return redirect()->route('produtos.index')
            ->with('sucesso', 'Produto atualizado com sucesso.');
    }

    public function destroy(Produto $produto)
    {
        $produto->delete();

        return redirect()->route('produtos.index')
            ->with('sucesso', 'Produto removido com sucesso.');
    }
}
