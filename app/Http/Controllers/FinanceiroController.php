<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\MovimentacaoFinanceira;
use App\Models\Visita;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceiroController extends Controller
{
    public function index(Request $request)
    {
        $tipo = $request->get('tipo'); // receita, despesa, null (todos)
        $status = $request->get('status'); // pendente, pago, null
        $mes = $request->get('mes', now()->format('Y-m'));

        $query = MovimentacaoFinanceira::with('visita.cliente');

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

        // Dados de conciliação com as OSs concluídas do mês (Regime de Competência DRE vs Fluxo de Caixa)
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

        $clientes = Cliente::orderBy('nome')->get();
        $visitas = Visita::with('cliente')->where('status', Visita::STATUS_CONCLUIDA)->orderByDesc('data_hora')->limit(50)->get();

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
            'faturamentoOSMes',
            'qtdOSConcluidasMes',
            'faturamentoOSRecebido',
            'faturamentoOSPendente',
            'taxaLiquidacaoOS'
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
            'data_pagamento' => 'nullable|date',
            'status' => 'required|in:pendente,pago',
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
        $movimentacao->delete();

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

        DB::transaction(function () use ($acao, $ids) {
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
                MovimentacaoFinanceira::whereIn('id', $ids)->delete();
            }
        });

        $mensagens = [
            'liquidar' => count($ids).' lançamento(s) liquidado(s) com sucesso.',
            'reabrir' => count($ids).' lançamento(s) reaberto(s) com sucesso.',
            'excluir' => count($ids).' lançamento(s) excluído(s) com sucesso.',
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

        // Visitas no período com cliente, técnico, comodato e movimentações financeiras
        $visitasQuery = Visita::with(['cliente', 'tecnico', 'comodato', 'movimentacoes'])
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

        // Despesas operacionais avulsas no período (sem vínculo com OS)
        $despesasGerais = (float) MovimentacaoFinanceira::despesas()
            ->whereNull('visita_id')
            ->whereBetween('data_competencia', [$inicio->toDateString(), $fim->toDateString()])
            ->sum('valor');

        $lucroLiquidoGeral = $lucroBrutoVisitas - $despesasGerais;
        $margemBruta = $faturamentoVisitas > 0 ? round(($lucroBrutoVisitas / $faturamentoVisitas) * 100, 1) : 0;

        // Indicadores de Conciliação DRE (Competência) vs Fluxo de Caixa (Realizado)
        $faturamentoRecebido = (float) MovimentacaoFinanceira::receitas()->pagas()
            ->whereIn('visita_id', $visitaIds)
            ->sum('valor');

        $faturamentoPendente = (float) MovimentacaoFinanceira::receitas()->pendentes()
            ->whereIn('visita_id', $visitaIds)
            ->sum('valor');

        $taxaLiquidacaoReceita = $faturamentoVisitas > 0
            ? round(($faturamentoRecebido / $faturamentoVisitas) * 100, 1)
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
        $totalFaturado = $faturamentoVisitas;
        $totalCustoProdutos = $custoProdutos;
        $totalCustoMaoDeObra = $custoMaoDeObra;
        $totalCustoDeslocamento = $custoDeslocamento;
        $totalGastosAdicionais = $gastosAdicionais;
        $totalImpostos = $valorImpostos;
        $totalCustoOperacional = $custoOperacionalVisitas + $valorImpostos;
        $lucroBrutoTotal = $lucroBrutoVisitas;
        $margemMedia = $margemBruta;
        $lucroLiquidoEstimado = $lucroLiquidoGeral;

        return compact(
            'mes',
            'periodo',
            'statusFiltro',
            'tituloPeriodo',
            'visitas',
            'dataInicio',
            'dataFim',
            'faturamentoVisitas',
            'custoProdutos',
            'custoMaoDeObra',
            'custoDeslocamento',
            'valorImpostos',
            'gastosAdicionais',
            'custoOperacionalVisitas',
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
