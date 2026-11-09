<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Contrato;
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
        $query = EquipamentoComodato::with(['cliente', 'produto', 'equipamento', 'contrato']);

        if ($busca = $request->get('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome_equipamento', 'like', "%{$busca}%")
                    ->orWhereHas('cliente', fn ($c) => $c->where('nome', 'like', "%{$busca}%"))
                    ->orWhereHas('contrato', fn ($ctr) => $ctr->where('numero', 'like', "%{$busca}%"))
                    ->orWhereHas('equipamento', fn ($e) => $e->where('nome', 'like', "%{$busca}%")->orWhere('modelo', 'like', "%{$busca}%"));
            });
        }

        if ($contratoId = $request->get('contrato_id')) {
            $query->where('contrato_id', $contratoId);
        }

        if ($request->boolean('vencendo')) {
            $query->operacionalmenteAtivos()
                ->whereNotNull('data_proxima_reposicao')
                ->whereDate('data_proxima_reposicao', '<=', now()->addDays(2)->toDateString());
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
        $contratoId = $request->get('contrato_id');

        if ($contratoId && ! $clienteId) {
            $contratoRef = Contrato::find($contratoId);
            if ($contratoRef) {
                $clienteId = $contratoRef->cliente_id;
            }
        }

        $contratos = Contrato::with('cliente')
            ->where('status', Contrato::STATUS_ATIVO)
            ->orderBy('numero')
            ->get();

        return view('comodatos.create', compact('clientes', 'produtos', 'equipamentos', 'clienteId', 'contratoId', 'contratos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'contrato_id' => 'nullable|exists:contratos,id',
            'equipamento_id' => 'nullable|exists:equipamentos,id',
            'produto_id' => 'nullable|exists:produtos,id',
            'nome_equipamento' => 'nullable|string|max:255',
            'quantidade' => 'required|integer|min:1',
            'periodicidade_dias' => 'nullable|required_without:contrato_id|integer|min:1',
            'data_instalacao' => 'required|date',
            'observacoes' => 'nullable|string',
        ]);

        $contrato = null;
        if (! empty($validated['contrato_id'])) {
            $contrato = Contrato::where('id', $validated['contrato_id'])
                ->where('status', Contrato::STATUS_ATIVO)
                ->first();

            if (! $contrato || (int) $contrato->cliente_id !== (int) $validated['cliente_id']) {
                return back()->withInput()->withErrors([
                    'contrato_id' => 'O contrato selecionado deve estar ativo e pertencer ao cliente escolhido.',
                ]);
            }

            $validated['periodicidade_dias'] = $contrato->periodicidadeEmDias();
        }

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
        $validated['data_proxima_reposicao'] = $contrato
            ? $contrato->calcularProximaDataReposicao($instalacao)->toDateString()
            : $instalacao->copy()->addDays((int) $validated['periodicidade_dias'])->toDateString();

        DB::transaction(function () use ($validated, $equipamento) {
            EquipamentoComodato::create($validated);
            if ($equipamento) {
                $equipamento->decrement('quantidade_disponivel', (int) $validated['quantidade']);
            }
        });

        if ($request->boolean('retorno_contrato') && ! empty($validated['contrato_id'])) {
            return redirect()->route('contratos.show', $validated['contrato_id'])
                ->with('sucesso', 'Equipamento em comodato alocado ao contrato com sucesso.');
        }

        return redirect()->route('comodatos.index')
            ->with('sucesso', 'Equipamento em comodato registrado com sucesso.');
    }

    public function show(EquipamentoComodato $comodato)
    {
        $comodato->load(['cliente', 'produto', 'equipamento']);
        $visitas = $comodato->visitas()->with(['tecnico', 'produtos'])->orderByDesc('data_hora')->paginate(10);

        return view('comodatos.show', compact('comodato', 'visitas'));
    }

    public function edit(EquipamentoComodato $comodato)
    {
        $clientes = Cliente::orderBy('nome')->get();
        $produtos = Produto::orderBy('nome')->get();
        $equipamentos = Equipamento::orderBy('nome')->get();
        $contratos = Contrato::with('cliente')
            ->where('status', Contrato::STATUS_ATIVO)
            ->orderBy('numero')
            ->get();

        return view('comodatos.edit', compact('comodato', 'clientes', 'produtos', 'equipamentos', 'contratos'));
    }

    public function update(Request $request, EquipamentoComodato $comodato)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'contrato_id' => 'nullable|exists:contratos,id',
            'equipamento_id' => 'nullable|exists:equipamentos,id',
            'produto_id' => 'nullable|exists:produtos,id',
            'nome_equipamento' => 'nullable|string|max:255',
            'quantidade' => 'required|integer|min:1',
            'periodicidade_dias' => 'nullable|required_without:contrato_id|integer|min:1',
            'data_instalacao' => 'required|date',
            'data_ultima_reposicao' => 'nullable|date',
            'data_proxima_reposicao' => 'nullable|date',
            'observacoes' => 'nullable|string',
        ]);

        if (! empty($validated['contrato_id'])) {
            $contrato = Contrato::where('id', $validated['contrato_id'])
                ->where('status', Contrato::STATUS_ATIVO)
                ->first();

            if (! $contrato || (int) $contrato->cliente_id !== (int) $validated['cliente_id']) {
                return back()->withInput()->withErrors([
                    'contrato_id' => 'O contrato selecionado deve estar ativo e pertencer ao cliente escolhido.',
                ]);
            }

            $validated['periodicidade_dias'] = $contrato->periodicidadeEmDias();
            if (empty($validated['data_proxima_reposicao']) || (int) $comodato->contrato_id !== (int) $contrato->id) {
                $refDate = ! empty($validated['data_ultima_reposicao'])
                    ? Carbon::parse($validated['data_ultima_reposicao'])
                    : Carbon::parse($validated['data_instalacao']);
                $validated['data_proxima_reposicao'] = $contrato->calcularProximaDataReposicao($refDate)->toDateString();
            }
        }

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
            if ($comodato->equipamento_id && $comodato->estaOperacionalmenteAtivo()) {
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
