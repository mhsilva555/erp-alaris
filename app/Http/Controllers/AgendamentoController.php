<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\EquipamentoComodato;
use App\Models\MovimentacaoFinanceira;
use App\Models\NotificacaoAgendamento;
use App\Models\Tecnico;
use App\Models\Visita;
use App\Services\ContratoAgendamentoService;
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
        $visitasQuery = Visita::with(['cliente', 'tecnico', 'contrato'])
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

        // 1.1. Atendimentos de Contratos Ativos Projetados na Agenda Geral (Opção A)
        // Regra de Negócio: Contratos ativos não gravam O.S. vazias antecipadamente no banco de dados.
        // A Agenda Geral projeta dinamicamente os atendimentos previstos para o período visualizado,
        // permitindo que o gestor visualize os compromissos e clique no evento para emitir e despachar a O.S. manualmente.
        $itensContratosRecorrentes = collect();
        if ((! $user || ! $user->isTecnico()) && ! $tecnicoId) {
            $contratosAtivos = Contrato::with(['cliente', 'itens'])
                ->where('status', Contrato::STATUS_ATIVO)
                ->get();

            $serviceAgendamento = app(ContratoAgendamentoService::class);

            foreach ($contratosAtivos as $ctr) {
                $datasNoPeriodo = $serviceAgendamento->calcularDatasNoPeriodo($ctr, $dataInicio, $dataFim);

                foreach ($datasNoPeriodo as $dataHoraPrevista) {
                    // Prevenção de duplicidade com O.S. já emitida:
                    // Se já existe uma Visita/OS real para este contrato no mesmo dia, não exibe o evento projetado.
                    $jaExisteOs = $visitas->first(function ($v) use ($ctr, $dataHoraPrevista) {
                        return (int) $v->contrato_id === (int) $ctr->id
                            && $v->data_hora->toDateString() === $dataHoraPrevista->toDateString();
                    });

                    if ($jaExisteOs) {
                        continue;
                    }

                    $itensContratosRecorrentes->push([
                        'tipo_item' => 'visita',
                        'data_hora' => $dataHoraPrevista,
                        'titulo' => "Atendimento Contratual: {$ctr->cliente->nome}",
                        'subtitulo' => "Contrato {$ctr->numero} ({$ctr->periodicidadeLabel()}) • {$ctr->cliente->cidade_estado}",
                        'detalhes' => 'Serviço contratual previsto — clique para emitir e despachar a O.S.',
                        'status' => 'Previsto (Contrato)',
                        'url' => route('visitas.create', [
                            'cliente_id' => $ctr->cliente_id,
                            'contrato_id' => $ctr->id,
                            'data' => $dataHoraPrevista->format('Y-m-d\TH:i'),
                            'tipo_servico' => $ctr->tipo_servico,
                        ]),
                        'model' => $ctr,
                        'contrato' => $ctr,
                        'eh_recorrente_projetado' => true,
                        'eh_contrato_projetado' => true,
                    ]);
                }
            }
        }

        // 1.2. Visitas Contratuais Recorrentes Projetadas para Clientes Ativos (Fallback Legado sem Contrato Ativo)
        $itensVisitasRecorrentes = collect();
        if ((! $user || ! $user->isTecnico()) && ! $tecnicoId) {
            $clientesRecorrentes = Cliente::with('visitas')
                ->where('status_prospeccao', Cliente::STATUS_ATIVO)
                ->whereDoesntHave('contratos', fn ($q) => $q->where('status', Contrato::STATUS_ATIVO))
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

                $visitasClienteNoMes = $visitas->filter(function ($v) use ($cli, $dataInicio, $dataFim) {
                    return (int) $v->cliente_id === (int) $cli->id
                        && $v->data_hora->gte($dataInicio)
                        && $v->data_hora->lte($dataFim);
                });

                $intervaloMeses = match ($cli->periodicidade) {
                    'mensal' => 1,
                    'bimestral' => 2,
                    'trimestral' => 3,
                    'semestral' => 6,
                    'anual' => 12,
                    default => null,
                };

                $deveProjetar = false;
                $datasParaProjetar = [];

                if ($cli->periodicidade === 'quinzenal') {
                    $diasValidos = [1, 15];
                    foreach ($diasValidos as $dia) {
                        $candidata = $dataInicio->copy()->day($dia)->setTime(9, 0);
                        if ($candidata->gte($dataCriacao) && $candidata->lte($dataFim)) {
                            $datasParaProjetar[] = $candidata;
                        }
                    }
                    $deveProjetar = count($datasParaProjetar) > 0;
                } elseif ($intervaloMeses !== null) {
                    if ($mesesDiff >= 0 && ($mesesDiff % $intervaloMeses === 0)) {
                        $deveProjetar = true;
                        $candidata = $dataInicio->copy()->day($preferredDay)->setTime(9, 0);
                        if ($candidata->gte($dataCriacao) && $candidata->lte($dataFim)) {
                            $datasParaProjetar[] = $candidata;
                        }
                    }
                }

                if ($deveProjetar) {
                    $periodoNome = ucfirst($cli->periodicidade);
                    foreach ($datasParaProjetar as $dataHoraItem) {
                        $jaTemVisitaNoDia = $visitasClienteNoMes->contains(function ($v) use ($dataHoraItem) {
                            return $v->data_hora->toDateString() === $dataHoraItem->toDateString();
                        });

                        if (! $jaTemVisitaNoDia) {
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
            $todosComodatos = EquipamentoComodato::with(['cliente', 'produto', 'contrato'])
                ->whereNotNull('data_proxima_reposicao')
                ->where(function ($q) {
                    $q->whereNull('contrato_id')
                        ->orWhereHas('contrato', function ($cq) {
                            $cq->where('status', Contrato::STATUS_ATIVO);
                        });
                })
                ->get();

            $comodatos = $todosComodatos;
            $ocorrenciasComodatosBrutas = collect();

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
                        $ocorrenciasComodatosBrutas->push([
                            'comodato' => $c,
                            'data' => $cur->copy()->startOfDay(),
                            'data_str' => $cur->toDateString(),
                            'eh_anchor_original' => $ehAnchorOriginal,
                        ]);
                    }
                    $cur->addDays($intervalo);
                }
            }

            // Agrupa ocorrências de comodatos vinculados ao mesmo contrato que caem no mesmo dia,
            // gerando um único evento e uma única O.S. (evitando duplicidade de diária técnica e deslocamento).
            $gruposComodatos = $ocorrenciasComodatosBrutas->groupBy(function (array $oc) {
                $c = $oc['comodato'];
                if ($c->contrato_id) {
                    return "contrato_{$c->contrato_id}_{$oc['data_str']}";
                }

                return "avulso_{$c->id}_{$oc['data_str']}";
            });

            foreach ($gruposComodatos as $grupo) {
                $primeira = $grupo->first();
                /** @var EquipamentoComodato $cPrincipal */
                $cPrincipal = $primeira['comodato'];
                /** @var Carbon $dataRef */
                $dataRef = $primeira['data'];

                if ($grupo->count() === 1) {
                    $ehAnchorOriginal = $primeira['eh_anchor_original'];
                    $itensComodatos->push([
                        'tipo_item' => 'comodato',
                        'data_hora' => $dataRef->copy()->setTime(9, 0),
                        'titulo' => "Reposição: {$cPrincipal->nome_equipamento}",
                        'subtitulo' => $cPrincipal->cliente->nome.($cPrincipal->contrato ? " • Contrato {$cPrincipal->contrato->numero}" : ''),
                        'detalhes' => "Periodicidade: a cada {$cPrincipal->periodicidade_dias} dias".($cPrincipal->produto ? " • Insumo: {$cPrincipal->produto->nome}" : ''),
                        'status' => $ehAnchorOriginal ? ($cPrincipal->estaAtrasado() ? 'Atrasado' : 'Agendado') : 'Previsto (Recorrente)',
                        'url' => route('comodatos.show', $cPrincipal),
                        'url_os' => route('visitas.create', array_filter([
                            'cliente_id' => $cPrincipal->cliente_id,
                            'contrato_id' => $cPrincipal->contrato_id,
                            'comodato_id' => $cPrincipal->id,
                            'data' => $dataRef->format('Y-m-d\T09:00'),
                            'observacoes' => "Reposição de comodato: {$cPrincipal->nome_equipamento} ({$cPrincipal->quantidade} un)".($cPrincipal->contrato ? " (Contrato {$cPrincipal->contrato->numero})" : ''),
                        ])),
                        'model' => $cPrincipal,
                        'eh_recorrente_projetado' => ! $ehAnchorOriginal,
                    ]);
                } else {
                    // Múltiplos comodatos do mesmo contrato no mesmo dia -> Unificados em 1 único evento e 1 única O.S.
                    $comodatosDoGrupo = $grupo->pluck('comodato');
                    $algumAnchorOriginal = $grupo->contains(fn ($oc) => $oc['eh_anchor_original']);
                    $algumAtrasado = $grupo->contains(fn ($oc) => $oc['eh_anchor_original'] && $oc['comodato']->estaAtrasado());

                    $resumoEquipamentos = $comodatosDoGrupo->map(function (EquipamentoComodato $item) {
                        return "{$item->nome_equipamento} ({$item->quantidade} un)";
                    })->implode(', ');

                    $insumosUnicos = $comodatosDoGrupo
                        ->map(fn (EquipamentoComodato $item) => $item->produto?->nome)
                        ->filter()
                        ->unique()
                        ->implode(', ');

                    $ciclosUnicos = $comodatosDoGrupo
                        ->pluck('periodicidade_dias')
                        ->unique()
                        ->sort()
                        ->implode('/');

                    $itensComodatos->push([
                        'tipo_item' => 'comodato',
                        'data_hora' => $dataRef->copy()->setTime(9, 0),
                        'titulo' => "Reposição: {$resumoEquipamentos}",
                        'subtitulo' => $cPrincipal->cliente->nome.($cPrincipal->contrato ? " • Contrato {$cPrincipal->contrato->numero} ({$comodatosDoGrupo->count()} equipamentos unificados)" : ''),
                        'detalhes' => "Periodicidade: a cada {$ciclosUnicos} dias".($insumosUnicos !== '' ? " • Insumos: {$insumosUnicos}" : ''),
                        'status' => $algumAnchorOriginal ? ($algumAtrasado ? 'Atrasado' : 'Agendado') : 'Previsto (Recorrente)',
                        'url' => $cPrincipal->contrato ? route('contratos.show', $cPrincipal->contrato) : route('comodatos.show', $cPrincipal),
                        'url_os' => route('visitas.create', array_filter([
                            'cliente_id' => $cPrincipal->cliente_id,
                            'contrato_id' => $cPrincipal->contrato_id,
                            'comodato_id' => $cPrincipal->id,
                            'data' => $dataRef->format('Y-m-d\T09:00'),
                            'observacoes' => "Reposição unificada de comodatos ({$cPrincipal->contrato?->numero}): {$resumoEquipamentos}",
                        ])),
                        'model' => $cPrincipal,
                        'eh_recorrente_projetado' => ! $algumAnchorOriginal,
                        'eh_comodato_unificado' => true,
                    ]);
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

        // Totais gerais (incluindo ocorrências reais, contratos projetados, legados e retornos)
        $totais = [
            'total_visitas' => $visitas->count() + $itensContratosRecorrentes->count() + $itensVisitasRecorrentes->count() + $itensRetornosPreventivos->count(),
            'total_comodatos' => $itensComodatos->count(),
            'total_followups' => $followups->count(),
            'total_geral' => $visitas->count() + $itensContratosRecorrentes->count() + $itensVisitasRecorrentes->count() + $itensRetornosPreventivos->count() + $itensComodatos->count() + $followups->count(),
        ];

        // Mapeamento dos itens de visitas reais para visualização unificada na agenda
        $itensVisitas = $visitas->map(fn (Visita $v) => [
            'tipo_item' => 'visita',
            'data_hora' => $v->data_hora,
            'titulo' => "OS #{$v->id} — {$v->cliente->nome}",
            'subtitulo' => $v->tipoServicoLabel().' • '.($v->tecnico?->nome ?? 'Sem técnico').($v->contrato ? " • Contrato: {$v->contrato->numero}" : '').($v->comodato_id ? ' • Reposição de Comodato' : ''),
            'detalhes' => $v->area_tratada ? "Área: {$v->area_tratada}" : ($v->tipo_praga ?? 'Monitoramento'),
            'status' => $v->statusLabel(),
            'url' => route('visitas.show', $v),
            'model' => $v,
            'contrato' => $v->contrato,
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
                ->concat($itensContratosRecorrentes)
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

            if ($comodato->contrato_id && $comodato->data_proxima_reposicao) {
                EquipamentoComodato::where('contrato_id', $comodato->contrato_id)
                    ->whereDate('data_proxima_reposicao', $comodato->data_proxima_reposicao->toDateString())
                    ->update(['data_proxima_reposicao' => null]);
            } else {
                $comodato->update(['data_proxima_reposicao' => null]);
            }

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
