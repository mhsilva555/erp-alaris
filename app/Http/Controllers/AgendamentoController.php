<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\EquipamentoComodato;
use App\Models\Tecnico;
use App\Models\Visita;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AgendamentoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $mes = $request->get('mes', now()->format('Y-m'));
        $tipoFiltro = $request->get('tipo', 'todos'); // todos, visitas, comodatos, followups
        $tecnicoId = $request->get('tecnico_id');

        if ($user && $user->isTecnico()) {
            $tecnicoId = $user->tecnico_id;
        }

        // Definir intervalo do mês selecionado
        $dataInicio = Carbon::parse($mes.'-01')->startOfMonth();
        $dataFim = Carbon::parse($mes.'-01')->endOfMonth();

        // 1. Visitas Agendadas no período
        $visitasQuery = Visita::with(['cliente', 'tecnico'])
            ->whereBetween('data_hora', [$dataInicio, $dataFim])
            ->where('status', '!=', Visita::STATUS_CANCELADA);

        if ($tecnicoId) {
            $visitasQuery->where('tecnico_id', $tecnicoId);
        }

        $visitas = $visitasQuery->orderBy('data_hora')->get();

        // 2. Comodatos com Reposição Programada
        $comodatos = collect();
        if (! $user || $user->isAdmin()) {
            $comodatos = EquipamentoComodato::with(['cliente', 'produto'])
                ->whereBetween('data_proxima_reposicao', [$dataInicio->toDateString(), $dataFim->toDateString()])
                ->orderBy('data_proxima_reposicao')
                ->get();
        }

        // 3. Follow-ups de Clientes em Prospecção
        $followups = collect();
        if (! $user || $user->isAdmin()) {
            $followups = Cliente::whereNotNull('data_followup')
                ->whereBetween('data_followup', [$dataInicio->toDateString(), $dataFim->toDateString()])
                ->orderBy('data_followup')
                ->get();
        }

        // Totais gerais
        $totais = [
            'total_visitas' => $visitas->count(),
            'total_comodatos' => $comodatos->count(),
            'total_followups' => $followups->count(),
            'total_geral' => $visitas->count() + $comodatos->count() + $followups->count(),
        ];

        // Mapeamento dos itens para visualização unificada na agenda
        $itensVisitas = $visitas->map(fn (Visita $v) => [
            'tipo_item' => 'visita',
            'data_hora' => $v->data_hora,
            'titulo' => "OS #{$v->id} — {$v->cliente->nome}",
            'subtitulo' => $v->tipoServicoLabel().' • '.($v->tecnico?->nome ?? 'Sem técnico'),
            'detalhes' => $v->area_tratada ? "Área: {$v->area_tratada}" : ($v->tipo_praga ?? 'Monitoramento'),
            'status' => $v->statusLabel(),
            'url' => route('visitas.show', $v),
            'model' => $v,
        ]);

        $itensComodatos = $comodatos->map(fn (EquipamentoComodato $c) => [
            'tipo_item' => 'comodato',
            'data_hora' => $c->data_proxima_reposicao ? $c->data_proxima_reposicao->copy()->setTime(9, 0) : now(),
            'titulo' => "Reposição: {$c->nome_equipamento}",
            'subtitulo' => $c->cliente->nome,
            'detalhes' => "Periodicidade: a cada {$c->periodicidade_dias} dias".($c->produto ? " • Insumo: {$c->produto->nome}" : ''),
            'status' => $c->estaAtrasado() ? 'Atrasado' : 'Agendado',
            'url' => route('comodatos.edit', $c),
            'model' => $c,
        ]);

        $itensFollowups = $followups->map(fn (Cliente $cli) => [
            'tipo_item' => 'followup',
            'data_hora' => Carbon::parse($cli->data_followup)->setTime(10, 0),
            'titulo' => "Follow-up: {$cli->nome}",
            'subtitulo' => $cli->cidade_estado.' • Tel: '.($cli->telefone ?? '—'),
            'detalhes' => "Status: {$cli->status_prospeccao_label}".($cli->observacoes ? " — {$cli->observacoes}" : ''),
            'status' => 'Pendente',
            'url' => route('clientes.show', $cli),
            'model' => $cli,
        ]);

        // Filtrar de acordo com o filtro selecionado
        $agenda = collect();
        if ($tipoFiltro === 'todos' || $tipoFiltro === 'visitas') {
            $agenda = $agenda->concat($itensVisitas);
        }
        if ($tipoFiltro === 'todos' || $tipoFiltro === 'comodatos') {
            $agenda = $agenda->concat($itensComodatos);
        }
        if ($tipoFiltro === 'todos' || $tipoFiltro === 'followups') {
            $agenda = $agenda->concat($itensFollowups);
        }

        $agendaOrdenada = $agenda->sortBy('data_hora')->values();
        $tecnicos = Tecnico::where('ativo', true)->orderBy('nome')->get();

        return view('agendamentos.index', compact(
            'mes',
            'dataInicio',
            'dataFim',
            'tipoFiltro',
            'totais',
            'agendaOrdenada',
            'visitas',
            'comodatos',
            'followups',
            'tecnicos',
            'tecnicoId'
        ));
    }
}
