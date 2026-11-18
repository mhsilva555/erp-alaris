<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\MovimentacaoFinanceira;
use App\Models\Visita;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceiroController extends Controller
{
    public function index(Request $request)
    {
        $tipo = $request->get('tipo'); // receita, despesa, null (todos)
        $status = $request->get('status'); // pendente, pago, null
        $mes = $request->get('mes', now()->format('Y-m'));

        // Sincroniza receitas de mensalidade dos contratos ativos no mês visualizado
        Contrato::sincronizarContratosAtivosNoMes($mes);

        $query = MovimentacaoFinanceira::with(['visita.cliente', 'contrato.cliente', 'cliente']);

        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($mes) {
            $inicio = Carbon::parse($mes.'-01')->startOfMonth();
            $fim = Carbon::parse($mes.'-01')->endOfMonth();
            $query->whereBetween('data_competencia', [$inicio->toDateString(), $fim->toDateString()]);
        }

        $movimentacoes = $query->orderByDesc('data_competencia')->paginate(20)->withQueryString();

        // Totais do mês
        $inicioMes = Carbon::parse($mes.'-01')->startOfMonth()->toDateString();
        $fimMes = Carbon::parse($mes.'-01')->endOfMonth()->toDateString();

        $totalReceitasPagas = MovimentacaoFinanceira::receitas()->pagas()
            ->whereBetween('data_competencia', [$inicioMes, $fimMes])->sum('valor');
        $totalReceitasPendentes = MovimentacaoFinanceira::receitas()->pendentes()
            ->whereBetween('data_competencia', [$inicioMes, $fimMes])->sum('valor');
        $totalDespesasPagas = MovimentacaoFinanceira::despesas()->pagas()
            ->whereBetween('data_competencia', [$inicioMes, $fimMes])->sum('valor');
        $totalDespesasPendentes = MovimentacaoFinanceira::despesas()->pendentes()
            ->whereBetween('data_competencia', [$inicioMes, $fimMes])->sum('valor');

        $saldoRealizado = $totalReceitasPagas - $totalDespesasPagas;
        $saldoPrevisto = ($totalReceitasPagas + $totalReceitasPendentes) - ($totalDespesasPagas + $totalDespesasPendentes);

        $saldoAtual = $saldoRealizado;
        $saldoProjetado = $saldoPrevisto;
        $receitasPagas = $totalReceitasPagas;
        $receitasPendentes = $totalReceitasPendentes;
        $despesasPagas = $totalDespesasPagas;
        $despesasPendentes = $totalDespesasPendentes;

        // Dados de conciliação com as OSs concluídas e Contratos Ativos do mês (Regime de Competência DRE vs Fluxo de Caixa)
        $visitasMes = Visita::where('status', Visita::STATUS_CONCLUIDA)
            ->whereDate('data_hora', '>=', $inicioMes)
            ->whereDate('data_hora', '<=', $fimMes)
            ->get();

        $faturamentoOSMes = (float) $visitasMes->sum('valor_cobrado');
        $qtdOSConcluidasMes = $visitasMes->count();
        $visitaIdsMes = $visitasMes->pluck('id');

        $faturamentoOSRecebido = (float) MovimentacaoFinanceira::receitas()->pagas()
            ->whereIn('visita_id', $visitaIdsMes)->sum('valor');
        $faturamentoOSPendente = (float) MovimentacaoFinanceira::receitas()->pendentes()
            ->whereIn('visita_id', $visitaIdsMes)->sum('valor');
        $taxaLiquidacaoOS = $faturamentoOSMes > 0
            ? round(($faturamentoOSRecebido / $faturamentoOSMes) * 100, 1)
            : 0;

        // Contratos ativos no mês visualizado
        $contratosMes = Contrato::with('cliente')
            ->where('status', Contrato::STATUS_ATIVO)
            ->where('valor_total', '>', 0)
            ->whereDate('data_inicio', '<=', $fimMes)
            ->where(function ($q) use ($inicioMes) {
                $q->where(function ($sub) use ($inicioMes) {
                    $sub->where('periodicidade', '!=', Contrato::PERIODICIDADE_UNICO)
                        ->where(function ($vig) use ($inicioMes) {
                            $vig->whereNull('data_fim')
                                ->orWhereDate('data_fim', '>=', $inicioMes);
                        });
                })->orWhere(function ($sub) use ($inicioMes) {
                    $sub->where('periodicidade', Contrato::PERIODICIDADE_UNICO)
                        ->whereDate('data_inicio', '>=', $inicioMes);
                });
            })
            ->get();

        $faturamentoContratosMes = (float) $contratosMes->sum('valor_total');
        $qtdContratosAtivosMes = $contratosMes->count();
        $contratoIdsMes = $contratosMes->pluck('id');

        $faturamentoContratosRecebido = (float) MovimentacaoFinanceira::receitas()->pagas()
            ->whereIn('contrato_id', $contratoIdsMes)
            ->whereBetween('data_competencia', [$inicioMes, $fimMes])
            ->sum('valor');
        $faturamentoContratosPendente = (float) MovimentacaoFinanceira::receitas()->pendentes()
            ->whereIn('contrato_id', $contratoIdsMes)
            ->whereBetween('data_competencia', [$inicioMes, $fimMes])
            ->sum('valor');

        $faturamentoTotalDRE = $faturamentoOSMes + $faturamentoContratosMes;
        $faturamentoTotalRecebido = $faturamentoOSRecebido + $faturamentoContratosRecebido;
        $faturamentoTotalPendente = $faturamentoOSPendente + $faturamentoContratosPendente;
        $taxaLiquidacaoDRE = $faturamentoTotalDRE > 0
            ? round(($faturamentoTotalRecebido / $faturamentoTotalDRE) * 100, 1)
            : 0;

        $clientes = Cliente::orderBy('nome')->get();
        $visitas = Visita::with('cliente')->where('status', Visita::STATUS_CONCLUIDA)->orderByDesc('data_hora')->limit(50)->get();
        $contratos = Contrato::with('cliente')->where('status', Contrato::STATUS_ATIVO)->orderBy('numero')->get();

        return view('financeiro.fluxo', compact(
            'movimentacoes',
            'mes',
            'totalReceitasPagas',
            'totalReceitasPendentes',
            'totalDespesasPagas',
            'totalDespesasPendentes',
            'saldoRealizado',
            'saldoPrevisto',
            'saldoAtual',
            'saldoProjetado',
            'receitasPagas',
            'receitasPendentes',
            'despesasPagas',
            'despesasPendentes',
            'clientes',
            'visitas',
            'contratos',
            'faturamentoOSMes',
            'qtdOSConcluidasMes',
            'faturamentoOSRecebido',
            'faturamentoOSPendente',
            'taxaLiquidacaoOS',
            'faturamentoContratosMes',
            'qtdContratosAtivosMes',
            'faturamentoContratosRecebido',
            'faturamentoContratosPendente',
            'faturamentoTotalDRE',
            'faturamentoTotalRecebido',
            'faturamentoTotalPendente',
            'taxaLiquidacaoDRE'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:receita,despesa',
            'categoria' => 'required|string|max:50',
            'descricao' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0.01',
            'data_competencia' => 'required|date',
            'data_vencimento' => 'nullable|date',
            'data_pagamento' => 'nullable|date',
            'status' => 'required|in:pendente,pago',
            'cliente_id' => 'nullable|exists:clientes,id',
            'visita_id' => 'nullable|exists:visitas,id',
            'contrato_id' => 'nullable|exists:contratos,id',
        ]);

        if ($validated['status'] === 'pago' && empty($validated['data_pagamento'])) {
            $validated['data_pagamento'] = $validated['data_competencia'];
        }

        MovimentacaoFinanceira::create($validated);

        return redirect()->route('financeiro.fluxo')
            ->with('sucesso', 'Movimentação registrada com sucesso.');
    }

    public function updateStatus(Request $request, MovimentacaoFinanceira $movimentacao)
    {
        $validated = $request->validate([
            'status' => 'required|in:pendente,pago,cancelado',
        ]);

        $movimentacao->status = $validated['status'];
        if ($validated['status'] === 'pago' && empty($movimentacao->data_pagamento)) {
            $movimentacao->data_pagamento = now()->toDateString();
        }
        $movimentacao->save();

        return back()->with('sucesso', 'Status da movimentação atualizado.');
    }

    public function destroy(MovimentacaoFinanceira $movimentacao)
    {
        $movimentacao->loadMissing(['contrato', 'visita']);

        if ($movimentacao->contrato_id !== null && $movimentacao->contrato?->estaAtivo()) {
            return back()->with('erro', "Este faturamento não pode ser excluído diretamente porque está vinculado ao contrato ativo {$movimentacao->contrato->numero}. Cancele, suspenda ou edite o contrato para ajustar a cobrança.");
        }

        if ($movimentacao->visita_id !== null && $movimentacao->visita && $movimentacao->visita->status === Visita::STATUS_CONCLUIDA) {
            return back()->with('erro', "Este lançamento não pode ser excluído diretamente porque está vinculado à Ordem de Serviço #{$movimentacao->visita_id} (Concluída). Cancele ou edite a O.S. para ajustar o financeiro.");
        }

        try {
            $movimentacao->delete();
        } catch (QueryException $e) {
            return back()->with('erro', 'Este lançamento financeiro não pode ser excluído porque possui registros vinculados a ele.');
        }

        return back()->with('sucesso', 'Movimentação removida com sucesso.');
    }

    /**
     * Executa ações em lote/massa sobre movimentações financeiras selecionadas (liquidar, reabrir, excluir).
     */
    public function acoesEmMassa(Request $request)
    {
        $validated = $request->validate([
            'acao' => 'required|in:liquidar,reabrir,excluir',
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:movimentacoes_financeiras,id',
        ]);

        $ids = $validated['ids'];
        $acao = $validated['acao'];

        if (($acao === 'liquidar' || $acao === 'reabrir') && ! auth()->user()->temPermissao('financeiro.alterar_status')) {
            abort(403, 'Acesso não autorizado para alterar status financeiro.');
        }

        if ($acao === 'excluir' && ! auth()->user()->temPermissao('financeiro.deletar')) {
            abort(403, 'Acesso não autorizado para excluir movimentações financeiras.');
        }

        $excluidos = 0;
        $bloqueados = 0;

        DB::transaction(function () use ($acao, $ids, &$excluidos, &$bloqueados) {
            if ($acao === 'liquidar') {
                $movs = MovimentacaoFinanceira::whereIn('id', $ids)->get();
                foreach ($movs as $mov) {
                    $mov->status = 'pago';
                    if (empty($mov->data_pagamento)) {
                        $mov->data_pagamento = now()->toDateString();
                    }
                    $mov->save();
                }
            } elseif ($acao === 'reabrir') {
                MovimentacaoFinanceira::whereIn('id', $ids)->update([
                    'status' => 'pendente',
                ]);
            } elseif ($acao === 'excluir') {
                $movs = MovimentacaoFinanceira::with(['contrato', 'visita'])->whereIn('id', $ids)->get();
                foreach ($movs as $mov) {
                    $vinculoContratoAtivo = $mov->contrato_id !== null && $mov->contrato?->estaAtivo();
                    $vinculoOsConcluida = $mov->visita_id !== null && $mov->visita && $mov->visita->status === Visita::STATUS_CONCLUIDA;

                    if ($vinculoContratoAtivo || $vinculoOsConcluida) {
                        $bloqueados++;

                        continue;
                    }

                    $mov->delete();
                    $excluidos++;
                }
            }
        });

        if ($acao === 'excluir') {
            if ($excluidos === 0 && $bloqueados > 0) {
                return back()->with('erro', 'Nenhum lançamento pôde ser excluído porque estão vinculados a contratos ativos ou ordens de serviço concluídas.');
            }
            if ($bloqueados > 0) {
                return back()->with('alerta', "{$excluidos} lançamento(s) excluído(s). {$bloqueados} lançamento(s) mantido(s) por vínculo com contrato ativo ou O.S. concluída.");
            }

            return back()->with('sucesso', "{$excluidos} lançamento(s) excluído(s) com sucesso.");
        }

        $mensagens = [
            'liquidar' => count($ids).' lançamento(s) liquidado(s) com sucesso.',
            'reabrir' => count($ids).' lançamento(s) reaberto(s) com sucesso.',
        ];

        return back()->with('sucesso', $mensagens[$acao] ?? 'Ação em massa executada com sucesso.');
    }

    /**
     * Relatório Gerencial de Lucro Bruto e Demonstrativo (Visualização Web)
     */
    public function relatorio(Request $request)
    {
        $dados = $this->obterDadosRelatorio($request);

        return view('financeiro.relatorios', $dados);
    }

    /**
     * Geração do Relatório Gerencial em PDF no Backend
     */
    public function relatorioPdf(Request $request)
    {
        $dados = $this->obterDadosRelatorio($request);

        $nomeArquivo = 'relatorio-lucro-bruto-'.$dados['mes'].'.pdf';

        $pdf = Pdf::loadView('financeiro.relatorio_pdf', $dados)
            ->setPaper('a4', 'landscape');

        return $pdf->stream($nomeArquivo);
    }

    /**
     * Extração e consolidação dos indicadores e registros do relatório gerencial
     */
    private function obterDadosRelatorio(Request $request): array
    {
        $mes = $request->get('mes', now()->format('Y-m'));
        $periodo = $request->get('periodo', 'mensal'); // semanal, mensal, anual
        $statusFiltro = $request->get('status', 'todas'); // todas, concluida, agendada
        $visao = $request->get('visao', 'projetado'); // projetado (orçado/provisão), realizado (executado estrito)

        if (! in_array($visao, ['projetado', 'realizado'], true)) {
            $visao = 'projetado';
        }

        try {
            $dataReferencia = Carbon::parse($mes.'-01');
        } catch (\Exception $e) {
            $dataReferencia = now();
            $mes = now()->format('Y-m');
        }

        if ($periodo === 'semanal' || $periodo === 'semana') {
            $inicio = $dataReferencia->copy()->startOfWeek();
            $fim = $dataReferencia->copy()->endOfWeek();
            $tituloPeriodo = 'Semana de '.$inicio->format('d/m').' a '.$fim->format('d/m/Y');
        } elseif ($periodo === 'anual' || $periodo === 'ano') {
            $inicio = $dataReferencia->copy()->startOfYear();
            $fim = $dataReferencia->copy()->endOfYear();
            $tituloPeriodo = 'Ano de '.$dataReferencia->year;
        } else {
            $periodo = 'mensal';
            $inicio = $dataReferencia->copy()->startOfMonth();
            $fim = $dataReferencia->copy()->endOfMonth();
            $tituloPeriodo = 'Mês de '.ucfirst($dataReferencia->translatedFormat('F \d\e Y'));
        }

        // Visitas no período com cliente, técnico, comodato, contrato e movimentações financeiras
        $visitasQuery = Visita::with(['cliente', 'tecnico', 'comodato', 'contrato', 'movimentacoes'])
            ->whereDate('data_hora', '>=', $inicio->toDateString())
            ->whereDate('data_hora', '<=', $fim->toDateString());

        if ($statusFiltro === 'concluida') {
            $visitasQuery->where('status', Visita::STATUS_CONCLUIDA);
        } else {
            // 'todas' apura todas as OSs ativas no período (incluindo abertas e em execução)
            $visitasQuery->where('status', '!=', Visita::STATUS_CANCELADA);
        }

        $visitas = $visitasQuery->orderBy('data_hora')->get();
        $visitaIds = $visitas->pluck('id');

        $faturamentoVisitas = (float) $visitas->sum('valor_cobrado');
        $custoProdutos = (float) $visitas->sum('custo_produtos');
        $custoMaoDeObra = (float) $visitas->sum('custo_mao_de_obra');
        $custoDeslocamento = (float) $visitas->sum('custo_deslocamento');
        $gastosAdicionais = (float) $visitas->sum('gastos_adicionais');
        $valorImpostos = (float) $visitas->sum('valor_imposto');

        $custoOperacionalVisitas = $custoProdutos + $custoMaoDeObra + $custoDeslocamento + $gastosAdicionais;
        $lucroBrutoVisitas = $faturamentoVisitas - ($custoOperacionalVisitas + $valorImpostos);

        // Contratos ativos no período sob modelo de mensalidade fixa / preço fechado ou atendimento único pontual
        $contratosAtivos = Contrato::with('cliente')
            ->where('status', Contrato::STATUS_ATIVO)
            ->where(function ($q) {
                $q->whereIn('modelo_cobranca', [
                    Contrato::MODELO_MENSAL,
                    Contrato::MODELO_FIXO_ANUAL,
                    Contrato::MODELO_FECHAMENTO_PERIODO,
                ])->orWhere('periodicidade', Contrato::PERIODICIDADE_UNICO);
            })
            ->whereDate('data_inicio', '<=', $fim->toDateString())
            ->where(function ($q) use ($inicio) {
                $q->where(function ($sub) use ($inicio) {
                    $sub->where('periodicidade', '!=', Contrato::PERIODICIDADE_UNICO)
                        ->where(function ($vig) use ($inicio) {
                            $vig->whereNull('data_fim')
                                ->orWhereDate('data_fim', '>=', $inicio->toDateString());
                        });
                })->orWhere(function ($sub) use ($inicio) {
                    $sub->where('periodicidade', Contrato::PERIODICIDADE_UNICO)
                        ->whereDate('data_inicio', '>=', $inicio->toDateString());
                });
            })
            ->get();

        $faturamentoContratos = (float) $contratosAtivos->sum('valor_total');
        $impostosContratos = (float) $contratosAtivos->sum(fn ($c) => $c->valorImposto());

        // Apuração das provisões operacionais dos contratos ativos
        $provisaoDiariasContratos = 0.0;
        $provisaoDeslocamentoContratos = 0.0;

        foreach ($contratosAtivos as $ctr) {
            $visitasPlanejadas = $ctr->visitasPrevistasPorCiclo();

            $visitasExecutadasQuery = $ctr->visitas()
                ->whereDate('data_hora', '>=', $inicio->toDateString())
                ->whereDate('data_hora', '<=', $fim->toDateString());

            if ($statusFiltro === 'concluida') {
                $visitasExecutadasQuery->where('status', Visita::STATUS_CONCLUIDA);
            } else {
                $visitasExecutadasQuery->where('status', '!=', Visita::STATUS_CANCELADA);
            }

            $visitasExecutadasCount = $visitasExecutadasQuery->count();
            $visitasRestantes = max(0, $visitasPlanejadas - $visitasExecutadasCount);

            $diariaPadrao = (float) ($ctr->custo_diaria_padrao ?? 0.0);
            $deslocamentoPadrao = (float) ($ctr->custo_deslocamento_padrao ?? 0.0);

            $provisaoDiariasContratos += ($visitasRestantes * $diariaPadrao);
            $provisaoDeslocamentoContratos += ($visitasRestantes * $deslocamentoPadrao);

            $custoExecutado = $ctr->custoTotalVisitasNoPeriodo($inicio, $fim);
            $provisaoRestante = $visitasRestantes * ($diariaPadrao + $deslocamentoPadrao);

            $ctr->visitasPlanejadas = $visitasPlanejadas;
            $ctr->visitasExecutadasCount = $visitasExecutadasCount;
            $ctr->visitasRestantes = $visitasRestantes;
            $ctr->custoExecutado = $custoExecutado;
            $ctr->provisaoRestante = $provisaoRestante;
            $ctr->custoTotalProjetado = $custoExecutado + $provisaoRestante;
            $ctr->saldoProjetado = round($ctr->valorLiquidoDisponivel() - $ctr->custoTotalProjetado, 2);
        }

        $provisaoOperacionalContratos = $provisaoDiariasContratos + $provisaoDeslocamentoContratos;

        if ($visao === 'projetado') {
            $totalCustoMaoDeObra = $custoMaoDeObra + $provisaoDiariasContratos;
            $totalCustoDeslocamento = $custoDeslocamento + $provisaoDeslocamentoContratos;
            $custoOperacionalEfetivo = $custoOperacionalVisitas + $provisaoOperacionalContratos;
        } else {
            $totalCustoMaoDeObra = $custoMaoDeObra;
            $totalCustoDeslocamento = $custoDeslocamento;
            $custoOperacionalEfetivo = $custoOperacionalVisitas;
        }

        $totalFaturado = $faturamentoVisitas + $faturamentoContratos;
        $totalImpostos = $valorImpostos + $impostosContratos;
        $totalCustoOperacional = $custoOperacionalEfetivo + $totalImpostos;
        $lucroBrutoTotal = $totalFaturado - $totalCustoOperacional;

        // Despesas operacionais avulsas no período (sem vínculo com OS)
        $despesasGerais = (float) MovimentacaoFinanceira::despesas()
            ->whereNull('visita_id')
            ->whereBetween('data_competencia', [$inicio->toDateString(), $fim->toDateString()])
            ->sum('valor');

        $lucroLiquidoGeral = $lucroBrutoTotal - $despesasGerais;
        $margemBruta = $totalFaturado > 0 ? round(($lucroBrutoTotal / $totalFaturado) * 100, 1) : 0;

        // Indicadores de Conciliação DRE (Competência) vs Fluxo de Caixa (Realizado)
        $contratoIdsAtivos = $contratosAtivos->pluck('id');

        $faturamentoRecebido = (float) MovimentacaoFinanceira::receitas()->pagas()
            ->where(function ($q) use ($visitaIds, $contratoIdsAtivos, $inicio, $fim) {
                $q->whereIn('visita_id', $visitaIds)
                    ->orWhere(function ($sub) use ($contratoIdsAtivos, $inicio, $fim) {
                        $sub->whereIn('contrato_id', $contratoIdsAtivos)
                            ->whereBetween('data_competencia', [$inicio->toDateString(), $fim->toDateString()]);
                    });
            })
            ->sum('valor');

        $faturamentoPendente = (float) MovimentacaoFinanceira::receitas()->pendentes()
            ->where(function ($q) use ($visitaIds, $contratoIdsAtivos, $inicio, $fim) {
                $q->whereIn('visita_id', $visitaIds)
                    ->orWhere(function ($sub) use ($contratoIdsAtivos, $inicio, $fim) {
                        $sub->whereIn('contrato_id', $contratoIdsAtivos)
                            ->whereBetween('data_competencia', [$inicio->toDateString(), $fim->toDateString()]);
                    });
            })
            ->sum('valor');

        $taxaLiquidacaoReceita = $totalFaturado > 0
            ? round(($faturamentoRecebido / $totalFaturado) * 100, 1)
            : 0;

        $diariasPagas = (float) MovimentacaoFinanceira::despesas()->pagas()
            ->whereIn('visita_id', $visitaIds)
            ->whereIn('categoria', ['mao_de_obra', 'diaria_tecnico'])
            ->sum('valor');

        $diariasPendentes = (float) MovimentacaoFinanceira::despesas()->pendentes()
            ->whereIn('visita_id', $visitaIds)
            ->whereIn('categoria', ['mao_de_obra', 'diaria_tecnico'])
            ->sum('valor');

        $gastosAdicionaisPagos = (float) MovimentacaoFinanceira::despesas()->pagas()
            ->whereIn('visita_id', $visitaIds)
            ->where(function ($q) {
                $q->where('categoria', 'gastos_adicionais')
                    ->orWhere(function ($sub) {
                        $sub->where('categoria', 'outros')
                            ->where('descricao', 'like', 'Gastos adicionais OS #%');
                    });
            })
            ->sum('valor');

        $totalReceitasRealizadasPeriodo = (float) MovimentacaoFinanceira::receitas()->pagas()
            ->whereBetween('data_competencia', [$inicio->toDateString(), $fim->toDateString()])
            ->sum('valor');

        $totalDespesasRealizadasPeriodo = (float) MovimentacaoFinanceira::despesas()->pagas()
            ->whereBetween('data_competencia', [$inicio->toDateString(), $fim->toDateString()])
            ->sum('valor');

        $saldoCaixaRealizadoPeriodo = $totalReceitasRealizadasPeriodo - $totalDespesasRealizadasPeriodo;

        $dataInicio = $inicio;
        $dataFim = $fim;
        $totalCustoProdutos = $custoProdutos;
        $totalGastosAdicionais = $gastosAdicionais;
        $margemMedia = $margemBruta;
        $lucroLiquidoEstimado = $lucroLiquidoGeral;

        return compact(
            'mes',
            'periodo',
            'statusFiltro',
            'visao',
            'tituloPeriodo',
            'visitas',
            'contratosAtivos',
            'dataInicio',
            'dataFim',
            'faturamentoVisitas',
            'faturamentoContratos',
            'impostosContratos',
            'custoProdutos',
            'custoMaoDeObra',
            'custoDeslocamento',
            'provisaoDiariasContratos',
            'provisaoDeslocamentoContratos',
            'provisaoOperacionalContratos',
            'valorImpostos',
            'gastosAdicionais',
            'custoOperacionalVisitas',
            'custoOperacionalEfetivo',
            'lucroBrutoVisitas',
            'despesasGerais',
            'lucroLiquidoGeral',
            'margemBruta',
            'totalFaturado',
            'totalCustoProdutos',
            'totalCustoMaoDeObra',
            'totalCustoDeslocamento',
            'totalGastosAdicionais',
            'totalImpostos',
            'totalCustoOperacional',
            'lucroBrutoTotal',
            'margemMedia',
            'lucroLiquidoEstimado',
            'faturamentoRecebido',
            'faturamentoPendente',
            'taxaLiquidacaoReceita',
            'diariasPagas',
            'diariasPendentes',
            'gastosAdicionaisPagos',
            'totalReceitasRealizadasPeriodo',
            'totalDespesasRealizadasPeriodo',
            'saldoCaixaRealizadoPeriodo'
        );
    }
}
