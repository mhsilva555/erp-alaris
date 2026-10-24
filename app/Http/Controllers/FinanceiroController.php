<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\MovimentacaoFinanceira;
use App\Models\Visita;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

        // Aliases para compatibilidade com as views
        $saldoAtual = $saldoRealizado;
        $saldoProjetado = $saldoPrevisto;
        $receitasPagas = $totalReceitasPagas;
        $receitasPendentes = $totalReceitasPendentes;
        $despesasPagas = $totalDespesasPagas;
        $despesasPendentes = $totalDespesasPendentes;

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
            'visitas'
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
     * Relatório Gerencial de Lucro Bruto e Demonstrativo
     */
    public function relatorio(Request $request)
    {
        $periodo = $request->get('periodo', 'mes'); // semana, mes, ano
        $agora = Carbon::now();

        if ($periodo === 'semana') {
            $inicio = $agora->copy()->startOfWeek();
            $fim = $agora->copy()->endOfWeek();
            $tituloPeriodo = 'Semana Atual ('.$inicio->format('d/m').' a '.$fim->format('d/m/Y').')';
        } elseif ($periodo === 'ano') {
            $inicio = $agora->copy()->startOfYear();
            $fim = $agora->copy()->endOfYear();
            $tituloPeriodo = 'Ano de '.$agora->year;
        } else {
            $inicio = $agora->copy()->startOfMonth();
            $fim = $agora->copy()->endOfMonth();
            $tituloPeriodo = 'Mês de '.$agora->translatedFormat('F \d\e Y');
        }

        // Visitas no período
        $visitas = Visita::with('cliente')
            ->whereBetween('data_hora', [$inicio, $fim])
            ->where('status', Visita::STATUS_CONCLUIDA)
            ->get();

        $faturamentoVisitas = (float) $visitas->sum('valor_cobrado');
        $custoProdutos = (float) $visitas->sum('custo_produtos');
        $custoMaoDeObra = (float) $visitas->sum('custo_mao_de_obra');
        $custoDeslocamento = (float) $visitas->sum('custo_deslocamento');
        $gastosAdicionais = (float) $visitas->sum('gastos_adicionais');
        $valorImpostos = (float) $visitas->sum('valor_imposto');

        $custoOperacionalVisitas = $custoProdutos + $custoMaoDeObra + $custoDeslocamento + $gastosAdicionais;
        $lucroBrutoVisitas = $faturamentoVisitas - ($custoOperacionalVisitas + $valorImpostos);

        // Despesas operacionais avulsas no período
        $despesasGerais = (float) MovimentacaoFinanceira::despesas()
            ->whereNull('visita_id')
            ->whereBetween('data_competencia', [$inicio->toDateString(), $fim->toDateString()])
            ->sum('valor');

        $lucroLiquidoGeral = $lucroBrutoVisitas - $despesasGerais;
        $margemBruta = $faturamentoVisitas > 0 ? round(($lucroBrutoVisitas / $faturamentoVisitas) * 100, 1) : 0;

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

        return view('financeiro.relatorios', compact(
            'periodo',
            'tituloPeriodo',
            'visitas',
            'dataInicio',
            'dataFim',
            'faturamentoVisitas',
            'custoProdutos',
            'custoMaoDeObra',
            'custoDeslocamento',
            'valorImpostos',
            'custoOperacionalVisitas',
            'lucroBrutoVisitas',
            'despesasGerais',
            'lucroLiquidoGeral',
            'margemBruta',
            'totalFaturado',
            'totalCustoProdutos',
            'totalCustoMaoDeObra',
            'totalCustoDeslocamento',
            'totalImpostos',
            'totalCustoOperacional',
            'lucroBrutoTotal',
            'margemMedia',
            'lucroLiquidoEstimado'
        ));
    }
}
