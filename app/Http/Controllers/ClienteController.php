<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::query();

        if ($busca = $request->get('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('documento', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%")
                    ->orWhere('telefone', 'like', "%{$busca}%")
                    ->orWhere('cidade', 'like', "%{$busca}%");
            });
        }

        if ($status = $request->get('status_prospeccao')) {
            $query->where('status_prospeccao', $status);
        }

        if ($origem = $request->get('origem')) {
            $query->where('origem', $origem);
        }

        $clientes = $query->orderBy('nome')->paginate(15)->withQueryString();

        return view('clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('clientes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'documento' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'endereco' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:2',
            'cep' => 'nullable|string|max:10',
            'origem' => 'nullable|string|max:50',
            'periodicidade' => 'nullable|string|max:50',
            'status_prospeccao' => 'nullable|string|max:50',
            'data_followup' => 'nullable|date',
            'observacoes' => 'nullable|string',
        ]);

        $validated['origem'] = $validated['origem'] ?? 'outros';
        $validated['periodicidade'] = $validated['periodicidade'] ?? 'avulso';
        $validated['status_prospeccao'] = $validated['status_prospeccao'] ?? 'ativo';

        Cliente::create($validated);

        return redirect()->route('clientes.index')
            ->with('sucesso', 'Cliente cadastrado com sucesso.');
    }

    public function show(Cliente $cliente)
    {
        $cliente->load([
            'visitas' => function ($q) {
                $q->orderByDesc('data_hora')->with(['produtos', 'tecnico']);
            },
            'comodatos.produto',
        ]);

        return view('clientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'documento' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'endereco' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:2',
            'cep' => 'nullable|string|max:10',
            'origem' => 'nullable|string|max:50',
            'periodicidade' => 'nullable|string|max:50',
            'status_prospeccao' => 'nullable|string|max:50',
            'data_followup' => 'nullable|date',
            'observacoes' => 'nullable|string',
        ]);

        $cliente->update($validated);

        return redirect()->route('clientes.index')
            ->with('sucesso', 'Cliente atualizado com sucesso.');
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return redirect()->route('clientes.index')
            ->with('sucesso', 'Cliente removido com sucesso.');
    }
}
