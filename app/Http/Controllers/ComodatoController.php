<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\EquipamentoComodato;
use App\Models\Produto;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ComodatoController extends Controller
{
    public function index(Request $request)
    {
        $query = EquipamentoComodato::with(['cliente', 'produto']);

        if ($busca = $request->get('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome_equipamento', 'like', "%{$busca}%")
                    ->orWhereHas('cliente', fn ($c) => $c->where('nome', 'like', "%{$busca}%"));
            });
        }

        if ($request->boolean('vencendo')) {
            $query->where('data_proxima_reposicao', '<=', now()->addDays(2)->toDateString());
        }

        $comodatos = $query->orderBy('data_proxima_reposicao')->paginate(15)->withQueryString();

        return view('comodatos.index', compact('comodatos'));
    }

    public function create(Request $request)
    {
        $clientes = Cliente::orderBy('nome')->get();
        $produtos = Produto::orderBy('nome')->get();
        $clienteId = $request->get('cliente_id');

        return view('comodatos.create', compact('clientes', 'produtos', 'clienteId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'produto_id' => 'nullable|exists:produtos,id',
            'nome_equipamento' => 'required|string|max:255',
            'quantidade' => 'required|integer|min:1',
            'periodicidade_dias' => 'required|integer|min:1',
            'data_instalacao' => 'required|date',
            'observacoes' => 'nullable|string',
        ]);

        $instalacao = Carbon::parse($validated['data_instalacao']);
        $validated['data_ultima_reposicao'] = $instalacao->toDateString();
        $validated['data_proxima_reposicao'] = $instalacao->copy()->addDays((int) $validated['periodicidade_dias'])->toDateString();

        EquipamentoComodato::create($validated);

        return redirect()->route('comodatos.index')
            ->with('sucesso', 'Equipamento em comodato registrado com sucesso.');
    }

    public function edit(EquipamentoComodato $comodato)
    {
        $clientes = Cliente::orderBy('nome')->get();
        $produtos = Produto::orderBy('nome')->get();

        return view('comodatos.edit', compact('comodato', 'clientes', 'produtos'));
    }

    public function update(Request $request, EquipamentoComodato $comodato)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'produto_id' => 'nullable|exists:produtos,id',
            'nome_equipamento' => 'required|string|max:255',
            'quantidade' => 'required|integer|min:1',
            'periodicidade_dias' => 'required|integer|min:1',
            'data_instalacao' => 'required|date',
            'data_ultima_reposicao' => 'nullable|date',
            'data_proxima_reposicao' => 'required|date',
            'observacoes' => 'nullable|string',
        ]);

        $comodato->update($validated);

        return redirect()->route('comodatos.index')
            ->with('sucesso', 'Comodato atualizado com sucesso.');
    }

    public function destroy(EquipamentoComodato $comodato)
    {
        $comodato->delete();

        return redirect()->route('comodatos.index')
            ->with('sucesso', 'Comodato removido com sucesso.');
    }

    public function registrarReposicao(EquipamentoComodato $comodato)
    {
        $comodato->registrarReposicao();

        return back()->with('sucesso', "Reposição do equipamento '{$comodato->nome_equipamento}' registrada com sucesso. Próxima reposição em {$comodato->data_proxima_reposicao->format('d/m/Y')}.");
    }
}
