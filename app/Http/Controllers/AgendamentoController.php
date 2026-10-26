<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\EquipamentoComodato;
use App\Models\MovimentacaoFinanceira;
use App\Models\NotificacaoAgendamento;
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

        // Se o usuário for Administrador, ele não precisa ver Ordens de Serviços geradas
        // para um comodato na Agenda, pois dentro do Evento de Comodato já existem as OSs vinculadas.
        // Se for um técnico, exibe normalmente para que ele execute sua OS de campo.
        if (! $user || $user->isAdmin()) {
            $visitasQuery->whereNull('comodato_id');
        }

        if ($tecnicoId) {
            $visitasQuery->where('tecnico_id', $tecnicoId);
        }

        $visitas = $visitasQuery->orderBy('data_hora')->get();

        // 1.1. Visitas Contratuais Recorrentes Projetadas para Clientes Ativos
        $itensVisitasRecorrentes = collect();
        if ((! $user || $user->isAdmin()) && ! $tecnicoId) {
            $clientesRecorrentes = Cliente::with('visitas')
                ->where('status_prospeccao', Cliente::STATUS_ATIVO)
                ->whereIn('periodicidade', ['quinzenal', 'mensal', 'bimestral', 'trimestral', 'semestral', 'anual'])
                ->get();

            foreach ($clientesRecorrentes as $cli) {
                $dataCriacao = $cli->created_at ? $cli->created_at->copy()->startOfDay() : now()->startOfDay();

                // Se o mês visualizado for anterior à data de cadastro do cliente, não projeta recorrências
                if ($dataFim->lt($dataCriacao)) {
                    continue;
                }

                $ultimaVisita = $cli->visitas->where('status', '!=', Visita::STATUS_CANCELADA)->sortByDesc('data_hora')->first();
                $anchorDate = $ultimaVisita ? $ultimaVisita->data_hora->copy() : ($cli->created_at ? $cli->created_at->copy() : now());
                $preferredDay = min(max(1, (int) $anchorDate->day), 28);

                $anoMesViewed = $dataInicio->copy();
                $mesesDiff = ($anoMesViewed->year - $anchorDate->year) * 12 + ($anoMesViewed->month - $anchorDate->month);

                $visitasClienteNoMes = $visitas->where('cliente_id', $cli->id);

                if ($cli->periodicidade === 'quinzenal') {
                    $primeiroDia = min($preferredDay, 15);
                    $segundoDia = max($preferredDay, 16);
                    if ($primeiroDia === $segundoDia) {
                        $segundoDia = min(28, $primeiroDia + 15);
                    }

                    $diasParaProjetar = [];
                    if ($visitasClienteNoMes->count() === 0) {
                        $diasParaProjetar = [$primeiroDia, $segundoDia];
                    } elseif ($visitasClienteNoMes->count() === 1) {
                        $diaExistente = $visitasClienteNoMes->first()->data_hora->day;
                        if ($diaExistente <= 15) {
                            $diasParaProjetar = [$segundoDia];
                        } else {
                            $diasParaProjetar = [$primeiroDia];
                        }
                    }

                    foreach ($diasParaProjetar as $dia) {
                        $dataHoraItem = Carbon::parse($anoMesViewed->format('Y-m-').str_pad((string) $dia, 2, '0', STR_PAD_LEFT).' 09:00:00');

                        // Ignora dias anteriores à data de cadastro do cliente (evita criar eventos 'passados' no mês de entrada)
                        if ($dataHoraItem->lt($dataCriacao)) {
                            continue;
                        }

                        $itensVisitasRecorrentes->push([
                            'tipo_item' => 'visita',
                            'data_hora' => $dataHoraItem,
                            'titulo' => "Visita Contratual: {$cli->nome}",
                            'subtitulo' => 'Contrato Quinzenal • '.$cli->cidade_estado,
                            'detalhes' => 'Serviço quinzenal previsto — clique para agendar a OS',
                            'status' => 'Previsto (Recorrente)',
                            'url' => route('visitas.create', ['cliente_id' => $cli->id, 'data' => $dataHoraItem->toDateString()]),
                            'model' => $cli,
                            'eh_recorrente_projetado' => true,
                        ]);
                    }
                } else {
                    $intervaloMeses = match ($cli->periodicidade) {
                        'mensal' => 1,
                        'bimestral' => 2,
                        'trimestral' => 3,
                        'semestral' => 6,
                        'anual' => 12,
                        default => null,
                    };

                    if ($intervaloMeses && abs($mesesDiff) % $intervaloMeses === 0) {
                        if ($visitasClienteNoMes->isEmpty()) {
                            $dataHoraItem = Carbon::parse($anoMesViewed->format('Y-m-').str_pad((string) $preferredDay, 2, '0', STR_PAD_LEFT).' 09:00:00');

                            // Ignora datas anteriores à data de cadastro do cliente
                            if ($dataHoraItem->lt($dataCriacao)) {
                                continue;
                            }

                            $periodoNome = ucfirst($cli->periodicidade);
                            $itensVisitasRecorrentes->push([
                                'tipo_item' => 'visita',
                                'data_hora' => $dataHoraItem,
                                'titulo' => "Visita Contratual: {$cli->nome}",
                                'subtitulo' => "Contrato {$periodoNome} • {$cli->cidade_estado}",
                                'detalhes' => "Serviço {$cli->periodicidade} previsto — clique para agendar a OS",
                                'status' => 'Previsto (Recorrente)',
                                'url' => route('visitas.create', ['cliente_id' => $cli->id, 'data' => $dataHoraItem->toDateString()]),
                                'model' => $cli,
                                'eh_recorrente_projetado' => true,
                            ]);
                        }
                    }
                }
            }
        }

        // 1.2. Previsões de Retorno Preventivo / Reforço Agendados
        $itensRetornosPreventivos = collect();
        $retornosQuery = Visita::with(['cliente', 'tecnico'])
            ->whereNotNull('proxima_visita')
            ->whereDate('proxima_visita', '>=', $dataInicio->toDateString())
            ->whereDate('proxima_visita', '<=', $dataFim->toDateString())
            ->where('status', '!=', Visita::STATUS_CANCELADA);

        if ($user && $user->isTecnico()) {
            $retornosQuery->where('tecnico_id', $user->tecnico_id);
        } elseif ($tecnicoId) {
            $retornosQuery->where('tecnico_id', $tecnicoId);
        }

        $visitasComRetorno = $retornosQuery->get();

        foreach ($visitasComRetorno as $vr) {
            $itensRetornosPreventivos->push([
                'tipo_item' => 'visita',
                'data_hora' => $vr->proxima_visita,
                'titulo' => "Retorno Preventivo: {$vr->cliente->nome}",
                'subtitulo' => "Ref. OS #{$vr->id} • {$vr->tipoServicoLabel()} • ".($vr->tecnico?->nome ?? 'Sem técnico'),
                'detalhes' => 'Foco: '.($vr->tipo_praga ?? 'Geral').($vr->area_tratada ? " • {$vr->area_tratada}" : ''),
                'status' => 'Retorno Previsto',
                'url' => route('visitas.create', [
                    'cliente_id' => $vr->cliente_id,
                    'tecnico_id' => $vr->tecnico_id,
                    'tipo_servico' => $vr->tipo_servico,
                    'tipo_praga' => $vr->tipo_praga,
                    'area_tratada' => $vr->area_tratada,
                    'data' => $vr->proxima_visita->format('Y-m-d\TH:i'),
                    'observacoes' => "Retorno preventivo / reforço referente à OS #{$vr->id}.",
                ]),
                'model' => $vr,
                'eh_recorrente_projetado' => true,
                'eh_retorno_preventivo' => true,
            ]);
        }

        // 2. Comodatos com Reposição Programada e Recorrências Projetadas
        $comodatos = collect();
        $itensComodatos = collect();

        if (! $user || $user->isAdmin()) {
            $todosComodatos = EquipamentoComodato::with(['cliente', 'produto'])
                ->whereNotNull('data_proxima_reposicao')
                ->get();

            $comodatos = $todosComodatos;

            foreach ($todosComodatos as $c) {
                $anchor = $c->data_proxima_reposicao->copy()->startOfDay();
                $intervalo = max(1, (int) $c->periodicidade_dias);

                // Se a reposição original cadastrada for além do fim do mês, não gera ciclos neste mês
                if ($anchor->gt($dataFim)) {
                    continue;
                }

                $cur = $anchor->copy();

                // Se a âncora foi em mês anterior, calculamos os ciclos futuros que caem no mês visualizado
                if ($cur->lt($dataInicio)) {
                    $diffDias = $cur->diffInDays($dataInicio, false);
                    $passos = (int) ceil($diffDias / $intervalo);
                    $cur->addDays($passos * $intervalo);
                }

                $iteracoes = 0;
                while ($cur->lte($dataFim) && $iteracoes < 60) {
                    $iteracoes++;
                    if ($cur->gte($dataInicio)) {
                        $ehAnchorOriginal = $cur->isSameDay($c->data_proxima_reposicao);
                        $itensComodatos->push([
                            'tipo_item' => 'comodato',
                            'data_hora' => $cur->copy()->setTime(9, 0),
                            'titulo' => "Reposição: {$c->nome_equipamento}",
                            'subtitulo' => $c->cliente->nome,
                            'detalhes' => "Periodicidade: a cada {$c->periodicidade_dias} dias".($c->produto ? " • Insumo: {$c->produto->nome}" : ''),
                            'status' => $ehAnchorOriginal ? ($c->estaAtrasado() ? 'Atrasado' : 'Agendado') : 'Previsto (Recorrente)',
                            'url' => route('comodatos.show', $c),
                            'url_os' => route('visitas.create', [
                                'cliente_id' => $c->cliente_id,
                                'comodato_id' => $c->id,
                                'data' => $cur->format('Y-m-d\T09:00'),
                                'observacoes' => "Reposição de comodato: {$c->nome_equipamento} ({$c->quantidade} un)",
                            ]),
                            'model' => $c,
                            'eh_recorrente_projetado' => ! $ehAnchorOriginal,
                        ]);
                    }
                    $cur->addDays($intervalo);
                }
            }
        }

        // 3. Follow-ups de Clientes em Prospecção
        $followups = collect();
        if (! $user || $user->isAdmin()) {
            $followups = Cliente::whereNotNull('data_followup')
                ->whereDate('data_followup', '>=', $dataInicio->toDateString())
                ->whereDate('data_followup', '<=', $dataFim->toDateString())
                ->orderBy('data_followup')
                ->get();
        }

        // Totais gerais (incluindo ocorrências reais, recorrentes projetadas e retornos preventivos)
        $totais = [
            'total_visitas' => $visitas->count() + $itensVisitasRecorrentes->count() + $itensRetornosPreventivos->count(),
            'total_comodatos' => $itensComodatos->count(),
            'total_followups' => $followups->count(),
            'total_geral' => $visitas->count() + $itensVisitasRecorrentes->count() + $itensRetornosPreventivos->count() + $itensComodatos->count() + $followups->count(),
        ];

        // Mapeamento dos itens de visitas reais para visualização unificada na agenda
        $itensVisitas = $visitas->map(fn (Visita $v) => [
            'tipo_item' => 'visita',
            'data_hora' => $v->data_hora,
            'titulo' => "OS #{$v->id} — {$v->cliente->nome}",
            'subtitulo' => $v->tipoServicoLabel().' • '.($v->tecnico?->nome ?? 'Sem técnico').($v->comodato_id ? ' • Reposição de Comodato' : ''),
            'detalhes' => $v->area_tratada ? "Área: {$v->area_tratada}" : ($v->tipo_praga ?? 'Monitoramento'),
            'status' => $v->statusLabel(),
            'url' => route('visitas.show', $v),
            'model' => $v,
            'eh_recorrente_projetado' => false,
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
            'eh_recorrente_projetado' => false,
        ]);

        // Filtrar de acordo com o filtro selecionado
        $agenda = collect();
        if ($tipoFiltro === 'todos' || $tipoFiltro === 'visitas') {
            $agenda = $agenda->concat($itensVisitas)
                ->concat($itensVisitasRecorrentes)
                ->concat($itensRetornosPreventivos);
        }
        if ($tipoFiltro === 'todos' || $tipoFiltro === 'comodatos') {
            $agenda = $agenda->concat($itensComodatos);
        }
        if ($tipoFiltro === 'todos' || $tipoFiltro === 'followups') {
            $agenda = $agenda->concat($itensFollowups);
        }

        $agendaOrdenada = $agenda->sortBy('data_hora')->values();
        $tecnicos = Tecnico::where('ativo', true)->orderBy('nome')->get();

        $modo = $request->get('modo', 'lista');
        if (! in_array($modo, ['lista', 'calendario'])) {
            $modo = 'lista';
        }

        // Preparação da grade do calendário mensal (Domingo a Sábado)
        $primeiroDiaGrade = $dataInicio->copy()->startOfWeek(Carbon::SUNDAY);
        $ultimoDiaGrade = $dataFim->copy()->endOfWeek(Carbon::SATURDAY);

        $eventosPorDia = $agendaOrdenada->groupBy(fn ($item) => $item['data_hora']->format('Y-m-d'));

        $gradeCalendario = [];
        $diaCursor = $primeiroDiaGrade->copy();

        while ($diaCursor->lte($ultimoDiaGrade)) {
            $dataStr = $diaCursor->format('Y-m-d');
            $itensDoDia = $eventosPorDia->get($dataStr, collect());
            $gradeCalendario[] = [
                'data' => $diaCursor->copy(),
                'data_str' => $dataStr,
                'dia' => $diaCursor->day,
                'dia_numero' => $diaCursor->day,
                'eh_mes_atual' => $diaCursor->month === $dataInicio->month,
                'eh_hoje' => $diaCursor->isToday(),
                'itens' => $itensDoDia,
                'eventos' => $itensDoDia,
                'total_itens' => $itensDoDia->count(),
            ];
            $diaCursor->addDay();
        }

        return view('agendamentos.index', compact(
            'mes',
            'dataInicio',
            'dataFim',
            'tipoFiltro',
            'modo',
            'totais',
            'agendaOrdenada',
            'gradeCalendario',
            'visitas',
            'comodatos',
            'followups',
            'tecnicos',
            'tecnicoId'
        ));
    }

    /**
     * Remove um evento da agenda (Visita/OS, Reposição de Comodato ou Follow-up CRM).
     */
    public function destroy(Request $request, string $tipo, int $id)
    {
        $user = $request->user();

        if ($tipo === 'visita') {
            $visita = Visita::findOrFail($id);
            if ($user && $user->isTecnico() && $visita->tecnico_id !== $user->tecnico_id) {
                abort(403, 'Acesso restrito. Você só pode remover seus próprios agendamentos.');
            }

            if ($visita->status === Visita::STATUS_CONCLUIDA) {
                $visita->estornarEstoqueProdutos();
                $visita->update(['status' => Visita::STATUS_CANCELADA]);
                $visita->movimentacoes()->where('status', MovimentacaoFinanceira::STATUS_PENDENTE)->delete();
                NotificacaoAgendamento::notificarOsCancelada($visita);
            } else {
                NotificacaoAgendamento::notificarOsCancelada($visita);
                $visita->delete();
            }

            return back()->with('sucesso', "Agendamento da OS #{$id} removido com sucesso da agenda.");
        }

        if ($tipo === 'retorno_preventivo') {
            $visita = Visita::findOrFail($id);
            if ($user && $user->isTecnico() && $visita->tecnico_id !== $user->tecnico_id) {
                abort(403, 'Acesso restrito. Você só pode remover seus próprios agendamentos.');
            }

            $visita->update(['proxima_visita' => null]);

            return back()->with('sucesso', "Previsão de retorno preventivo da OS #{$id} removida da agenda.");
        }

        if ($tipo === 'comodato') {
            if ($user && $user->isTecnico()) {
                abort(403, 'Acesso restrito a administradores.');
            }
            $comodato = EquipamentoComodato::findOrFail($id);
            $comodato->update(['data_proxima_reposicao' => null]);

            return back()->with('sucesso', "Alerta de reposição do equipamento '{$comodato->nome_equipamento}' removido da agenda.");
        }

        if ($tipo === 'followup') {
            if ($user && $user->isTecnico()) {
                abort(403, 'Acesso restrito a administradores.');
            }
            $cliente = Cliente::findOrFail($id);
            $cliente->update(['data_followup' => null]);

            return back()->with('sucesso', "Follow-up do cliente '{$cliente->nome}' removido da agenda.");
        }

        abort(404, 'Tipo de agendamento inválido.');
    }
}
