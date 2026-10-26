@php
    $logoPath = public_path('images/logo.png');
    $logoDataUri = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : asset('images/logo.png');
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
            background: #f1f5f9;
            margin: 0;
            padding: 0;
            font-size: 13px;
            line-height: 1.4;
        }

        /* Barra de Ações Nativa PWA */
        .pwa-action-bar {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(15, 23, 42, 0.94);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            padding: max(10px, env(safe-area-inset-top)) 16px 10px 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
        }

        .pwa-bar-inner {
            max-width: 860px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .pwa-bar-left,
        .pwa-bar-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pwa-bar-center {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            min-width: 0;
        }

        .pwa-title {
            font-size: 13px;
            font-weight: 700;
            color: #f9fafb;
            letter-spacing: 0.3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pwa-subtitle {
            font-size: 11px;
            color: #94a3b8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 260px;
        }

        .pwa-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            border: 1px solid transparent;
            line-height: 1;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .pwa-icon {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .pwa-btn-back {
            background: rgba(255, 255, 255, 0.08);
            color: #e2e8f0;
            border-color: rgba(255, 255, 255, 0.15);
        }

        .pwa-btn-back:hover {
            background: rgba(255, 255, 255, 0.16);
            color: #ffffff;
        }

        .pwa-btn-share {
            background: rgba(35, 180, 235, 0.15);
            color: #38bdf8;
            border-color: rgba(35, 180, 235, 0.3);
        }

        .pwa-btn-share:hover {
            background: rgba(35, 180, 235, 0.25);
            color: #7dd3fc;
        }

        .pwa-btn-primary {
            background: #23b4eb;
            color: #0f172a;
            font-weight: 700;
            border: none;
            box-shadow: 0 2px 8px rgba(35, 180, 235, 0.35);
        }

        .pwa-btn-primary:hover {
            background: #1ea0d2;
            color: #0f172a;
        }

        .document-viewport {
            padding: 24px 16px 40px 16px;
            box-sizing: border-box;
        }

        .pwa-toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #0f172a;
            color: #38bdf8;
            border: 1px solid #38bdf8;
            padding: 10px 20px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            z-index: 2000;
            opacity: 0;
            transition: all 0.25s ease;
            pointer-events: none;
        }

        .pwa-toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
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
            border-bottom: 2px solid #23b4eb;
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
            color: #23b4eb;
            background: #e6f3f8;
            padding: 6px 10px;
            border-left: 3px solid #23b4eb;
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

        th,
        td {
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

        @media (max-width: 640px) {
            .pwa-bar-center {
                display: none;
            }

            .pwa-btn {
                padding: 7px 10px;
                font-size: 12px;
            }

            .document-viewport {
                padding: 12px 8px 32px 8px;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .os-container {
                padding: 18px 14px;
                border-radius: 6px;
            }
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .pwa-action-bar,
            .no-print,
            .pwa-toast {
                display: none !important;
            }

            .document-viewport {
                padding: 0 !important;
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
        }
    </style>
</head>

<body>

    <header class="pwa-action-bar no-print">
        <div class="pwa-bar-inner">
            <div class="pwa-bar-left">
                <a href="{{ route('visitas.show', $visita) }}" class="pwa-btn pwa-btn-back" onclick="if (history.length > 1) { history.back(); return false; }">
                    <svg class="pwa-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Voltar</span>
                </a>
            </div>

            <div class="pwa-bar-center">
                <span class="pwa-title">OS Nº {{ str_pad($visita->id, 5, '0', STR_PAD_LEFT) }}</span>
                <span class="pwa-subtitle">{{ $visita->cliente->nome }}</span>
            </div>

            <div class="pwa-bar-right">
                <button type="button" class="pwa-btn pwa-btn-share" onclick="compartilharDocumento()">
                    <svg class="pwa-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                    <span class="btn-label-text">Compartilhar</span>
                </button>

                <button type="button" class="pwa-btn pwa-btn-primary" onclick="window.print()">
                    <svg class="pwa-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Imprimir / PDF</span>
                </button>
            </div>
        </div>
    </header>

    <div class="document-viewport">
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
                    <div style="font-size: 11px; font-weight: 600; color: #23b4eb;">
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
                        <span class="info-val">{{ $visita->cliente->telefone ?? '—' }}
                            ({{ $visita->cliente->email ?? 'Sem e-mail' }})</span>
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
                        <span class="info-val">{{ $visita->cliente->bairro ?? '—' }} — CEP:
                            {{ $visita->cliente->cep ?? '—' }}</span>
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
            @if ($visita->produtos->isNotEmpty())
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
                        @foreach ($visita->produtos as $produto)
                            <tr>
                                <td>
                                    <strong>{{ $produto->nome }}</strong>
                                    <div style="font-size: 10px; color: #6b7280;">
                                        {{ $produto->principio_ativo ?? 'Princípio ativo não cadastrado' }}</div>
                                </td>
                                <td>{{ $produto->registro_anvisa ?? 'Conforme bula' }}</td>
                                <td>{{ $produto->grupo_quimico ?? 'Geral' }}</td>
                                <td style="text-align: right; font-weight: 700; color: #065f46;">
                                    {{ number_format($produto->pivot->quantidade, 2, ',', '.') }}
                                    {{ $produto->unidade_aplicacao }}
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
            @if ($visita->cliente->comodatos && $visita->cliente->comodatos->isNotEmpty())
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
                        @foreach ($visita->cliente->comodatos as $comodato)
                            <tr>
                                <td>
                                    <strong>{{ $comodato->codigo_identificacao }}</strong> — {{ $comodato->tipo }}
                                </td>
                                <td>{{ $comodato->localizacao ?? 'Não especificado' }}</td>
                                <td>{{ $comodato->data_ultima_reposicao ? $comodato->data_ultima_reposicao->format('d/m/Y') : 'Inicial' }}
                                </td>
                                <td
                                    style="font-weight: 600; color: {{ $comodato->estaAtrasado() ? '#dc2626' : '#059669' }}">
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
            <div
                style="background: #fdfdfd; border: 1px dashed #d1d5db; padding: 10px; border-radius: 4px; font-size: 11px; color: #374151;">
                <p style="margin: 0 0 6px 0;">
                    <strong>Instruções de Segurança:</strong> Manter crianças e animais domésticos afastados das áreas
                    tratadas durante o período de reentrada (mínimo de 4 a 6 horas para ambientes internos ou conforme
                    instrução do aplicador). Não remover nem violar porta-iscas e armadilhas fixadas.
                </p>
                @if ($visita->observacoes)
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
    </div> {{-- fim .document-viewport --}}

    <script>
        function compartilharDocumento() {
            const dados = {
                title: 'Ordem de Serviço OS #{{ str_pad($visita->id, 5, "0", STR_PAD_LEFT) }} — Alaris Service',
                text: 'Ordem de Serviço #{{ str_pad($visita->id, 5, "0", STR_PAD_LEFT) }} - Cliente: {{ addslashes($visita->cliente->nome) }}',
                url: window.location.href
            };

            if (navigator.share) {
                navigator.share(dados).catch((err) => {
                    if (err.name !== 'AbortError') {
                        console.error('Erro ao compartilhar:', err);
                    }
                });
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(window.location.href).then(() => {
                    mostrarToast('Link copiado para a área de transferência!');
                }).catch(() => {
                    window.open('https://api.whatsapp.com/send?text=' + encodeURIComponent(dados.text + ' ' + dados.url), '_blank');
                });
            } else {
                window.open('https://api.whatsapp.com/send?text=' + encodeURIComponent(dados.text + ' ' + dados.url), '_blank');
            }
        }

        function mostrarToast(mensagem) {
            let toast = document.getElementById('pwa-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'pwa-toast';
                toast.className = 'pwa-toast no-print';
                document.body.appendChild(toast);
            }
            toast.textContent = mensagem;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
    </script>
</body>

</html>
