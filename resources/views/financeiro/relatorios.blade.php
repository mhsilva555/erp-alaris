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

                <div class="flex items-center gap-3">
                    <div class="tab-pills">
                        <a href="{{ route('financeiro.relatorio', ['periodo' => 'semanal']) }}"
                            class="tab-pill {{ $periodo === 'semanal' ? 'active' : '' }}">
                            Semanal (7 dias)
                        </a>
                        <a href="{{ route('financeiro.relatorio', ['periodo' => 'mensal']) }}"
                            class="tab-pill {{ $periodo === 'mensal' ? 'active' : '' }}">
                            Mensal (Mês Atual)
                        </a>
                    </div>
                    <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm flex items-center gap-1.5" title="Imprimir ou Salvar em PDF">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Imprimir / PDF</span>
                    </button>
                </div>
            </div>

            <div class="p-3.5 rounded-lg flex items-center justify-between text-xs"
                style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                <span style="color: var(--color-surface-300);">Período analisado: <strong
                        class="text-white">{{ $dataInicio->format('d/m/Y') }}</strong> até <strong
                        class="text-white">{{ $dataFim->format('d/m/Y') }}</strong></span>
                <span class="badge badge-success font-semibold">{{ $visitas->count() }} OS apuradas</span>
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
                            <div class="stat-helper">Insumos + Diárias + Deslocamento + NF</div>
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
            <div class="mt-5 pt-4 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs"
                style="border-top: 1px solid var(--color-surface-800);">
                <div class="p-3 rounded-lg"
                    style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                    <span class="block" style="color: var(--color-surface-400);">Custo de Defensivos:</span>
                    <strong class="text-white  text-sm">R$ {{ number_format($totalCustoProdutos, 2, ',', '.') }}</strong>
                </div>
                <div class="p-3 rounded-lg"
                    style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                    <span class="block" style="color: var(--color-surface-400);">Diárias Técnicas:</span>
                    <strong class="text-white  text-sm">R$ {{ number_format($totalCustoMaoDeObra, 2, ',', '.') }}</strong>
                </div>
                <div class="p-3 rounded-lg"
                    style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                    <span class="block" style="color: var(--color-surface-400);">Deslocamento:</span>
                    <strong class="text-white  text-sm">R$
                        {{ number_format($totalCustoDeslocamento, 2, ',', '.') }}</strong>
                </div>
                <div class="p-3 rounded-lg"
                    style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                    <span class="block" style="color: var(--color-surface-400);">Tributos / NF:</span>
                    <strong class="text-amber-400  text-sm">R$ {{ number_format($totalImpostos, 2, ',', '.') }}</strong>
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
                                <th class="text-right">Insumos</th>
                                <th class="text-right">Mão de Obra</th>
                                <th class="text-right">Desloc.</th>
                                <th class="text-right">Imposto</th>
                                <th class="text-right">Lucro Bruto</th>
                                <th class="text-right">Margem %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($visitas as $vis)
                                <tr class="animate-fadeIn">
                                    <td>
                                        <a href="{{ route('visitas.show', $vis) }}"
                                            class="text-emerald-400 hover:underline font-bold text-xs block">
                                            OS #{{ $vis->id }}
                                        </a>
                                        <span class="text-[11px] "
                                            style="color: var(--color-surface-400);">{{ $vis->data_hora->format('d/m/Y') }}</span>
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
                                    <td class="text-right  font-bold text-emerald-400">
                                        R$ {{ number_format($vis->valor_cobrado, 2, ',', '.') }}
                                    </td>
                                    <td class="text-right  text-xs" style="color: var(--color-surface-300);">
                                        R$ {{ number_format($vis->custo_produtos, 2, ',', '.') }}
                                    </td>
                                    <td class="text-right  text-xs" style="color: var(--color-surface-300);">
                                        R$ {{ number_format($vis->custo_mao_de_obra, 2, ',', '.') }}
                                    </td>
                                    <td class="text-right  text-xs" style="color: var(--color-surface-300);">
                                        R$ {{ number_format($vis->custo_deslocamento, 2, ',', '.') }}
                                    </td>
                                    <td class="text-right  text-amber-400 text-xs">
                                        R$ {{ number_format($vis->valor_imposto, 2, ',', '.') }}
                                    </td>
                                    <td
                                        class="text-right  font-bold {{ $vis->lucro_bruto >= 0 ? 'text-white' : 'text-rose-400' }}">
                                        R$ {{ number_format($vis->lucro_bruto, 2, ',', '.') }}
                                    </td>
                                    <td
                                        class="text-right font-semibold text-xs {{ $vis->margemPercentual() >= 40 ? 'text-emerald-400' : 'text-amber-400' }}">
                                        {{ number_format($vis->margemPercentual(), 1) }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
@endsection
