<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Configuracao;
use App\Models\MovimentacaoFinanceira;
use App\Models\Produto;
use App\Models\Tecnico;
use App\Models\Visita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitaController extends Controller
{
    public function index(Request $request)
    {
        $query = Visita::with(['cliente', 'tecnico']);

        // Se for técnico, filtra estritamente as suas próprias visitas
        if ($request->user() && $request->user()->isTecnico()) {
            $query->where('tecnico_id', $request->user()->tecnico_id);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($tipoVisita = $request->get('tipo_visita')) {
            $query->where('tipo_visita', $tipoVisita);
        }

        if ($tipoServico = $request->get('tipo_servico')) {
            $query->where('tipo_servico', $tipoServico);
        }

        if ($data = $request->get('data')) {
            $query->whereDate('data_hora', $data);
        }

        if ($busca = $request->get('busca')) {
            $query->whereHas('cliente', function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%");
            });
        }

        $visitas = $query->orderByDesc('data_hora')->paginate(15)->withQueryString();

        return view('visitas.index', compact('visitas'));
    }

    public function create()
    {
        $clientes = Cliente::orderBy('nome')->get();
        $produtos = Produto::orderBy('nome')->get();
        $tecnicos = Tecnico::where('ativo', true)->orderBy('nome')->get();
        $aliquotaPadrao = (float) Configuracao::get('aliquota_imposto_padrao', '14.0');
        $diariaPadrao = (float) Configuracao::get('diaria_tecnico_padrao', '50.00');

        return view('visitas.create', compact('clientes', 'produtos', 'tecnicos', 'aliquotaPadrao', 'diariaPadrao'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'data_hora' => 'required|date',
            'status' => 'required|in:agendada,em_andamento,concluida,cancelada',
            'tecnico_id' => 'required|exists:tecnicos,id',
            'tipo_visita' => 'required|string|in:comercial,tecnica,demonstracao,execucao',
            'tipo_servico' => 'required|string|in:controle_pragas,limpeza',
            'tipo_praga' => 'nullable|string|max:255',
            'area_tratada' => 'nullable|string|max:255',
            'observacoes' => 'nullable|string',
            'proxima_visita' => 'nullable|date|after:data_hora',
            'valor_cobrado' => 'nullable|numeric|min:0',
            'custo_mao_de_obra' => 'nullable|numeric|min:0',
            'custo_deslocamento' => 'nullable|numeric|min:0',
            'exige_nota_fiscal' => 'boolean',
            'produtos' => 'nullable|array',
            'produtos.*.id' => 'nullable|exists:produtos,id',
            'produtos.*.produto_id' => 'nullable|exists:produtos,id',
            'produtos.*.quantidade' => 'required_with:produtos|numeric|min:0.01',
        ]);

        $exigeNf = $request->has('exige_nota_fiscal');
        $valorCobrado = (float) ($validated['valor_cobrado'] ?? 0);
        $custoMaoDeObra = (float) ($validated['custo_mao_de_obra'] ?? 50.00);
        $custoDeslocamento = (float) ($validated['custo_deslocamento'] ?? 0);

        $aliquotaImposto = $exigeNf ? (float) Configuracao::get('aliquota_imposto_padrao', '14.0') : 0.00;
        $valorImposto = round($valorCobrado * ($aliquotaImposto / 100), 2);

        DB::transaction(function () use ($validated, $valorCobrado, $custoMaoDeObra, $custoDeslocamento, $exigeNf, $aliquotaImposto, $valorImposto) {
            $custoProdutos = 0.0;
            $sync = [];

            if (! empty($validated['produtos'])) {
                foreach ($validated['produtos'] as $item) {
                    $prodId = $item['id'] ?? $item['produto_id'] ?? null;
                    if (! $prodId) {
                        continue;
                    }
                    $prod = Produto::findOrFail($prodId);
                    $dose = (float) $item['quantidade'];
                    $custoItem = $prod->calcularCustoDose($dose);
                    $custoProdutos += $custoItem;

                    $sync[$prod->id] = [
                        'quantidade' => $dose,
                        'custo_proporcional' => $custoItem,
                    ];
                }
            }

            $custoOperacional = $custoProdutos + $custoMaoDeObra + $custoDeslocamento;
            $lucroBruto = $valorCobrado - ($custoOperacional + $valorImposto);

            $visitaData = collect($validated)->except('produtos')->toArray();
            $visitaData['exige_nota_fiscal'] = $exigeNf;
            $visitaData['valor_cobrado'] = $valorCobrado;
            $visitaData['custo_produtos'] = $custoProdutos;
            $visitaData['custo_mao_de_obra'] = $custoMaoDeObra;
            $visitaData['custo_deslocamento'] = $custoDeslocamento;
            $visitaData['percentual_imposto'] = $aliquotaImposto;
            $visitaData['valor_imposto'] = $valorImposto;
            $visitaData['lucro_bruto'] = $lucroBruto;

            $visita = Visita::create($visitaData);

            if (! empty($sync)) {
                $visita->produtos()->sync($sync);
            }

            // Se criada já como concluída, debitar frações do estoque e lançar no contas a receber
            if ($visita->status === Visita::STATUS_CONCLUIDA) {
                $visita->load('produtos');
                foreach ($visita->produtos as $produto) {
                    $dose = (float) $produto->pivot->quantidade;
                    $fracao = $produto->calcularFracaoBaixa($dose);
                    $produto->decrement('quantidade_estoque', $fracao);
                }

                if ($valorCobrado > 0) {
                    MovimentacaoFinanceira::create([
                        'visita_id' => $visita->id,
                        'tipo' => MovimentacaoFinanceira::TIPO_RECEITA,
                        'categoria' => 'faturamento_servico',
                        'descricao' => "Faturamento OS #{$visita->id} - {$visita->cliente->nome}",
                        'valor' => $valorCobrado,
                        'data_competencia' => now()->toDateString(),
                        'status' => MovimentacaoFinanceira::STATUS_PENDENTE,
                    ]);
                }
            }
        });

        return redirect()->route('visitas.index')
            ->with('sucesso', 'Visita registrada com sucesso.');
    }

    public function show(Visita $visita)
    {
        $this->autorizarAcessoVisita($visita);

        $visita->load(['cliente', 'tecnico', 'produtos']);

        return view('visitas.show', compact('visita'));
    }

    public function edit(Visita $visita)
    {
        $this->autorizarAcessoVisita($visita);

        $visita->load('produtos');
        $clientes = Cliente::orderBy('nome')->get();
        $produtos = Produto::orderBy('nome')->get();
        $tecnicos = Tecnico::where('ativo', true)->orderBy('nome')->get();
        $aliquotaPadrao = (float) Configuracao::get('aliquota_imposto_padrao', '14.0');

        return view('visitas.edit', compact('visita', 'clientes', 'produtos', 'tecnicos', 'aliquotaPadrao'));
    }

    public function update(Request $request, Visita $visita)
    {
        $this->autorizarAcessoVisita($visita);

        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'data_hora' => 'required|date',
            'status' => 'required|in:agendada,em_andamento,concluida,cancelada',
            'tecnico_id' => 'required|exists:tecnicos,id',
            'tipo_visita' => 'required|string|in:comercial,tecnica,demonstracao,execucao',
            'tipo_servico' => 'required|string|in:controle_pragas,limpeza',
            'tipo_praga' => 'nullable|string|max:255',
            'area_tratada' => 'nullable|string|max:255',
            'observacoes' => 'nullable|string',
            'proxima_visita' => 'nullable|date|after:data_hora',
            'valor_cobrado' => 'nullable|numeric|min:0',
            'custo_mao_de_obra' => 'nullable|numeric|min:0',
            'custo_deslocamento' => 'nullable|numeric|min:0',
            'exige_nota_fiscal' => 'boolean',
            'produtos' => 'nullable|array',
            'produtos.*.id' => 'nullable|exists:produtos,id',
            'produtos.*.produto_id' => 'nullable|exists:produtos,id',
            'produtos.*.quantidade' => 'required_with:produtos|numeric|min:0.01',
        ]);

        $statusAnterior = $visita->status;
        $exigeNf = $request->has('exige_nota_fiscal');
        $valorCobrado = (float) ($validated['valor_cobrado'] ?? 0);
        $custoMaoDeObra = (float) ($validated['custo_mao_de_obra'] ?? 50.00);
        $custoDeslocamento = (float) ($validated['custo_deslocamento'] ?? 0);

        $aliquotaImposto = $exigeNf ? (float) Configuracao::get('aliquota_imposto_padrao', '14.0') : 0.00;
        $valorImposto = round($valorCobrado * ($aliquotaImposto / 100), 2);

        DB::transaction(function () use ($validated, $visita, $statusAnterior, $valorCobrado, $custoMaoDeObra, $custoDeslocamento, $exigeNf, $aliquotaImposto, $valorImposto) {
            $custoProdutos = 0.0;
            $sync = [];

            if (! empty($validated['produtos'])) {
                foreach ($validated['produtos'] as $item) {
                    $prodId = $item['id'] ?? $item['produto_id'] ?? null;
                    if (! $prodId) {
                        continue;
                    }
                    $prod = Produto::findOrFail($prodId);
                    $dose = (float) $item['quantidade'];
                    $custoItem = $prod->calcularCustoDose($dose);
                    $custoProdutos += $custoItem;

                    $sync[$prod->id] = [
                        'quantidade' => $dose,
                        'custo_proporcional' => $custoItem,
                    ];
                }
            }

            $custoOperacional = $custoProdutos + $custoMaoDeObra + $custoDeslocamento;
            $lucroBruto = $valorCobrado - ($custoOperacional + $valorImposto);

            $visitaData = collect($validated)->except('produtos')->toArray();
            $visitaData['exige_nota_fiscal'] = $exigeNf;
            $visitaData['valor_cobrado'] = $valorCobrado;
            $visitaData['custo_produtos'] = $custoProdutos;
            $visitaData['custo_mao_de_obra'] = $custoMaoDeObra;
            $visitaData['custo_deslocamento'] = $custoDeslocamento;
            $visitaData['percentual_imposto'] = $aliquotaImposto;
            $visitaData['valor_imposto'] = $valorImposto;
            $visitaData['lucro_bruto'] = $lucroBruto;

            $visita->update($visitaData);
            $visita->produtos()->sync($sync);

            // Se transicionou para concluída, descontar frações do estoque e lançar no contas a receber
            if ($statusAnterior !== Visita::STATUS_CONCLUIDA && $visita->status === Visita::STATUS_CONCLUIDA) {
                $visita->load('produtos');
                foreach ($visita->produtos as $produto) {
                    $dose = (float) $produto->pivot->quantidade;
                    $fracao = $produto->calcularFracaoBaixa($dose);
                    $produto->decrement('quantidade_estoque', $fracao);
                }

                if ($valorCobrado > 0) {
                    MovimentacaoFinanceira::firstOrCreate(
                        ['visita_id' => $visita->id, 'tipo' => MovimentacaoFinanceira::TIPO_RECEITA],
                        [
                            'categoria' => 'faturamento_servico',
                            'descricao' => "Faturamento OS #{$visita->id} - {$visita->cliente->nome}",
                            'valor' => $valorCobrado,
                            'data_competencia' => now()->toDateString(),
                            'status' => MovimentacaoFinanceira::STATUS_PENDENTE,
                        ]
                    );
                }
            }
        });

        return redirect()->route('visitas.show', $visita)
            ->with('sucesso', 'Visita atualizada com sucesso.');
    }

    public function destroy(Visita $visita)
    {
        $visita->delete();

        return redirect()->route('visitas.index')
            ->with('sucesso', 'Visita removida com sucesso.');
    }

    /**
     * Gera Ordem de Serviço (OS) detalhada para o técnico em campo.
     */
    public function ordemServico(Visita $visita)
    {
        $this->autorizarAcessoVisita($visita);

        $visita->load(['cliente.comodatos.produto', 'tecnico', 'produtos']);

        return view('visitas.os', compact('visita'));
    }

    /**
     * Gera Proposta Comercial para o cliente (com estrito sigilo de custos e mL).
     */
    public function propostaCliente(Visita $visita)
    {
        $this->autorizarAcessoVisita($visita);

        $visita->load(['cliente', 'tecnico']);

        return view('visitas.proposta', compact('visita'));
    }

    private function autorizarAcessoVisita(Visita $visita): void
    {
        $user = auth()->user();
        if ($user && $user->isTecnico() && $visita->tecnico_id !== $user->tecnico_id) {
            abort(403, 'Acesso restrito. Você só pode visualizar as suas próprias visitas.');
        }
    }
}
