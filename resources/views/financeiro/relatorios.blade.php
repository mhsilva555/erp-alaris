@extends('layouts.app')

@section('title', 'Relatório de Lucro Bruto & Rentabilidade')

@section('content')
    <style>
        @media print {
            aside, header, #sidebar-toggle, #sidebar-overlay, .tab-pills, .btn, .no-print {
                display: none !important;
            }
            body, .min-h-full, .lg\:ml-64 {
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                color: #111827 !important;
            }
            .card {
                border: 1px solid #e5e7eb !important;
                background: #ffffff !important;
                box-shadow: none !important;
                color: #111827 !important;
            }
            .stat-card {
                background: #f9fafb !important;
                border: 1px solid #e5e7eb !important;
                box-shadow: none !important;
            }
            .print-watermark {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 65%;
                max-width: 500px;
                opacity: 0.055;
                pointer-events: none;
                z-index: 0;
                display: block !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .print-header {
                display: flex !important;
                align-items: center;
                justify-content: space-between;
                border-bottom: 2px solid #0284c7;
                padding-bottom: 14px;
                margin-bottom: 20px;
            }
        }
        .print-watermark, .print-header {
            display: none;
        }
    </style>

    {{-- Marca d'água oficial Alaris Service na Impressão --}}
    <div class="print-watermark" aria-hidden="true">
        <img src="{{ asset('images/logo.png') }}" alt="" class="w-full h-auto object-contain">
    </div>

    {{-- Cabeçalho exclusivo para Impressão / PDF --}}
    <div class="print-header">
        <img src="{{ asset('images/logo.png') }}" alt="Alaris Service" style="height: 44px; width: auto; object-fit: contain;">
        <div style="text-align: right; font-size: 11px; color: #6b7280;">
            <strong style="font-size: 13px; color: #0284c7;">Relatório de Rentabilidade & Lucro Bruto</strong><br>
            Emissão: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <div class="space-y-6">

        {{-- Header & Period Selector --}}
        <div class="card p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Relatório de Lucro Bruto & Formação de Preço
                    </h2>
                    <p class="text-sm mt-1" style="color: var(--color-surface-400);">
                        Apuração de receitas, custos diretos de defensivos, mão de obra, deslocamento e impostos.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <form method="GET" action="{{ route('financeiro.relatorio') }}" class="flex flex-wrap items-center gap-2">
                        <input type="hidden" name="periodo" value="{{ $periodo }}">
                        <input type="hidden" name="status" value="{{ $statusFiltro }}">

                        <x-month-picker :value="$mes" />
                    </form>

                    <div class="tab-pills">
                        <a href="{{ route('financeiro.relatorio', ['mes' => $mes, 'periodo' => 'mensal', 'status' => $statusFiltro]) }}"
                            class="tab-pill {{ $periodo === 'mensal' ? 'active' : '' }}">
                            Mensal
                        </a>
                        <a href="{{ route('financeiro.relatorio', ['mes' => $mes, 'periodo' => 'semanal', 'status' => $statusFiltro]) }}"
                            class="tab-pill {{ $periodo === 'semanal' ? 'active' : '' }}">
                            Semanal
                        </a>
                        <a href="{{ route('financeiro.relatorio', ['mes' => $mes, 'periodo' => 'anual', 'status' => $statusFiltro]) }}"
                            class="tab-pill {{ $periodo === 'anual' ? 'active' : '' }}">
                            Anual
                        </a>
                    </div>

                    <div class="tab-pills">
                        <a href="{{ route('financeiro.relatorio', ['mes' => $mes, 'periodo' => $periodo, 'status' => 'todas']) }}"
                            class="tab-pill {{ $statusFiltro === 'todas' ? 'active' : '' }}" title="Considera OSs abertas, em execução e concluídas">
                            Todas as OSs
                        </a>
                        <a href="{{ route('financeiro.relatorio', ['mes' => $mes, 'periodo' => $periodo, 'status' => 'concluida']) }}"
                            class="tab-pill {{ $statusFiltro === 'concluida' ? 'active' : '' }}" title="Considera apenas OSs finalizadas">
                            Concluídas
                        </a>
                    </div>

                    @permissao('financeiro.ver')
                        <a href="{{ route('financeiro.fluxo', ['mes' => $mes]) }}" class="btn btn-secondary btn-sm flex items-center gap-1.5" title="Ver lançamentos no Fluxo de Caixa deste mês">
                            <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            <span>Fluxo de Caixa</span>
                        </a>
                    @endpermissao

                    @permissao('financeiro.exportar_pdf')
                        <a href="{{ route('financeiro.relatorio.pdf', ['mes' => $mes, 'periodo' => $periodo, 'status' => $statusFiltro]) }}" target="_blank" class="btn btn-secondary btn-sm flex items-center gap-1.5" title="Exportar Relatório em PDF gerado no Backend">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Exportar PDF</span>
                        </a>
                    @endpermissao
                </div>
            </div>

            <div class="p-3.5 rounded-lg flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs"
                style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                <span style="color: var(--color-surface-300);">Período analisado: <strong
                        class="text-white">{{ $dataInicio->format('d/m/Y') }}</strong> até <strong
                        class="text-white">{{ $dataFim->format('d/m/Y') }}</strong> ({{ $tituloPeriodo }})</span>
                <div class="flex items-center gap-2">
                    <span class="badge badge-info">{{ $statusFiltro === 'concluida' ? 'Apenas Concluídas' : 'Todas as OSs (Abertas + Concluídas)' }}</span>
                    <span class="badge badge-success font-semibold">{{ $visitas->count() }} OS {{ $visitas->count() === 1 ? 'apurada' : 'apuradas' }}</span>
                </div>
            </div>

            {{-- Executive KPI Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-5">
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="stat-label text-emerald-400">Faturamento Bruto</span>
                            <div class="stat-value text-emerald-400">
                                R$ {{ number_format($totalFaturado, 2, ',', '.') }}
                            </div>
                            <div class="stat-helper">Total cobrado em OS concluídas</div>
                        </div>
                        <div class="stat-icon" style="background: rgba(16,185,129,.12); color: var(--color-success);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="stat-label text-rose-400">Custo Direto Operacional</span>
                            <div class="stat-value text-rose-400">
                                R$ {{ number_format($totalCustoOperacional, 2, ',', '.') }}
                            </div>
                            <div class="stat-helper">Insumos + Diárias + Desloc. + Extras + NF</div>
                        </div>
                        <div class="stat-icon" style="background: rgba(239,68,68,.12); color: var(--color-danger);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="stat-label">Lucro Bruto Operacional</span>
                            <div class="stat-value {{ $lucroBrutoTotal >= 0 ? 'text-white' : 'text-rose-400' }}">
                                R$ {{ number_format($lucroBrutoTotal, 2, ',', '.') }}
                            </div>
                            <div class="stat-helper text-emerald-400 font-bold">Margem média:
                                {{ number_format($margemMedia, 1) }}%</div>
                        </div>
                        <div class="stat-icon" style="background: rgba(43,143,255,.12); color: var(--color-primary-400);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="stat-label text-blue-400">Resultado Líquido Estimado</span>
                            <div class="stat-value {{ $lucroLiquidoEstimado >= 0 ? 'text-blue-400' : 'text-rose-400' }}">
                                R$ {{ number_format($lucroLiquidoEstimado, 2, ',', '.') }}
                            </div>
                            <div class="stat-helper">Abatendo R$ {{ number_format($despesasGerais, 2, ',', '.') }} gerais
                            </div>
                        </div>
                        <div class="stat-icon" style="background: rgba(59,130,246,.12); color: var(--color-info);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Breakdown of direct costs --}}
            <div class="mt-5 pt-4 grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs"
                style="border-top: 1px solid var(--color-surface-800);">
                <div class="p-3 rounded-lg"
                    style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                    <span class="block" style="color: var(--color-surface-400);">Custo de Defensivos:</span>
                    <strong class="text-white text-sm">R$ {{ number_format($totalCustoProdutos, 2, ',', '.') }}</strong>
                </div>
                <div class="p-3 rounded-lg"
                    style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                    <span class="block" style="color: var(--color-surface-400);">Diárias Técnicas:</span>
                    <strong class="text-white text-sm">R$ {{ number_format($totalCustoMaoDeObra, 2, ',', '.') }}</strong>
                </div>
                <div class="p-3 rounded-lg"
                    style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                    <span class="block" style="color: var(--color-surface-400);">Deslocamento:</span>
                    <strong class="text-white text-sm">R$
                        {{ number_format($totalCustoDeslocamento, 2, ',', '.') }}</strong>
                </div>
                <div class="p-3 rounded-lg"
                    style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                    <span class="block" style="color: var(--color-surface-400);">Gastos Adicionais:</span>
                    <strong class="text-amber-300 text-sm">R$
                        {{ number_format($totalGastosAdicionais, 2, ',', '.') }}</strong>
                </div>
                <div class="p-3 rounded-lg"
                    style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                    <span class="block" style="color: var(--color-surface-400);">Tributos / NF:</span>
                    <strong class="text-amber-400 text-sm">R$ {{ number_format($totalImpostos, 2, ',', '.') }}</strong>
                </div>
            </div>
        </div>

        {{-- Executive Reconciliation Panel: DRE (Competência) vs Fluxo de Caixa (Realizado) --}}
        <div class="card p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3" style="border-bottom: 1px solid var(--color-surface-800);">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-lg bg-sky-500/10 text-sky-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            Auditoria & Conciliação: DRE (Competência) vs Fluxo de Caixa (Realizado)
                            <span class="badge badge-info text-[10px]">Integridade Contábil</span>
                        </h3>
                        <p class="text-xs" style="color: var(--color-surface-400);">
                            Verificação cruzada entre os valores apurados na execução das OSs e as baixas financeiras efetivas no caixa.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('financeiro.fluxo', ['mes' => $mes]) }}" class="text-xs text-sky-400 hover:text-sky-300 font-medium flex items-center gap-1">
                        Ver Extrato do Mês
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- 1. Faturamento OS DRE --}}
                <div class="p-3.5 rounded-lg" style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium" style="color: var(--color-surface-400);">Faturamento OS (DRE)</span>
                        <span class="badge badge-neutral text-[10px]">Competência</span>
                    </div>
                    <div class="text-base font-bold text-white">
                        R$ {{ number_format($totalFaturado, 2, ',', '.') }}
                    </div>
                    <div class="text-[11px] mt-1" style="color: var(--color-surface-400);">
                        Apurado em <strong class="text-white">{{ $visitas->count() }}</strong> {{ $visitas->count() === 1 ? 'OS' : 'OSs' }}
                    </div>
                </div>

                {{-- 2. Recebido no Caixa --}}
                <div class="p-3.5 rounded-lg" style="background: var(--color-surface-950); border: 1px solid rgba(16, 185, 129, 0.25);">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium text-emerald-400">Recebido Efetivo (Caixa)</span>
                        <span class="badge badge-success text-[10px]">{{ $taxaLiquidacaoReceita }}% Liquidado</span>
                    </div>
                    <div class="text-base font-bold text-emerald-400">
                        R$ {{ number_format($faturamentoRecebido, 2, ',', '.') }}
                    </div>
                    <div class="text-[11px] mt-1 text-emerald-500/90">
                        Compensado no fluxo financeiro
                    </div>
                </div>

                {{-- 3. A Receber / Em Aberto --}}
                <div class="p-3.5 rounded-lg" style="background: var(--color-surface-950); border: 1px solid rgba(245, 158, 11, 0.25);">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium text-amber-400">Pendente no Caixa (A Receber)</span>
                        <span class="badge badge-warning text-[10px]">A Vencer</span>
                    </div>
                    <div class="text-base font-bold text-amber-400">
                        R$ {{ number_format($faturamentoPendente, 2, ',', '.') }}
                    </div>
                    <div class="text-[11px] mt-1 text-amber-500/90">
                        Aguardando baixa no contas a receber
                    </div>
                </div>

                {{-- 4. Diárias Técnicas OS --}}
                <div class="p-3.5 rounded-lg" style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium" style="color: var(--color-surface-400);">Diárias Técnicas (OS)</span>
                        <span class="badge badge-neutral text-[10px]">Mão de Obra</span>
                    </div>
                    <div class="text-base font-bold text-white">
                        R$ {{ number_format($totalCustoMaoDeObra, 2, ',', '.') }}
                    </div>
                    <div class="text-[11px] mt-1" style="color: var(--color-surface-400);">
                        Pagas: <strong class="text-emerald-400">R$ {{ number_format($diariasPagas, 2, ',', '.') }}</strong> &bull; Pendentes: <strong class="text-amber-400">R$ {{ number_format($diariasPendentes, 2, ',', '.') }}</strong>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs" style="border-top: 1px solid var(--color-surface-800); color: var(--color-surface-400);">
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span><strong>Integridade Sincronizada:</strong> Cada Ordem de Serviço concluída tem lançamentos espelhados no Caixa. O DRE reflete a competência da execução e o Fluxo reflete os saldos bancários.</span>
                </div>
                <div class="flex items-center gap-2">
                    <span>Saldo Efetivo Geral do Caixa no Mês: <strong class="{{ $saldoCaixaRealizadoPeriodo >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">R$ {{ number_format($saldoCaixaRealizadoPeriodo, 2, ',', '.') }}</strong></span>
                </div>
            </div>
        </div>

        {{-- Detailed OS Breakdown Table --}}
        <div class="card overflow-hidden">
            <div class="card-header">
                <h3 class="text-sm font-semibold text-white">Detalhamento Unitário por Ordem de Serviço</h3>
                <span class="badge badge-neutral">Exclusivo do Perfil Gestor</span>
            </div>

            @if ($visitas->isEmpty())
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h4 class="empty-state-title">Nenhuma OS faturada no período</h4>
                    <p class="empty-state-desc">Não foram encontradas ordens de serviço concluídas no intervalo de datas
                        selecionado.</p>
                </div>
            @else
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>OS / Data</th>
                                <th>Cliente</th>
                                <th>Serviço</th>
                                <th class="text-right">Faturado</th>
                                <th class="text-center">Recebimento</th>
                                <th class="text-right">Insumos</th>
                                <th class="text-right">Mão de Obra</th>
                                <th class="text-right">Desloc.</th>
                                <th class="text-right">Gastos Extras</th>
                                <th class="text-right">Imposto</th>
                                <th class="text-right">Lucro Bruto</th>
                                <th class="text-right">Margem %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($visitas as $vis)
                                @php
                                    $badgeClass = match ($vis->status) {
                                        'concluida' => 'badge-success',
                                        'em_andamento' => 'badge-warning',
                                        'agendada' => 'badge-info',
                                        'cancelada' => 'badge-neutral',
                                        default => 'badge-neutral',
                                    };
                                @endphp
                                <tr class="animate-fadeIn hover:bg-slate-50/5 dark:hover:bg-surface-800/40">
                                    <td>
                                        <div class="flex items-center gap-1.5">
                                            <a href="{{ route('visitas.show', $vis) }}"
                                                class="text-emerald-400 hover:underline font-bold text-xs block">
                                                OS #{{ $vis->id }}
                                            </a>
                                            <span class="badge {{ $badgeClass }} text-[10px] py-0 px-1.5">
                                                {{ $vis->statusLabel() }}
                                            </span>
                                        </div>
                                        <span class="text-[11px] block mt-0.5"
                                            style="color: var(--color-surface-400);">{{ $vis->data_hora->format('d/m/Y') }}</span>
                                        @if ($vis->comodato)
                                            <span class="inline-flex items-center gap-1 text-[10px] text-sky-400 font-medium mt-0.5" title="OS de Comodato">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                                Comodato
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong class="text-white text-xs block">{{ $vis->cliente->nome }}</strong>
                                        <span class="text-[11px]"
                                            style="color: var(--color-surface-400);">{{ $vis->cliente->cidade_estado }}</span>
                                    </td>
                                    <td>
                                        <span class="text-xs" style="color: var(--color-surface-300);">
                                            {{ $vis->tipoServicoLabel() }}
                                        </span>
                                    </td>
                                    <td class="text-right font-bold text-emerald-400">
                                        R$ {{ number_format($vis->valor_cobrado, 2, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        @php $statusRec = $vis->statusRecebimento(); @endphp
                                        @if ($statusRec === 'pago')
                                            <span class="badge badge-success text-[10px] py-0.5 px-2 inline-flex items-center gap-1" title="Valor já liquidado no Fluxo de Caixa">
                                                <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Recebido
                                            </span>
                                        @elseif ($statusRec === 'pendente')
                                            <span class="badge badge-warning text-[10px] py-0.5 px-2 inline-flex items-center gap-1" title="Aguardando liquidação no contas a receber">
                                                <svg class="w-3 h-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                A Receber
                                            </span>
                                        @elseif ($statusRec === 'isento')
                                            <span class="badge badge-neutral text-[10px] py-0.5 px-2">Isento</span>
                                        @else
                                            <span class="badge badge-neutral text-[10px] py-0.5 px-2">Cancelado</span>
                                        @endif
                                    </td>
                                    <td class="text-right text-xs" style="color: var(--color-surface-300);">
                                        R$ {{ number_format($vis->custo_produtos, 2, ',', '.') }}
                                    </td>
                                    <td class="text-right text-xs" style="color: var(--color-surface-300);">
                                        <strong class="text-white block">R$ {{ number_format($vis->custo_mao_de_obra, 2, ',', '.') }}</strong>
                                        <span class="text-[10px] text-slate-400 block truncate max-w-[120px] ml-auto" title="{{ $vis->tecnico?->nome ?? 'Não atribuído' }}">
                                            {{ $vis->tecnico?->nome ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="text-right text-xs" style="color: var(--color-surface-300);">
                                        R$ {{ number_format($vis->custo_deslocamento, 2, ',', '.') }}
                                    </td>
                                    <td class="text-right text-xs">
                                        @if ($vis->gastos_adicionais > 0)
                                            <strong class="text-amber-300 block">R$ {{ number_format($vis->gastos_adicionais, 2, ',', '.') }}</strong>
                                            @if ($vis->motivo_gastos_adicionais)
                                                <span class="text-[10px] text-slate-400 block truncate max-w-[110px] ml-auto" title="{{ $vis->motivo_gastos_adicionais }}">
                                                    {{ $vis->motivo_gastos_adicionais }}
                                                </span>
                                            @endif
                                        @else
                                            <span style="color: var(--color-surface-500);">—</span>
                                        @endif
                                    </td>
                                    <td class="text-right text-amber-400 text-xs">
                                        R$ {{ number_format($vis->valor_imposto, 2, ',', '.') }}
                                    </td>
                                    <td
                                        class="text-right font-bold {{ $vis->lucro_bruto >= 0 ? 'text-white' : 'text-rose-400' }}">
                                        R$ {{ number_format($vis->lucro_bruto, 2, ',', '.') }}
                                    </td>
                                    <td
                                        class="text-right font-semibold text-xs {{ $vis->margemPercentual() >= 40 ? 'text-emerald-400' : 'text-amber-400' }}">
                                        {{ number_format($vis->margemPercentual(), 1) }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-bold" style="background: var(--color-surface-950); border-top: 2px solid var(--color-surface-800);">
                                <td colspan="3" class="text-white text-xs py-3 px-3">
                                    TOTAIS ({{ $visitas->count() }} {{ $visitas->count() === 1 ? 'OS' : 'OSs' }})
                                </td>
                                <td class="text-right text-emerald-400 text-xs">
                                    R$ {{ number_format($totalFaturado, 2, ',', '.') }}
                                </td>
                                <td class="text-center text-[10px] text-slate-400">
                                    <span class="text-emerald-400 font-bold" title="Total Recebido">R$ {{ number_format($faturamentoRecebido, 2, ',', '.') }}</span>
                                    <span class="text-slate-600 block text-[9px]">A Rec: R$ {{ number_format($faturamentoPendente, 2, ',', '.') }}</span>
                                </td>
                                <td class="text-right text-xs" style="color: var(--color-surface-300);">
                                    R$ {{ number_format($totalCustoProdutos, 2, ',', '.') }}
                                </td>
                                <td class="text-right text-xs text-white">
                                    R$ {{ number_format($totalCustoMaoDeObra, 2, ',', '.') }}
                                </td>
                                <td class="text-right text-xs" style="color: var(--color-surface-300);">
                                    R$ {{ number_format($totalCustoDeslocamento, 2, ',', '.') }}
                                </td>
                                <td class="text-right text-xs text-amber-300">
                                    R$ {{ number_format($totalGastosAdicionais, 2, ',', '.') }}
                                </td>
                                <td class="text-right text-amber-400 text-xs">
                                    R$ {{ number_format($totalImpostos, 2, ',', '.') }}
                                </td>
                                <td class="text-right text-xs font-bold {{ $lucroBrutoTotal >= 0 ? 'text-white' : 'text-rose-400' }}">
                                    R$ {{ number_format($lucroBrutoTotal, 2, ',', '.') }}
                                </td>
                                <td class="text-right text-xs font-semibold text-emerald-400">
                                    {{ number_format($margemMedia, 1) }}%
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>

    </div>
@endsection
