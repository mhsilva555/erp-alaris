<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Equipamento;
use App\Models\EquipamentoComodato;
use App\Models\Produto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComodatoController extends Controller
{
    public function index(Request $request)
    {
        $query = EquipamentoComodato::with(['cliente', 'produto', 'equipamento']);

        if ($busca = $request->get('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome_equipamento', 'like', "%{$busca}%")
                    ->orWhereHas('cliente', fn ($c) => $c->where('nome', 'like', "%{$busca}%"))
                    ->orWhereHas('equipamento', fn ($e) => $e->where('nome', 'like', "%{$busca}%")->orWhere('modelo', 'like', "%{$busca}%"));
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
        $equipamentos = Equipamento::disponiveis()->orderBy('nome')->get();
        $clienteId = $request->get('cliente_id');

        return view('comodatos.create', compact('clientes', 'produtos', 'equipamentos', 'clienteId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'equipamento_id' => 'nullable|exists:equipamentos,id',
            'produto_id' => 'nullable|exists:produtos,id',
            'nome_equipamento' => 'nullable|string|max:255',
            'quantidade' => 'required|integer|min:1',
            'periodicidade_dias' => 'required|integer|min:1',
            'data_instalacao' => 'required|date',
            'observacoes' => 'nullable|string',
        ]);

        if (empty($validated['equipamento_id']) && empty($validated['nome_equipamento'])) {
            return back()->withInput()->withErrors([
                'equipamento_id' => 'Selecione um equipamento do inventário ou informe o nome/tipo.',
            ]);
        }

        $equipamento = null;
        if (! empty($validated['equipamento_id'])) {
            $equipamento = Equipamento::findOrFail($validated['equipamento_id']);
            if ((int) $validated['quantidade'] > (int) $equipamento->quantidade_disponivel) {
                return back()->withInput()->withErrors([
                    'quantidade' => "Quantidade solicitada ({$validated['quantidade']} un) excede o saldo disponível em estoque ({$equipamento->quantidade_disponivel} un).",
                ]);
            }
            if (empty($validated['nome_equipamento'])) {
                $validated['nome_equipamento'] = $equipamento->nome.($equipamento->modelo ? " - {$equipamento->modelo}" : '');
            }
        }

        $instalacao = Carbon::parse($validated['data_instalacao']);
        $validated['data_ultima_reposicao'] = $instalacao->toDateString();
        $validated['data_proxima_reposicao'] = $instalacao->copy()->addDays((int) $validated['periodicidade_dias'])->toDateString();

        DB::transaction(function () use ($validated, $equipamento) {
            EquipamentoComodato::create($validated);
            if ($equipamento) {
                $equipamento->decrement('quantidade_disponivel', (int) $validated['quantidade']);
            }
        });

        return redirect()->route('comodatos.index')
            ->with('sucesso', 'Equipamento em comodato registrado com sucesso.');
    }

    public function edit(EquipamentoComodato $comodato)
    {
        $clientes = Cliente::orderBy('nome')->get();
        $produtos = Produto::orderBy('nome')->get();
        $equipamentos = Equipamento::orderBy('nome')->get();

        return view('comodatos.edit', compact('comodato', 'clientes', 'produtos', 'equipamentos'));
    }

    public function update(Request $request, EquipamentoComodato $comodato)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'equipamento_id' => 'nullable|exists:equipamentos,id',
            'produto_id' => 'nullable|exists:produtos,id',
            'nome_equipamento' => 'nullable|string|max:255',
            'quantidade' => 'required|integer|min:1',
            'periodicidade_dias' => 'required|integer|min:1',
            'data_instalacao' => 'required|date',
            'data_ultima_reposicao' => 'nullable|date',
            'data_proxima_reposicao' => 'nullable|date',
            'observacoes' => 'nullable|string',
        ]);

        $novoEquipamentoId = $validated['equipamento_id'] ?? null;
        $antigoEquipamentoId = $comodato->equipamento_id;
        $novaQtd = (int) $validated['quantidade'];
        $antigaQtd = (int) $comodato->quantidade;

        $novoEquipamento = $novoEquipamentoId ? Equipamento::findOrFail($novoEquipamentoId) : null;
        if ($novoEquipamento && empty($validated['nome_equipamento'])) {
            $validated['nome_equipamento'] = $novoEquipamento->nome.($novoEquipamento->modelo ? " - {$novoEquipamento->modelo}" : '');
        }

        // Validação de saldo disponível
        if ($novoEquipamentoId === $antigoEquipamentoId) {
            if ($novoEquipamento) {
                $diferenca = $novaQtd - $antigaQtd;
                if ($diferenca > 0 && $diferenca > $novoEquipamento->quantidade_disponivel) {
                    return back()->withInput()->withErrors([
                        'quantidade' => "Aumento de {$diferenca} un excede o saldo disponível ({$novoEquipamento->quantidade_disponivel} un).",
                    ]);
                }
            }
        } else {
            if ($novoEquipamento && $novaQtd > $novoEquipamento->quantidade_disponivel) {
                return back()->withInput()->withErrors([
                    'quantidade' => "Quantidade solicitada ({$novaQtd} un) excede o saldo disponível em estoque ({$novoEquipamento->quantidade_disponivel} un).",
                ]);
            }
        }

        DB::transaction(function () use ($comodato, $validated, $novoEquipamentoId, $antigoEquipamentoId, $novaQtd, $antigaQtd, $novoEquipamento) {
            if ($novoEquipamentoId === $antigoEquipamentoId) {
                if ($novoEquipamento) {
                    $diferenca = $novaQtd - $antigaQtd;
                    if ($diferenca > 0) {
                        $novoEquipamento->decrement('quantidade_disponivel', $diferenca);
                    } elseif ($diferenca < 0) {
                        $novoEquipamento->increment('quantidade_disponivel', abs($diferenca));
                    }
                }
            } else {
                if ($antigoEquipamentoId) {
                    $antigo = Equipamento::find($antigoEquipamentoId);
                    $antigo?->increment('quantidade_disponivel', $antigaQtd);
                }
                if ($novoEquipamento) {
                    $novoEquipamento->decrement('quantidade_disponivel', $novaQtd);
                }
            }

            $comodato->update($validated);
        });

        return redirect()->route('comodatos.index')
            ->with('sucesso', 'Comodato atualizado com sucesso.');
    }

    public function destroy(EquipamentoComodato $comodato)
    {
        DB::transaction(function () use ($comodato) {
            if ($comodato->equipamento_id) {
                $comodato->equipamento?->increment('quantidade_disponivel', $comodato->quantidade);
            }
            $comodato->delete();
        });

        return redirect()->route('comodatos.index')
            ->with('sucesso', 'Comodato removido com sucesso.');
    }

    public function registrarReposicao(EquipamentoComodato $comodato)
    {
        $comodato->registrarReposicao();

        return back()->with('sucesso', "Reposição do equipamento '{$comodato->nome_equipamento}' registrada com sucesso. Próxima reposição em {$comodato->data_proxima_reposicao->format('d/m/Y')}.");
    }
}
