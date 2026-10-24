@php
    $logoPath = public_path('images/logo.png');
    $logoDataUri = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : asset('images/logo.png');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordem de Serviço #{{ $visita->id }} — Alaris Service</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <style>
        @page {
            size: A4;
            margin: 12mm 15mm;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1f2937;
            background: #f9fafb;
            margin: 0;
            padding: 20px;
            font-size: 13px;
            line-height: 1.4;
        }
        .os-container {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 32px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .watermark-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 70%;
            max-width: 500px;
            opacity: 0.055;
            pointer-events: none;
            z-index: 0;
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .watermark-logo {
            width: 100%;
            height: auto;
            object-fit: contain;
        }
        .os-content {
            position: relative;
            z-index: 1;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .logo-box {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .header-logo-img {
            height: 44px;
            width: auto;
            object-fit: contain;
        }
        .brand-sub {
            font-size: 11px;
            color: #6b7280;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .os-meta {
            text-align: right;
        }
        .os-badge {
            display: inline-block;
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 15px;
        }
        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #065f46;
            background: #f0fdf4;
            padding: 6px 10px;
            border-left: 3px solid #10b981;
            margin-top: 20px;
            margin-bottom: 12px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
        }
        .info-group {
            margin-bottom: 6px;
        }
        .info-label {
            font-size: 10px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            display: block;
        }
        .info-val {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 7px 10px;
            text-align: left;
        }
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            font-size: 11px;
            text-transform: uppercase;
        }
        .signatures {
            margin-top: 36px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            page-break-inside: avoid;
        }
        .sig-box {
            text-align: center;
        }
        .sig-line {
            border-top: 1px solid #374151;
            margin-top: 45px;
            margin-bottom: 4px;
        }
        .sig-title {
            font-size: 11px;
            color: #4b5563;
        }
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn-print {
            background: #059669;
            color: white;
            border: none;
            padding: 10px 22px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-print:hover {
            background: #047857;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .os-container {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .watermark-container {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 70%;
                max-width: 500px;
                opacity: 0.06;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">
            🖨️ Imprimir Ordem de Serviço
        </button>
    </div>

    <div class="os-container">
        {{-- Marca d'água oficial Alaris Service --}}
        <div class="watermark-container" aria-hidden="true">
            <img src="{{ $logoDataUri }}" alt="" class="watermark-logo">
        </div>

        <div class="os-content">
            {{-- Header --}}
            <div class="header">
                <div class="logo-box">
                    <img src="{{ $logoDataUri }}" alt="Alaris Service" class="header-logo-img">
                    <div style="border-left: 1px solid #e5e7eb; padding-left: 14px;">
                        <p class="brand-sub">Controle Integrado de Pragas & Higienização</p>
                    </div>
                </div>
                <div class="os-meta">
                    <div class="os-badge">OS Nº {{ str_pad($visita->id, 5, '0', STR_PAD_LEFT) }}</div>
                    <div style="font-size: 11px; color: #6b7280; margin-top: 4px;">
                        Emissão: {{ now()->format('d/m/Y H:i') }}
                    </div>
                    <div style="font-size: 11px; font-weight: 600; color: #0284c7;">
                        Status: {{ strtoupper($visita->statusLabel()) }}
                    </div>
                </div>
            </div>

        {{-- Dados do Cliente --}}
        <div class="section-title">1. Dados do Cliente / Local do Atendimento</div>
        <div class="grid-2">
            <div>
                <div class="info-group">
                    <span class="info-label">Cliente / Razão Social</span>
                    <span class="info-val">{{ $visita->cliente->nome }}</span>
                </div>
                <div class="info-group">
                    <span class="info-label">CPF / CNPJ</span>
                    <span class="info-val">{{ $visita->cliente->cpf_cnpj ?? 'Não informado' }}</span>
                </div>
                <div class="info-group">
                    <span class="info-label">Contato / Telefone</span>
                    <span class="info-val">{{ $visita->cliente->telefone ?? '—' }} ({{ $visita->cliente->email ?? 'Sem e-mail' }})</span>
                </div>
            </div>
            <div>
                <div class="info-group">
                    <span class="info-label">Endereço de Execução</span>
                    <span class="info-val">{{ $visita->cliente->endereco ?? 'Não informado' }}</span>
                </div>
                <div class="info-group">
                    <span class="info-label">Cidade / UF</span>
                    <span class="info-val">{{ $visita->cliente->cidade_estado }}</span>
                </div>
                <div class="info-group">
                    <span class="info-label">Bairro / CEP</span>
                    <span class="info-val">{{ $visita->cliente->bairro ?? '—' }} — CEP: {{ $visita->cliente->cep ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- Dados do Serviço e Execução --}}
        <div class="section-title">2. Especificações do Serviço & Equipe Técnica</div>
        <div class="grid-3">
            <div class="info-group">
                <span class="info-label">Data e Hora Programada</span>
                <span class="info-val">{{ $visita->data_hora->format('d/m/Y H:i') }}</span>
            </div>
            <div class="info-group">
                <span class="info-label">Tipo de Visita</span>
                <span class="info-val">{{ $visita->tipoVisitaLabel() }}</span>
            </div>
            <div class="info-group">
                <span class="info-label">Tipo de Serviço</span>
                <span class="info-val">{{ $visita->tipoServicoLabel() }}</span>
            </div>
        </div>

        <div class="grid-3" style="margin-top: 6px;">
            <div class="info-group">
                <span class="info-label">Técnico Responsável</span>
                <span class="info-val">{{ $visita->tecnico?->nome ?? 'Não atribuído' }}</span>
            </div>
            <div class="info-group">
                <span class="info-label">Praga-Alvo Detectada</span>
                <span class="info-val">{{ $visita->tipo_praga ?? 'Monitoramento Preventivo' }}</span>
            </div>
            <div class="info-group">
                <span class="info-label">Área Tratada</span>
                <span class="info-val">{{ $visita->area_tratada ?? 'Área total' }}</span>
            </div>
        </div>

        {{-- Produtos Utilizados na Aplicação --}}
        <div class="section-title">3. Insumos e Defensivos Químicos Aplicados</div>
        @if($visita->produtos->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>Produto / Princípio Ativo</th>
                        <th>Reg. ANVISA / MS</th>
                        <th>Grupo Químico</th>
                        <th style="text-align: right;">Dose Aplicada</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($visita->produtos as $produto)
                        <tr>
                            <td>
                                <strong>{{ $produto->nome }}</strong>
                                <div style="font-size: 10px; color: #6b7280;">{{ $produto->principio_ativo ?? 'Princípio ativo não cadastrado' }}</div>
                            </td>
                            <td>{{ $produto->registro_anvisa ?? 'Conforme bula' }}</td>
                            <td>{{ $produto->grupo_quimico ?? 'Geral' }}</td>
                            <td style="text-align: right; font-weight: 700; color: #065f46;">
                                {{ number_format($produto->pivot->quantidade, 2, ',', '.') }} {{ $produto->unidade_aplicacao }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div style="font-size: 12px; color: #6b7280; font-style: italic; padding: 6px 0;">
                Nenhum produto químico consumido ou registrado nesta OS até o momento.
            </div>
        @endif

        {{-- Equipamentos em Comodato Instalados --}}
        @if($visita->cliente->comodatos && $visita->cliente->comodatos->isNotEmpty())
            <div class="section-title">4. Dispositivos & Equipamentos em Comodato no Local</div>
            <table>
                <thead>
                    <tr>
                        <th>Código / Dispositivo</th>
                        <th>Localização Interna</th>
                        <th>Última Reposição</th>
                        <th>Próxima Reposição</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($visita->cliente->comodatos as $comodato)
                        <tr>
                            <td>
                                <strong>{{ $comodato->codigo_identificacao }}</strong> — {{ $comodato->tipo }}
                            </td>
                            <td>{{ $comodato->localizacao ?? 'Não especificado' }}</td>
                            <td>{{ $comodato->data_ultima_reposicao ? $comodato->data_ultima_reposicao->format('d/m/Y') : 'Inicial' }}</td>
                            <td style="font-weight: 600; color: {{ $comodato->estaAtrasado() ? '#dc2626' : '#059669' }}">
                                {{ $comodato->data_proxima_reposicao ? $comodato->data_proxima_reposicao->format('d/m/Y') : '—' }}
                            </td>
                            <td>{{ ucfirst($comodato->status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Observações e Recomendações --}}
        <div class="section-title">5. Orientações Técnicas, Precauções e Cuidados</div>
        <div style="background: #fdfdfd; border: 1px dashed #d1d5db; padding: 10px; border-radius: 4px; font-size: 11px; color: #374151;">
            <p style="margin: 0 0 6px 0;">
                <strong>Instruções de Segurança:</strong> Manter crianças e animais domésticos afastados das áreas tratadas durante o período de reentrada (mínimo de 4 a 6 horas para ambientes internos ou conforme instrução do aplicador). Não remover nem violar porta-iscas e armadilhas fixadas.
            </p>
            @if($visita->observacoes)
                <p style="margin: 6px 0 0 0; color: #111827;">
                    <strong>Observações Técnicas Específicas:</strong> {{ $visita->observacoes }}
                </p>
            @endif
        </div>

        {{-- Assinaturas --}}
        <div class="signatures">
            <div class="sig-box">
                <div class="sig-line"></div>
                <strong>{{ $visita->tecnico?->nome ?? 'Técnico Aplicador' }}</strong>
                <div class="sig-title">Alaris Service — Responsável Técnico</div>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <strong>{{ $visita->cliente->nome }}</strong>
                <div class="sig-title">Assinatura do Cliente / Recebedor</div>
            </div>
        </div>

        </div> {{-- fim .os-content --}}
    </div> {{-- fim .os-container --}}

</body>
</html>
