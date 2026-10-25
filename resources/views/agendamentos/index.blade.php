@extends('layouts.app')

@section('title', 'Agenda Geral Unificada')

@section('content')
    <div class="space-y-6">

        {{-- Header & Filtros --}}
        <div class="card p-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Agenda Geral Unificada
                    </h2>
                    <p class="text-sm mt-1" style="color: var(--color-surface-400);">
                        Consolidação de Ordens de Serviço, Reposições de Comodato e Follow-ups Comerciais.
                    </p>
                </div>

                <form method="GET" action="{{ route('agendamentos.index') }}" class="flex flex-wrap items-center gap-3">
                    <input type="hidden" name="modo" id="form-modo-input" value="{{ $modo }}">
                    <input type="hidden" name="tipo" value="{{ $tipoFiltro }}">
                    @if (request('tecnico_id'))
                        <input type="hidden" name="tecnico_id" value="{{ request('tecnico_id') }}">
                    @endif

                    <x-month-picker :value="$mes" />

                    {{-- Seletor de Modo: Lista / Calendário --}}
                    <div class="tab-pills">
                        <button type="button" onclick="alternarModoAgenda('lista')" id="btn-modo-lista"
                            class="tab-pill {{ $modo === 'lista' ? 'active' : '' }} flex items-center gap-1.5 text-xs py-1.5 px-3">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                            <span>Lista</span>
                        </button>
                        <button type="button" onclick="alternarModoAgenda('calendario')" id="btn-modo-calendario"
                            class="tab-pill {{ $modo === 'calendario' ? 'active' : '' }} flex items-center gap-1.5 text-xs py-1.5 px-3">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Calendário</span>
                        </button>
                    </div>

                    <div class="tab-pills">
                        <a href="{{ route('agendamentos.index', ['mes' => $mes, 'tipo' => 'todos', 'modo' => $modo]) }}"
                            class="tab-pill {{ $tipoFiltro === 'todos' ? 'active' : '' }}">
                            Todos ({{ $totais['total_geral'] }})
                        </a>
                        <a href="{{ route('agendamentos.index', ['mes' => $mes, 'tipo' => 'visitas', 'modo' => $modo]) }}"
                            class="tab-pill {{ $tipoFiltro === 'visitas' ? 'active' : '' }}">
                            Visitas ({{ $totais['total_visitas'] }})
                        </a>
                        <a href="{{ route('agendamentos.index', ['mes' => $mes, 'tipo' => 'comodatos', 'modo' => $modo]) }}"
                            class="tab-pill {{ $tipoFiltro === 'comodatos' ? 'active' : '' }}">
                            Comodatos ({{ $totais['total_comodatos'] }})
                        </a>
                        <a href="{{ route('agendamentos.index', ['mes' => $mes, 'tipo' => 'followups', 'modo' => $modo]) }}"
                            class="tab-pill {{ $tipoFiltro === 'followups' ? 'active' : '' }}">
                            Follow-ups ({{ $totais['total_followups'] }})
                        </a>
                    </div>
                </form>
            </div>

            {{-- Barra de Monitoramento Minuto a Minuto & Alertas no SO (oculta quando permissões já concedidas) --}}
            <div id="banner-notif-agenda"
                class="mb-6 p-3 rounded-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                        style="background: rgba(20,112,245,.15); color: var(--color-primary-400);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-white flex items-center gap-2">
                            <span>Monitoramento Automático Minuto a Minuto</span>
                            <span class="badge badge-info text-[10px]">Cron Ativa</span>
                        </div>
                        <div class="text-[11px]" style="color: var(--color-surface-400);">
                            Alertas nativos emitidos no computador (Windows/Mac/Linux) e no celular (Android/iOS PWA) para
                            eventos iminentes.
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" onclick="window.solicitarNotificacoesSO && window.solicitarNotificacoesSO()"
                        class="btn btn-primary btn-sm text-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Ativar Alertas no SO
                    </button>
                    <button type="button" onclick="window.testarNotificacaoSO && window.testarNotificacaoSO()"
                        class="btn btn-secondary btn-sm text-xs" title="Dispara um alerta de teste imediato no dispositivo">
                        Testar Alerta
                    </button>
                </div>
            </div>
            <script>
                if ('Notification' in window && Notification.permission === 'granted') {
                    document.getElementById('banner-notif-agenda')?.classList.add('hidden');
                }
            </script>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3.5 lg:gap-4">
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="stat-label">Total Geral</span>
                            <div class="stat-value text-white">{{ $totais['total_geral'] }}</div>
                            <div class="stat-helper">No mês selecionado</div>
                        </div>
                        <div class="stat-icon" style="background: rgba(16,185,129,.12); color: var(--color-success);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="stat-label text-blue-400">Visitas & OS</span>
                            <div class="stat-value text-blue-400">{{ $totais['total_visitas'] }}</div>
                            <div class="stat-helper">Agendadas ou em rota</div>
                        </div>
                        <div class="stat-icon" style="background: rgba(59,130,246,.12); color: var(--color-info);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="stat-label text-sky-400">Reposições</span>
                            <div class="stat-value text-sky-400">{{ $totais['total_comodatos'] }}</div>
                            <div class="stat-helper">Vencimento de iscas</div>
                        </div>
                        <div class="stat-icon" style="background: rgba(14,165,233,.12); color: #38bdf8;">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="stat-label text-purple-400">Follow-ups</span>
                            <div class="stat-value text-purple-400">{{ $totais['total_followups'] }}</div>
                            <div class="stat-helper">Prospecção ativa</div>
                        </div>
                        <div class="stat-icon" style="background: rgba(168,85,247,.12); color: #c084fc;">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Visualização em Lista --}}
        <div id="view-lista" class="{{ $modo === 'calendario' ? 'hidden' : '' }}">
            @if ($agendaOrdenada->isEmpty())
                <div class="card empty-state p-12">
                    <div class="empty-state-icon">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="empty-state-title">Nenhum compromisso ou alerta encontrado</h3>
                    <p class="empty-state-desc">Selecione outro mês ou altere o filtro de categoria acima.</p>
                </div>
            @else
                @php
                    $gruposPorData = $agendaOrdenada->groupBy(function ($item) {
                        return $item['data_hora']->format('Y-m-d');
                    });
                @endphp

                <div class="space-y-6">
                    @foreach ($gruposPorData as $dataStr => $itensDoDia)
                        @php
                            $dataObj = \Carbon\Carbon::parse($dataStr);
                            $ehHoje = $dataObj->isToday();
                            $ehPassado = $dataObj->isPast() && !$ehHoje;
                        @endphp

                        <div class="card overflow-hidden">
                            {{-- Date Header --}}
                            <div class="px-6 py-3.5 flex items-center justify-between"
                                style="background: {{ $ehHoje ? 'rgba(16,185,129,0.1)' : 'var(--color-surface-900)' }}; border-bottom: 1px solid var(--color-surface-800);">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-bold {{ $ehHoje ? 'text-emerald-400' : 'text-white' }}">
                                        {{ ucfirst($dataObj->translatedFormat('l, d \d\e F \d\e Y')) }}
                                    </span>
                                    @if ($ehHoje)
                                        <span class="badge badge-success font-bold">
                                            Hoje
                                        </span>
                                    @elseif($ehPassado)
                                        <span class="badge badge-neutral">
                                            Passado
                                        </span>
                                    @endif
                                </div>
                                <span class="text-xs" style="color: var(--color-surface-400);">
                                    {{ $itensDoDia->count() }} {{ $itensDoDia->count() === 1 ? 'item' : 'itens' }}
                                </span>
                            </div>

                            {{-- Items List --}}
                            <div class="divide-y" style="border-color: var(--color-border);">
                                @foreach ($itensDoDia as $item)
                                    <div class="p-4 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                                        style="border-color: var(--color-border);"
                                        onmouseover="this.style.background='var(--color-surface-subtle)'"
                                        onmouseout="this.style.background='transparent'">
                                        <div class="flex items-start gap-3">
                                            {{-- Type Icon --}}
                                            <div
                                                class="mt-0.5 w-9 h-9 rounded-lg flex items-center justify-center shrink-0
                                        @if ($item['tipo_item'] === 'visita') badge-info
                                        @elseif($item['tipo_item'] === 'comodato')
                                            badge-cyan
                                        @else
                                            badge-purple @endif
                                    ">
                                                @if ($item['tipo_item'] === 'visita')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                    </svg>
                                                @elseif($item['tipo_item'] === 'comodato')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                    </svg>
                                                @endif
                                            </div>

                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="text-xs font-bold uppercase tracking-wider
                                                @if ($item['tipo_item'] === 'visita') {{ ($item['eh_retorno_preventivo'] ?? false) ? 'text-teal-400' : 'text-blue-400' }}
                                                @elseif($item['tipo_item'] === 'comodato') text-sky-400
                                                @else text-purple-400 @endif
                                            ">
                                                        @if ($item['tipo_item'] === 'visita')
                                                            @if ($item['eh_retorno_preventivo'] ?? false)
                                                                Retorno Preventivo (Reforço)
                                                            @elseif ($item['eh_recorrente_projetado'] ?? false)
                                                                Visita Recorrente (Projetada)
                                                            @else
                                                                Ordem de Serviço
                                                            @endif
                                                        @elseif($item['tipo_item'] === 'comodato')
                                                            Reposição de Comodato
                                                        @else
                                                            Follow-up CRM
                                                        @endif
                                                    </span>
                                                    <span class="text-xs " style="color: var(--color-surface-400);">
                                                        {{ $item['data_hora']->format('H:i') }}
                                                    </span>
                                                </div>

                                                <div class="text-sm font-semibold text-white mt-0.5">
                                                    {{ $item['titulo'] }}
                                                </div>

                                                <div class="text-xs mt-0.5" style="color: var(--color-surface-400);">
                                                    {{ $item['subtitulo'] }} • <span
                                                        style="color: var(--color-surface-300);">{{ $item['detalhes'] }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 sm:self-center ml-12 sm:ml-0">
                                            @if ($item['eh_retorno_preventivo'] ?? false)
                                                <span class="badge badge-cyan font-medium flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                    Retorno Previsto
                                                </span>
                                            @elseif ($item['eh_recorrente_projetado'] ?? false)
                                                <span class="badge badge-purple font-medium flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                    {{ $item['status'] }}
                                                </span>
                                            @elseif ($item['status'] === 'Atrasado')
                                                <span class="badge badge-danger">
                                                    {{ $item['status'] }}
                                                </span>
                                            @elseif ($item['status'] === 'Concluída')
                                                <span class="badge badge-success">
                                                    {{ $item['status'] }}
                                                </span>
                                            @else
                                                <span class="badge badge-neutral">
                                                    {{ $item['status'] }}
                                                </span>
                                            @endif

                                            @if (($item['eh_recorrente_projetado'] ?? false) && $item['tipo_item'] === 'visita')
                                                @permissao('visitas.criar')
                                                    <a href="{{ $item['url'] }}" class="btn btn-primary btn-sm text-xs">
                                                        Agendar OS
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M9 5l7 7-7 7" />
                                                        </svg>
                                                    </a>
                                                @endpermissao
                                            @elseif ($item['tipo_item'] === 'comodato')
                                                @if (!empty($item['url_os']))
                                                    @permissao('visitas.criar')
                                                        <a href="{{ $item['url_os'] }}" class="btn btn-primary btn-sm text-xs" title="Criar Ordem de Serviço vinculada a esta reposição">
                                                            Agendar OS
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M9 5l7 7-7 7" />
                                                            </svg>
                                                        </a>
                                                    @endpermissao
                                                @endif
                                                <a href="{{ $item['url'] }}" class="btn btn-secondary btn-sm text-xs" title="Ver detalhes do comodato">
                                                    Abrir
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            @else
                                                <a href="{{ $item['url'] }}" class="btn btn-secondary btn-sm text-xs">
                                                    Abrir
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            @endif

                                            @if (auth()->user() &&
                                                    (auth()->user()->temPermissao('agendamentos.deletar') ||
                                                        ($item['tipo_item'] === 'visita' &&
                                                            $item['model']->tecnico_id === auth()->user()->tecnico_id &&
                                                            auth()->user()->temPermissao('visitas.deletar'))))
                                                @if ($item['eh_retorno_preventivo'] ?? false)
                                                    <form
                                                        id="delete-agenda-{{ $loop->parent->index }}-{{ $loop->index }}"
                                                        action="{{ route('agendamentos.destroy', ['tipo' => 'retorno_preventivo', 'id' => $item['model']->id]) }}"
                                                        method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>
                                                    <button type="button"
                                                        onclick="confirmDelete('delete-agenda-{{ $loop->parent->index }}-{{ $loop->index }}')"
                                                        class="btn btn-danger-soft btn-sm p-1.5"
                                                        title="Remover retorno preventivo da agenda">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                @elseif (!($item['eh_recorrente_projetado'] ?? false) || $item['tipo_item'] === 'comodato')
                                                    <form
                                                        id="delete-agenda-{{ $loop->parent->index }}-{{ $loop->index }}"
                                                        action="{{ route('agendamentos.destroy', ['tipo' => $item['tipo_item'], 'id' => $item['model']->id]) }}"
                                                        method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>
                                                    <button type="button"
                                                        onclick="confirmDelete('delete-agenda-{{ $loop->parent->index }}-{{ $loop->index }}')"
                                                        class="btn btn-danger-soft btn-sm p-1.5"
                                                        title="Remover evento da agenda">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Visualização em Calendário (Grade Mensal) --}}
        <div id="view-calendario" class="{{ $modo === 'lista' ? 'hidden' : '' }}">
            <div class="card overflow-hidden">
                {{-- Navegação / Info do Mês & Legenda --}}
                <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b"
                    style="border-color: var(--color-surface-800); background: var(--color-surface-900);">
                    <div class="flex items-center gap-3">
                        <span class="text-base font-bold text-slate-900 dark:text-white capitalize">
                            {{ \Carbon\Carbon::parse($mes . '-01')->translatedFormat('F \d\e Y') }}
                        </span>
                        <span class="badge badge-neutral text-xs">
                            {{ $totais['total_geral'] }} {{ $totais['total_geral'] === 1 ? 'evento' : 'eventos' }} no mês
                        </span>
                    </div>

                    {{-- Legenda das Cores --}}
                    <div class="flex items-center gap-4 text-xs flex-wrap font-medium text-slate-600 dark:text-surface-400">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600 dark:bg-blue-500 shadow-2xs"></span>
                            <span>Visita / OS</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 dark:bg-emerald-500 shadow-2xs"></span>
                            <span>Retorno Preventivo</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-cyan-600 dark:bg-cyan-500 shadow-2xs"></span>
                            <span>Comodato</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-600 dark:bg-purple-500 shadow-2xs"></span>
                            <span>Follow-up</span>
                        </div>
                    </div>
                </div>

                {{-- Grade do Calendário (7 Colunas) --}}
                <div class="overflow-x-auto">
                    <div class="min-w-[760px]">
                        {{-- Cabeçalho dos Dias da Semana --}}
                        <div class="grid grid-cols-7 text-center text-xs font-bold uppercase tracking-wider py-2.5 border-b text-slate-600 dark:text-surface-400"
                            style="background: var(--color-surface-950); border-color: var(--color-surface-800);">
                            <div>Dom</div>
                            <div>Seg</div>
                            <div>Ter</div>
                            <div>Qua</div>
                            <div>Qui</div>
                            <div>Sex</div>
                            <div>Sáb</div>
                        </div>

                        {{-- Células dos Dias --}}
                        <div class="grid grid-cols-7 divide-x divide-y" style="border-color: var(--color-surface-800);">
                            @foreach ($gradeCalendario as $celula)
                                @php
                                    $ehMesAtual = $celula['eh_mes_atual'];
                                    $ehHoje = $celula['eh_hoje'];
                                    $itens = $celula['itens'];
                                    $totalItens = $celula['total_itens'];
                                @endphp
                                <div class="min-h-[120px] p-2 flex flex-col justify-between transition relative
                                    {{ !$ehMesAtual ? 'opacity-40 bg-surface-950/30' : '' }}
                                    {{ $ehHoje ? 'bg-emerald-500/5 ring-1 ring-inset ring-emerald-500/30' : '' }}"
                                    style="border-color: var(--color-surface-800);"
                                    data-data-str="{{ $celula['data_str'] }}">

                                    {{-- Cabeçalho da Célula (Número do Dia + Indicador) --}}
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span
                                            class="text-xs inline-flex items-center justify-center w-6 h-6 rounded-full
                                            {{ $ehHoje ? 'bg-emerald-600 text-white font-bold shadow-sm' : ($ehMesAtual ? 'font-bold text-slate-800 dark:text-surface-100' : 'text-slate-400 dark:text-surface-500') }}">
                                            {{ $celula['dia'] }}
                                        </span>

                                        @if ($totalItens > 0)
                                            <button type="button"
                                                onclick="abrirModalDia('{{ $celula['data_str'] }}', '{{ ucfirst($celula['data']->translatedFormat('l, d \d\e F \d\e Y')) }}')"
                                                class="text-[10px] font-semibold px-1.5 py-0.5 rounded transition bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-surface-800 dark:hover:bg-surface-700 dark:text-surface-300"
                                                title="Ver todos os {{ $totalItens }} eventos deste dia">
                                                {{ $totalItens }} {{ $totalItens === 1 ? 'item' : 'itens' }}
                                            </button>
                                        @endif
                                    </div>

                                    {{-- Chips de Eventos (Máximo 3 visíveis) --}}
                                    <div class="space-y-1.5 flex-1">
                                        @foreach ($itens->take(3) as $ev)
                                            @php
                                                $chipClass = match (true) {
                                                    $ev['eh_retorno_preventivo'] ?? false
                                                        => 'bg-emerald-50 text-emerald-950 border-emerald-200/90 border-l-[3px] border-l-emerald-600 hover:bg-emerald-100 hover:border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-100 dark:border-emerald-800/80 dark:border-l-emerald-500 dark:hover:bg-emerald-900/60',
                                                    $ev['tipo_item'] === 'visita'
                                                        => 'bg-blue-50 text-blue-950 border-blue-200/90 border-l-[3px] border-l-blue-600 hover:bg-blue-100 hover:border-blue-300 dark:bg-blue-950/50 dark:text-blue-100 dark:border-blue-800/80 dark:border-l-blue-500 dark:hover:bg-blue-900/60',
                                                    $ev['tipo_item'] === 'comodato'
                                                        => 'bg-cyan-50 text-cyan-950 border-cyan-200/90 border-l-[3px] border-l-cyan-600 hover:bg-cyan-100 hover:border-cyan-300 dark:bg-cyan-950/50 dark:text-cyan-100 dark:border-cyan-800/80 dark:border-l-cyan-500 dark:hover:bg-cyan-900/60',
                                                    $ev['tipo_item'] === 'followup'
                                                        => 'bg-purple-50 text-purple-950 border-purple-200/90 border-l-[3px] border-l-purple-600 hover:bg-purple-100 hover:border-purple-300 dark:bg-purple-950/50 dark:text-purple-100 dark:border-purple-800/80 dark:border-l-purple-500 dark:hover:bg-purple-900/60',
                                                    default => 'bg-slate-50 text-slate-800 border-slate-200 border-l-[3px] border-l-slate-500 hover:bg-slate-100 dark:bg-surface-800 dark:text-surface-200 dark:border-surface-700 dark:border-l-surface-400',
                                                };
                                                $hora = $ev['data_hora']->format('H:i');
                                            @endphp

                                            @php
                                                $urlEv = $ev['url'] ?? null;
                                            @endphp
                                            <div class="group relative">
                                                @if ($urlEv)
                                                    <a href="{{ $urlEv }}"
                                                        class="flex items-center gap-1.5 text-xs px-2 py-1 rounded border shadow-2xs transition truncate {{ $chipClass }}"
                                                        title="{{ $ev['titulo'] }} ({{ $hora }})">
                                                        <span class="font-bold tabular-nums shrink-0 text-[11px] opacity-90">{{ $hora }}</span>
                                                        <span class="truncate font-medium">{{ $ev['titulo'] }}</span>
                                                    </a>
                                                @else
                                                    <div class="flex items-center gap-1.5 text-xs px-2 py-1 rounded border shadow-2xs transition truncate cursor-pointer {{ $chipClass }}"
                                                        onclick="abrirModalDia('{{ $celula['data_str'] }}', '{{ ucfirst($celula['data']->translatedFormat('l, d \d\e F \d\e Y')) }}')"
                                                        title="{{ $ev['titulo'] }} ({{ $hora }})">
                                                        <span class="font-bold tabular-nums shrink-0 text-[11px] opacity-90">{{ $hora }}</span>
                                                        <span class="truncate font-medium">{{ $ev['titulo'] }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach

                                        @if ($totalItens > 3)
                                            <button type="button"
                                                onclick="abrirModalDia('{{ $celula['data_str'] }}', '{{ ucfirst($celula['data']->translatedFormat('l, d \d\e F \d\e Y')) }}')"
                                                class="w-full text-center text-xs font-semibold py-1 px-1 rounded transition bg-slate-100/80 hover:bg-slate-200 text-blue-700 dark:bg-surface-800 dark:hover:bg-surface-700 dark:text-primary-300">
                                                +{{ $totalItens - 3 }} mais...
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Templates dos eventos por dia para o Modal do Calendário --}}
    @foreach ($gradeCalendario as $celula)
        @if ($celula['total_itens'] > 0)
            <template id="template-dia-{{ $celula['data_str'] }}">
                <div class="space-y-3">
                    @foreach ($celula['itens'] as $item)
                        @php
                            $statusVisita = $item['model']->status ?? null;
                            $badgeClass = match ($statusVisita) {
                                'concluida' => 'badge-success',
                                'em_rota' => 'badge-warning',
                                'agendada' => 'badge-info',
                                'cancelada' => 'badge-neutral',
                                default => $item['tipo_item'] === 'comodato' ? 'badge-cyan' : 'badge-purple',
                            };
                            $urlModal = $item['url'] ?? null;
                        @endphp
                        <div class="p-3.5 rounded-lg border transition flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/80 border-slate-200/80 dark:bg-surface-800 dark:border-surface-700">
                            <div class="flex items-start gap-3 min-w-0">
                                <div
                                    class="mt-0.5 w-8 h-8 rounded-lg flex items-center justify-center shrink-0
                                    @if ($item['tipo_item'] === 'visita') badge-info
                                    @elseif($item['tipo_item'] === 'comodato') badge-cyan
                                    @else badge-purple @endif">
                                    @if ($item['tipo_item'] === 'visita')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    @elseif($item['tipo_item'] === 'comodato')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span
                                            class="text-xs font-bold tracking-wide uppercase
                                            @if ($item['tipo_item'] === 'visita')
                                                {{ ($item['eh_retorno_preventivo'] ?? false) ? 'text-emerald-700 dark:text-emerald-400' : 'text-blue-700 dark:text-blue-400' }}
                                            @elseif($item['tipo_item'] === 'comodato') text-cyan-700 dark:text-cyan-400
                                            @else text-purple-700 dark:text-purple-400 @endif">
                                            @if ($item['tipo_item'] === 'visita')
                                                {{ ($item['eh_retorno_preventivo'] ?? false) ? 'RETORNO PREVENTIVO (REFORÇO)' : 'ORDEM DE SERVIÇO' }}
                                            @elseif($item['tipo_item'] === 'comodato')
                                                REPOSIÇÃO DE COMODATO
                                            @else
                                                FOLLOW-UP COMERCIAL
                                            @endif
                                        </span>
                                        <span class="text-xs text-slate-400 dark:text-surface-400">•</span>
                                        <span class="text-xs font-bold text-slate-900 dark:text-white">
                                            {{ $item['data_hora']->format('H:i') }}
                                        </span>
                                    </div>
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white mt-0.5 truncate">
                                        {{ $item['titulo'] }}
                                    </h4>
                                    @if (!empty($item['subtitulo']))
                                        <p class="text-xs mt-0.5 text-slate-600 dark:text-surface-400">
                                            {{ $item['subtitulo'] }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                                @if (!empty($item['status']))
                                    <span class="badge {{ $badgeClass }}">
                                        {{ $item['status'] }}
                                    </span>
                                @endif

                                @if ($item['tipo_item'] === 'comodato' && !empty($item['url_os']))
                                    @permissao('visitas.criar')
                                        <a href="{{ $item['url_os'] }}" class="btn btn-primary btn-sm text-xs">
                                            Agendar OS →
                                        </a>
                                    @endpermissao
                                    <a href="{{ $urlModal }}" class="btn btn-secondary btn-sm text-xs">
                                        Ver Comodato →
                                    </a>
                                @elseif ($urlModal)
                                    @if (($item['eh_recorrente_projetado'] ?? false) && $item['tipo_item'] === 'visita')
                                        @permissao('visitas.criar')
                                            <a href="{{ $urlModal }}" class="btn btn-secondary btn-sm text-xs">
                                                Agendar OS →
                                            </a>
                                        @endpermissao
                                    @else
                                        <a href="{{ $urlModal }}" class="btn btn-secondary btn-sm text-xs">
                                            Abrir →
                                        </a>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </template>
        @endif
    @endforeach

    {{-- Modal de Detalhes do Dia --}}
    <div id="modal-dia-eventos" class="modal-backdrop hidden fixed inset-0 z-50 flex items-center justify-center p-4"
        style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
        <div class="card max-w-2xl w-full p-6 space-y-4 max-h-[85vh] flex flex-col"
            style="background: var(--color-surface); border: 1px solid var(--color-border);">
            <div class="flex items-center justify-between pb-3 border-b" style="border-color: var(--color-border);">
                <div>
                    <h3 id="modal-dia-titulo" class="text-base font-bold text-slate-900 dark:text-white">Eventos do Dia</h3>
                    <p class="text-xs mt-0.5 text-slate-500 dark:text-surface-400">Compromissos agendados nesta data
                    </p>
                </div>
                <button type="button" onclick="fecharModalDia()"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:text-surface-400 dark:hover:text-white dark:hover:bg-surface-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div id="modal-dia-conteudo" class="overflow-y-auto flex-1 pr-1">
                {{-- Injetado dinamicamente via JS --}}
            </div>

            <div class="pt-3 border-t flex justify-end" style="border-color: var(--color-surface-800);">
                <button type="button" onclick="fecharModalDia()" class="btn btn-secondary btn-sm">
                    Fechar
                </button>
            </div>
        </div>
    </div>

    <script>
        function alternarModoAgenda(modo) {
            const viewLista = document.getElementById('view-lista');
            const viewCalendario = document.getElementById('view-calendario');
            const btnLista = document.getElementById('btn-modo-lista');
            const btnCalendario = document.getElementById('btn-modo-calendario');
            const inputModo = document.getElementById('form-modo-input');

            if (modo === 'calendario') {
                viewLista?.classList.add('hidden');
                viewCalendario?.classList.remove('hidden');
                btnLista?.classList.remove('active');
                btnCalendario?.classList.add('active');
            } else {
                viewCalendario?.classList.add('hidden');
                viewLista?.classList.remove('hidden');
                btnCalendario?.classList.remove('active');
                btnLista?.classList.add('active');
            }

            if (inputModo) inputModo.value = modo;
            localStorage.setItem('alaris_agenda_modo', modo);

            // Atualiza URL sem recarregar a página
            const url = new URL(window.location.href);
            url.searchParams.set('modo', modo);
            window.history.replaceState({}, '', url.toString());

            // Atualiza os links dos filtros mantendo o modo ativo
            document.querySelectorAll('.tab-pills a').forEach(a => {
                try {
                    const linkUrl = new URL(a.href);
                    linkUrl.searchParams.set('modo', modo);
                    a.href = linkUrl.toString();
                } catch (e) {}
            });
        }

        function abrirModalDia(dataStr, tituloFormatado) {
            const template = document.getElementById('template-dia-' + dataStr);
            const modal = document.getElementById('modal-dia-eventos');
            const tituloEl = document.getElementById('modal-dia-titulo');
            const conteudoEl = document.getElementById('modal-dia-conteudo');

            if (!modal || !conteudoEl) return;

            if (tituloEl) {
                tituloEl.textContent = tituloFormatado;
            }

            if (template) {
                conteudoEl.innerHTML = template.innerHTML;
            } else {
                conteudoEl.innerHTML =
                    '<p class="text-sm text-surface-400 py-4 text-center">Nenhum evento agendado para este dia.</p>';
            }

            modal.classList.remove('hidden');
        }

        function fecharModalDia() {
            const modal = document.getElementById('modal-dia-eventos');
            if (modal) modal.classList.add('hidden');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') fecharModalDia();
        });

        // Restaurar preferência do localStorage se a URL não tiver parâmetro explícito
        document.addEventListener('DOMContentLoaded', function() {
            const url = new URL(window.location.href);
            if (!url.searchParams.has('modo')) {
                const salvo = localStorage.getItem('alaris_agenda_modo');
                if (salvo && (salvo === 'calendario' || salvo === 'lista')) {
                    alternarModoAgenda(salvo);
                }
            }
        });
    </script>
@endsection
