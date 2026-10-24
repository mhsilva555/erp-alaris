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
                    <div>
                        <input type="month" name="mes" value="{{ $mes }}" class="form-input text-sm py-1.5"
                            onchange="this.form.submit()">
                    </div>

                    <div class="tab-pills">
                        <a href="{{ route('agendamentos.index', ['mes' => $mes, 'tipo' => 'todos']) }}"
                            class="tab-pill {{ $tipoFiltro === 'todos' ? 'active' : '' }}">
                            Todos ({{ $totais['total_geral'] }})
                        </a>
                        <a href="{{ route('agendamentos.index', ['mes' => $mes, 'tipo' => 'visitas']) }}"
                            class="tab-pill {{ $tipoFiltro === 'visitas' ? 'active' : '' }}">
                            Visitas ({{ $totais['total_visitas'] }})
                        </a>
                        <a href="{{ route('agendamentos.index', ['mes' => $mes, 'tipo' => 'comodatos']) }}"
                            class="tab-pill {{ $tipoFiltro === 'comodatos' ? 'active' : '' }}">
                            Comodatos ({{ $totais['total_comodatos'] }})
                        </a>
                        <a href="{{ route('agendamentos.index', ['mes' => $mes, 'tipo' => 'followups']) }}"
                            class="tab-pill {{ $tipoFiltro === 'followups' ? 'active' : '' }}">
                            Follow-ups ({{ $totais['total_followups'] }})
                        </a>
                    </div>
                </form>
            </div>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
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

        {{-- Timeline de Eventos Agrupados por Dia --}}
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
                                    <span class="badge badge-success preserve-white font-bold">
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
                        <div class="divide-y" style="border-color: var(--color-surface-800);">
                            @foreach ($itensDoDia as $item)
                                <div class="p-4 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                                    onmouseover="this.style.background='var(--color-surface-800)'"
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
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                            @elseif($item['tipo_item'] === 'comodato')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                </svg>
                                            @endif
                                        </div>

                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="text-xs font-bold uppercase tracking-wider
                                                @if ($item['tipo_item'] === 'visita') text-blue-400
                                                @elseif($item['tipo_item'] === 'comodato') text-sky-400
                                                @else text-purple-400 @endif
                                            ">
                                                    {{ $item['tipo_item'] === 'visita' ? 'Ordem de Serviço' : ($item['tipo_item'] === 'comodato' ? 'Reposição Comodato' : 'Follow-up CRM') }}
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
                                        <span class="badge badge-neutral">
                                            {{ $item['status'] }}
                                        </span>
                                        <a href="{{ $item['url'] }}" class="btn btn-secondary btn-sm text-xs">
                                            Abrir
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
@endsection
