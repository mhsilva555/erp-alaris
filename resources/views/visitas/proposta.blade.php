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
    <title>Proposta Comercial #{{ $visita->id }} — Alaris Service</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <style>
        @page {
            size: A4;
            margin: 15mm 18mm;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1f2937;
            background: #f1f5f9;
            margin: 0;
            padding: 0;
            font-size: 13.5px;
            line-height: 1.5;
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
            max-width: 880px;
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

        .proposal-container {
            position: relative;
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .watermark-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 70%;
            max-width: 520px;
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

        .proposal-content {
            position: relative;
            z-index: 1;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #23b4eb;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }

        .logo-box {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-logo-img {
            height: 46px;
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

        .meta-box {
            text-align: right;
        }

        .proposal-badge {
            display: inline-block;
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 5px 12px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 14px;
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
            border-radius: 0 4px 4px 0;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .info-group {
            margin-bottom: 8px;
        }

        .info-label {
            font-size: 10px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            display: block;
        }

        .info-val {
            font-size: 13.5px;
            font-weight: 600;
            color: #111827;
        }

        .scope-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            margin-top: 8px;
        }

        .scope-card h4 {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: #111827;
        }

        .scope-card p {
            margin: 0;
            color: #4b5563;
            font-size: 13px;
        }

        .pricing-box {
            background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
            border: 2px solid #a7f3d0;
            border-radius: 10px;
            padding: 24px;
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pricing-label {
            font-size: 13px;
            font-weight: 600;
            color: #065f46;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pricing-desc {
            font-size: 12px;
            color: #047857;
            margin-top: 2px;
        }

        .pricing-amount {
            font-size: 28px;
            font-weight: 700;
            color: #065f46;
            letter-spacing: -0.5px;
        }

        .terms-list {
            margin: 8px 0;
            padding-left: 20px;
            font-size: 12.5px;
            color: #4b5563;
        }

        .terms-list li {
            margin-bottom: 6px;
        }

        .signatures {
            margin-top: 48px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
            page-break-inside: avoid;
        }

        .sig-box {
            text-align: center;
        }

        .sig-line {
            border-top: 1px solid #374151;
            margin-top: 50px;
            margin-bottom: 6px;
        }

        .sig-title {
            font-size: 11px;
            color: #6b7280;
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

            .proposal-container {
                padding: 20px 16px;
                border-radius: 8px;
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

            .proposal-container {
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
                <span class="pwa-title">Proposta Comercial #{{ $visita->id }}</span>
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
        <div class="proposal-container">
        {{-- Marca d'água oficial Alaris Service --}}
        <div class="watermark-container" aria-hidden="true">
            <img src="{{ $logoDataUri }}" alt="" class="watermark-logo">
        </div>

        <div class="proposal-content">
            {{-- Header --}}
            <div class="header">
                <div class="logo-box">
                    <img src="{{ $logoDataUri }}" alt="Alaris Service" class="header-logo-img">
                    <div style="border-left: 1px solid #e5e7eb; padding-left: 14px;">
                        <p class="brand-sub">Soluções em Controle de Pragas & Higienização</p>
                    </div>
                </div>
                <div class="meta-box">
                    <div class="proposal-badge">PROPOSTA COMERCIAL Nº {{ str_pad($visita->id, 5, '0', STR_PAD_LEFT) }}
                    </div>
                    <div style="font-size: 11px; color: #6b7280; margin-top: 4px;">
                        Data: {{ now()->format('d/m/Y') }}
                    </div>
                    <div style="font-size: 11px; color: #23b4eb; font-weight: 600;">
                        Validade: 15 dias corridos
                    </div>
                </div>
            </div>

            {{-- Dados do Cliente --}}
            <div class="section-title">1. Dados do Cliente</div>
            <div class="grid-2">
                <div>
                    <div class="info-group">
                        <span class="info-label">Cliente / Razão Social</span>
                        <span class="info-val">{{ $visita->cliente->nome }}</span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">CPF / CNPJ</span>
                        <span class="info-val">{{ $visita->cliente->cpf_cnpj ?? 'A fornecer' }}</span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Telefone / E-mail</span>
                        <span class="info-val">{{ $visita->cliente->telefone ?? '—' }} •
                            {{ $visita->cliente->email ?? '—' }}</span>
                    </div>
                </div>
                <div>
                    <div class="info-group">
                        <span class="info-label">Local de Atendimento</span>
                        <span class="info-val">{{ $visita->cliente->endereco ?? 'Endereço cadastrado' }}</span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Bairro / CEP</span>
                        <span class="info-val">{{ $visita->cliente->bairro ?? '—' }} • CEP:
                            {{ $visita->cliente->cep ?? '—' }}</span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Município / Estado</span>
                        <span class="info-val">{{ $visita->cliente->cidade_estado }}</span>
                    </div>
                </div>
            </div>

            {{-- Escopo dos Serviços --}}
            <div class="section-title">2. Objeto & Escopo dos Serviços Prestados</div>
            <div class="scope-card">
                <h4>{{ $visita->tipoServicoLabel() }} — {{ $visita->tipoVisitaLabel() }}</h4>
                <p>
                    Execução técnica especializada de {{ strtolower($visita->tipoServicoLabel()) }}
                    @if ($visita->tipo_praga)
                        com foco no controle e erradicação de <strong>{{ $visita->tipo_praga }}</strong>
                    @endif,
                    compreendendo o tratamento preventivo e corretivo das dependências
                    @if ($visita->area_tratada)
                        (área estimada: <strong>{{ $visita->area_tratada }}</strong>)
                    @endif.
                </p>
            </div>

            <div style="margin-top: 12px; font-size: 12.5px; color: #4b5563;">
                <p style="margin: 0 0 6px 0;"><strong>Metodologia Aplicada:</strong></p>
                <ul class="terms-list">
                    <li>Utilização de saneantes domissanitários de uso profissional devidamente registrados e
                        autorizados pelo Ministério da Saúde e ANVISA.</li>
                    <li>Mapeamento de pontos críticos, identificação de focos e aplicação em conformidade com as Boas
                        Práticas Operacionais e Ambientais.</li>
                    <li>Equipe técnica treinada e equipada com Equipamentos de Proteção Individual (EPIs) adequados.
                    </li>
                    <li>Emissão de Certificado Técnico de Execução e Garantia com validade legal perante órgãos
                        fiscalizadores.</li>
                </ul>
            </div>

            {{-- Investimento e Condições Comerciais (SIGILO ABSOLUTO DE CUSTOS INTERNOS) --}}
            <div class="section-title">3. Investimento & Condições de Pagamento</div>
            <div class="pricing-box">
                <div>
                    <div class="pricing-label">Investimento Total do Serviço</div>
                    <div class="pricing-desc">{{ $visita->tipoServicoLabel() }} com assistência técnica inclusa</div>
                </div>
                <div class="pricing-amount">
                    R$ {{ number_format($visita->valor_cobrado, 2, ',', '.') }}
                </div>
            </div>

            <div style="margin-top: 14px;">
                <p
                    style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: #6b7280; margin: 0 0 4px 0;">
                    Condições Gerais:</p>
                <ul class="terms-list">
                    <li>O valor engloba a execução integral do serviço especificado, equipe técnica habilitada e emissão
                        do respectivo certificado.</li>
                    <li>Forma de pagamento: Boleto bancário ou transferência Pix faturado conforme alinhamento
                        comercial.</li>
                    <li>Garantia técnica de assistência pós-aplicação durante o período estipulado em contrato.</li>
                </ul>
            </div>

            {{-- Assinaturas e De Acordo --}}
            <div class="signatures">
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <strong>Alaris Service</strong>
                    <div class="sig-title">Departamento Comercial & Técnico</div>
                </div>
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <strong>{{ $visita->cliente->nome }}</strong>
                    <div class="sig-title">De Acordo / Aceite da Proposta</div>
                </div>
            </div>

        </div> {{-- fim .proposal-content --}}
    </div> {{-- fim .proposal-container --}}
    </div> {{-- fim .document-viewport --}}

    <script>
        function compartilharDocumento() {
            const dados = {
                title: 'Proposta Comercial #{{ $visita->id }} — Alaris Service',
                text: 'Proposta Comercial #{{ $visita->id }} - Cliente: {{ addslashes($visita->cliente->nome) }}',
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
