<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $tituloPeriodo }} — Relatório Gerencial ERP Alaris</title>
    <style>
        @page {
            margin: 12mm 10mm;
            size: a4 landscape;
        }

        * {
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        body {
            font-size: 10px;
            color: #1f2937;
            background: #ffffff;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }

        /* Header */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #059669;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #065f46;
            letter-spacing: 0.5px;
        }

        .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
            margin-top: 2px;
        }

        .report-subtitle {
            font-size: 9px;
            color: #6b7280;
        }

        .meta-info {
            text-align: right;
            font-size: 9px;
            color: #4b5563;
        }

        .meta-info strong {
            color: #111827;
        }

        /* KPI Cards Grid */
        .kpi-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: separate;
            border-spacing: 6px;
        }

        .kpi-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
            width: 25%;
        }

        .kpi-card.highlight {
            background: #ecfdf5;
            border-color: #a7f3d0;
        }

        .kpi-title {
            font-size: 8px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .kpi-value {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
        }

        .kpi-value.green {
            color: #059669;
        }

        .kpi-value.red {
            color: #dc2626;
        }

        .kpi-value.blue {
            color: #2563eb;
        }

        .kpi-sub {
            font-size: 8px;
            color: #6b7280;
            margin-top: 2px;
        }

        /* Breakdown Summary Table */
        .costs-summary {
            width: 100%;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 12px;
            font-size: 9px;
        }

        .costs-summary table {
            width: 100%;
            border-collapse: collapse;
        }

        .costs-summary td {
            padding: 2px 6px;
        }

        /* Data Table */
        .table-title {
            font-size: 10px;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            border-left: 3px solid #059669;
            padding-left: 6px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            margin-bottom: 15px;
        }

        .data-table th {
            background: #f3f4f6;
            color: #374151;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.5px;
            letter-spacing: 0.5px;
            padding: 5px 6px;
            border-top: 1px solid #d1d5db;
            border-bottom: 1.5px solid #9ca3af;
            text-align: left;
        }

        .data-table td {
            padding: 5px 6px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .data-table tr:nth-child(even) td {
            background-color: #fafafa;
        }

        .data-table tfoot td {
            background: #e5e7eb;
            font-weight: bold;
            border-top: 1.5px solid #9ca3af;
            border-bottom: 1.5px solid #9ca3af;
            padding: 6px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-neutral { background: #e5e7eb; color: #374151; }
        .badge-comodato { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }

        .footer {
            margin-top: 15px;
            padding-top: 6px;
            border-top: 1px solid #e5e7eb;
            font-size: 8px;
            color: #9ca3af;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td style="vertical-align: middle;">
                <div class="company-name">ALARIS SERVICE</div>
                <div class="report-title">RELATÓRIO GERENCIAL DE LUCRO BRUTO & FORMAÇÃO DE PREÇO</div>
                <div class="report-subtitle">ERP Alaris — Controle Operacional, Insumos, Mão de Obra e Apuração de Margens</div>
            </td>
            <td class="meta-info" style="vertical-align: middle;">
                <div><strong>Período:</strong> {{ $dataInicio->format('d/m/Y') }} a {{ $dataFim->format('d/m/Y') }} ({{ $tituloPeriodo }})</div>
                <div><strong>Filtro:</strong> {{ $statusFiltro === 'concluida' ? 'Apenas Concluídas' : 'Todas as OSs (Abertas + Concluídas)' }}</div>
                <div><strong>Emissão:</strong> {{ now()->format('d/m/Y H:i') }} &bull; <strong>OSs:</strong> {{ $visitas->count() }}</div>
            </td>
        </tr>
    </table>

    {{-- KPI Cards --}}
    <table class="kpi-table">
        <tr>
            <td class="kpi-card highlight">
                <div class="kpi-title">Faturamento Bruto</div>
                <div class="kpi-value green">R$ {{ number_format($totalFaturado, 2, ',', '.') }}</div>
                <div class="kpi-sub">Total cobrado dos clientes</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-title">Custo Operacional Direto</div>
                <div class="kpi-value red">R$ {{ number_format($totalCustoOperacional, 2, ',', '.') }}</div>
                <div class="kpi-sub">Insumos + Diárias + Deslocamento + Tributos</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-title">Lucro Bruto Operacional</div>
                <div class="kpi-value {{ $lucroBrutoTotal >= 0 ? 'green' : 'red' }}">
                    R$ {{ number_format($lucroBrutoTotal, 2, ',', '.') }}
                </div>
                <div class="kpi-sub">Margem Média: <strong>{{ number_format($margemMedia, 1) }}%</strong></div>
            </td>
            <td class="kpi-card">
                <div class="kpi-title">Resultado Líquido Estimado</div>
                <div class="kpi-value blue">
                    R$ {{ number_format($lucroLiquidoEstimado, 2, ',', '.') }}
                </div>
                <div class="kpi-sub">Abatendo R$ {{ number_format($despesasGerais, 2, ',', '.') }} gerais</div>
            </td>
        </tr>
    </table>

    {{-- Breakdown dos Custos Diretos --}}
    <div class="costs-summary">
        <table>
            <tr>
                <td><strong>Defensivos Químicos:</strong> R$ {{ number_format($totalCustoProdutos, 2, ',', '.') }}</td>
                <td><strong>Diárias / Mão de Obra:</strong> R$ {{ number_format($totalCustoMaoDeObra, 2, ',', '.') }}</td>
                <td><strong>Deslocamento:</strong> R$ {{ number_format($totalCustoDeslocamento, 2, ',', '.') }}</td>
                <td><strong>Gastos Extras:</strong> R$ {{ number_format($totalGastosAdicionais, 2, ',', '.') }}</td>
                <td><strong>Tributos / NF:</strong> R$ {{ number_format($totalImpostos, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    {{-- Conciliação DRE x Fluxo de Caixa --}}
    <div style="width: 100%; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px; padding: 6px 10px; margin-bottom: 12px; font-size: 8.5px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 25%;"><strong>Faturamento OS (DRE):</strong> R$ {{ number_format($totalFaturado, 2, ',', '.') }}</td>
                <td style="width: 25%; color: #065f46;"><strong>Recebido no Caixa:</strong> R$ {{ number_format($faturamentoRecebido, 2, ',', '.') }} ({{ $taxaLiquidacaoReceita }}%)</td>
                <td style="width: 25%; color: #92400e;"><strong>A Receber (Pendente):</strong> R$ {{ number_format($faturamentoPendente, 2, ',', '.') }}</td>
                <td style="width: 25%;"><strong>Diárias Pagas / Pend.:</strong> R$ {{ number_format($diariasPagas, 2, ',', '.') }} / R$ {{ number_format($diariasPendentes, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    {{-- Tabela Detalhada de OS --}}
    <div class="table-title">Detalhamento Unitário por Ordem de Serviço</div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 44px;">OS #</th>
                <th style="width: 54px;">Data</th>
                <th>Cliente</th>
                <th style="width: 80px;">Serviço</th>
                <th style="width: 80px;">Técnico</th>
                <th style="width: 55px;" class="text-center">Status</th>
                <th style="width: 58px;" class="text-right">Faturado</th>
                <th style="width: 52px;" class="text-center">Recebimento</th>
                <th style="width: 50px;" class="text-right">Insumos</th>
                <th style="width: 54px;" class="text-right">Mão de Obra</th>
                <th style="width: 45px;" class="text-right">Desloc.</th>
                <th style="width: 48px;" class="text-right">Extras</th>
                <th style="width: 48px;" class="text-right">Imposto</th>
                <th style="width: 58px;" class="text-right">Lucro Bruto</th>
                <th style="width: 38px;" class="text-right">Margem</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($visitas as $v)
                @php
                    $badgeClass = match ($v->status) {
                        'concluida' => 'badge-success',
                        'em_andamento' => 'badge-warning',
                        'agendada' => 'badge-info',
                        'cancelada' => 'badge-neutral',
                        default => 'badge-neutral',
                    };
                @endphp
                <tr>
                    <td>
                        <strong>#{{ $v->id }}</strong>
                        @if ($v->comodato)
                            <div style="font-size: 7px; color: #0284c7;">[COMODATO]</div>
                        @endif
                    </td>
                    <td>{{ $v->data_hora ? $v->data_hora->format('d/m/Y') : '—' }}</td>
                    <td>
                        <strong>{{ $v->cliente->nome }}</strong>
                        <div style="font-size: 7.5px; color: #6b7280;">{{ $v->cliente->cidade_estado }}</div>
                    </td>
                    <td>{{ $v->tipoServicoLabel() }}</td>
                    <td>{{ $v->tecnico?->nome ?? 'Não atribuído' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $badgeClass }}">{{ $v->statusLabel() }}</span>
                    </td>
                    <td class="text-right">R$ {{ number_format($v->valor_cobrado, 2, ',', '.') }}</td>
                    <td class="text-center">
                        @php $st = $v->statusRecebimento(); @endphp
                        @if ($st === 'pago')
                            <span class="badge badge-success">Recebido</span>
                        @elseif ($st === 'pendente')
                            <span class="badge badge-warning">A Receber</span>
                        @elseif ($st === 'isento')
                            <span class="badge badge-neutral">Isento</span>
                        @else
                            <span class="badge badge-neutral">Cancelado</span>
                        @endif
                    </td>
                    <td class="text-right">R$ {{ number_format($v->custo_produtos, 2, ',', '.') }}</td>
                    <td class="text-right"><strong>R$ {{ number_format($v->custo_mao_de_obra, 2, ',', '.') }}</strong></td>
                    <td class="text-right">R$ {{ number_format($v->custo_deslocamento, 2, ',', '.') }}</td>
                    <td class="text-right">
                        @if ($v->gastos_adicionais > 0)
                            R$ {{ number_format($v->gastos_adicionais, 2, ',', '.') }}
                            @if ($v->motivo_gastos_adicionais)
                                <div style="font-size: 6.5px; color: #6b7280;">{{ $v->motivo_gastos_adicionais }}</div>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-right">R$ {{ number_format($v->valor_imposto, 2, ',', '.') }}</td>
                    <td class="text-right" style="font-weight: bold; color: {{ $v->lucro_bruto >= 0 ? '#065f46' : '#dc2626' }};">
                        R$ {{ number_format($v->lucro_bruto, 2, ',', '.') }}
                    </td>
                    <td class="text-right">{{ number_format($v->margemPercentual(), 1) }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" class="text-center" style="padding: 15px; color: #6b7280;">
                        Nenhuma Ordem de Serviço encontrada para o período selecionado.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6">TOTAIS CONSOLIDADOS ({{ $visitas->count() }} OSs)</td>
                <td class="text-right">R$ {{ number_format($totalFaturado, 2, ',', '.') }}</td>
                <td class="text-center" style="font-size: 7px; color: #065f46;">
                    R$ {{ number_format($faturamentoRecebido, 2, ',', '.') }}
                </td>
                <td class="text-right">R$ {{ number_format($totalCustoProdutos, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($totalCustoMaoDeObra, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($totalCustoDeslocamento, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($totalGastosAdicionais, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($totalImpostos, 2, ',', '.') }}</td>
                <td class="text-right" style="color: {{ $lucroBrutoTotal >= 0 ? '#065f46' : '#dc2626' }};">
                    R$ {{ number_format($lucroBrutoTotal, 2, ',', '.') }}
                </td>
                <td class="text-right">{{ number_format($margemMedia, 1) }}%</td>
            </tr>
        </tfoot>
    </table>

    {{-- Footer --}}
    <table style="width: 100%; border-top: 1px solid #e5e7eb; padding-top: 5px; font-size: 7.5px; color: #9ca3af;">
        <tr>
            <td>ERP Alaris &bull; Relatório Confidencial Administrativo de Lucro Bruto e Custos Operacionais</td>
            <td style="text-align: right;">Documento Gerado no Backend &bull; Alaris Service</td>
        </tr>
    </table>

</body>
</html>
