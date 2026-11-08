@php
    $logoPath = public_path('images/logo.png');
    $logoDataUri = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : asset('images/logo.png');
    $ehProposta = $contrato->ehProposta();
    $tituloDoc = $ehProposta ? 'PROPOSTA COMERCIAL' : 'CONTRATO DE PRESTAÇÃO DE SERVIÇOS';
    $dataEmissao = now()->format('d/m/Y');
    $isPdfEngine = isset($pdfEngine) && $pdfEngine;
    $podeExibirImg = extension_loaded('gd') || ! $isPdfEngine;
    $empresa = \App\Models\Configuracao::dadosEmpresa();
@endphp
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $ehProposta ? 'Proposta Comercial' : 'Contrato' }} #{{ $contrato->numero }} — {{ $empresa['nomeFantasia'] }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 12mm 8mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        @if ($isPdfEngine)
        html, body {
            background: #ffffff !important;
            padding: 0 !important;
            margin: 0 !important;
            font-size: 10.5px !important;
            line-height: 1.35 !important;
        }
        .pwa-action-bar, .no-print, .pwa-toast {
            display: none !important;
        }
        .document-viewport {
            padding: 0 !important;
            margin: 0 !important;
        }
        .proposal-container {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            max-width: 100% !important;
            border-radius: 0 !important;
        }
        .header {
            display: table !important;
            width: 100% !important;
        }
        .header > .logo-box {
            display: table-cell !important;
            vertical-align: middle !important;
        }
        .header > .meta-box {
            display: table-cell !important;
            vertical-align: middle !important;
            text-align: right !important;
        }
        .grid-2 {
            display: table !important;
            width: 100% !important;
        }
        .grid-2 > div {
            display: table-cell !important;
            width: 50% !important;
            vertical-align: top !important;
        }
        .pricing-box {
            display: table !important;
            width: 100% !important;
        }
        .pricing-box > div:first-child {
            display: table-cell !important;
            vertical-align: middle !important;
        }
        .pricing-box > .pricing-amount {
            display: table-cell !important;
            vertical-align: middle !important;
            text-align: right !important;
        }
        .signatures {
            display: table !important;
            width: 100% !important;
        }
        .signatures > .sig-box {
            display: table-cell !important;
            width: 50% !important;
            vertical-align: top !important;
        }
        @endif

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1f2937;
            background: #f1f5f9;
            margin: 0;
            padding: 0;
            font-size: 12.5px;
            line-height: 1.45;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
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
            max-width: 280px;
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

        /* Container do Documento (Visual Folha A4) */
        .proposal-container {
            position: relative;
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 30px 36px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .watermark-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 65%;
            max-width: 460px;
            opacity: 0.045;
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

        /* Header da Proposta */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #23b4eb;
            padding-bottom: 14px;
            margin-bottom: 14px;
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

        .brand-title {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.3px;
            line-height: 1.2;
        }

        .brand-sub {
            font-size: 11px;
            color: #64748b;
            margin: 2px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .meta-box {
            text-align: right;
        }

        .proposal-badge {
            display: inline-block;
            background: {{ $ehProposta ? '#ecfdf5' : '#f0f9ff' }};
            color: {{ $ehProposta ? '#065f46' : '#0369a1' }};
            border: 1px solid {{ $ehProposta ? '#a7f3d0' : '#bae6fd' }};
            padding: 5px 12px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12.5px;
            letter-spacing: 0.3px;
            line-height: 1.2;
        }

        .meta-date {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
        }

        .meta-validity {
            font-size: 11px;
            color: #23b4eb;
            font-weight: 600;
        }

        /* Seções */
        .section-title {
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #23b4eb;
            background: #f0f9ff;
            padding: 4px 10px;
            border-left: 3px solid #23b4eb;
            margin-top: 12px;
            margin-bottom: 10px;
            border-radius: 0 4px 4px 0;
            line-height: 1.25;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 20px;
        }

        .info-group {
            margin-bottom: 4px;
        }

        .info-label {
            font-size: 9px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: block;
            line-height: 1.2;
            margin-bottom: 1px;
        }

        .info-val {
            font-size: 12px;
            font-weight: 600;
            color: #111827;
            line-height: 1.35;
        }

        /* Tabela de Itens */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 6px;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .items-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 5px 10px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            line-height: 1.2;
        }

        .items-table td {
            padding: 5px 10px;
            font-size: 11.5px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            line-height: 1.35;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Card de Escopo Geral */
        .scope-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            margin-top: 6px;
        }

        .scope-card h4 {
            margin: 0 0 3px 0;
            font-size: 12.5px;
            color: #111827;
            font-weight: 700;
        }

        .scope-card p {
            margin: 0;
            color: #4b5563;
            font-size: 11.5px;
            line-height: 1.4;
        }

        /* Metodologia Técnica */
        .method-box {
            margin-top: 6px;
            font-size: 11px;
            color: #4b5563;
        }

        .terms-list {
            margin: 3px 0;
            padding-left: 16px;
            font-size: 10.5px;
            color: #4b5563;
            line-height: 1.38;
        }

        .terms-list li {
            margin-bottom: 2px;
        }

        /* Recomendações Preventivas */
        .preventive-box {
            margin-top: 3px;
        }

        .preventive-intro {
            font-size: 10.5px;
            font-weight: 600;
            color: #475569;
            margin: 0 0 3px 0;
            line-height: 1.35;
        }

        .preventive-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin: 0;
        }

        .preventive-col {
            width: 50%;
            vertical-align: top;
            border: none;
            padding: 0 10px 0 0;
        }

        .preventive-col:last-child {
            padding: 0 0 0 10px;
        }

        /* Box de Preço e Condições (Destaque Verde) */
        .pricing-box {
            background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
            border: 1.5px solid #a7f3d0;
            border-radius: 8px;
            padding: 10px 16px;
            margin-top: 9px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pricing-label {
            font-size: 11.5px;
            font-weight: 700;
            color: #065f46;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }

        .pricing-desc {
            font-size: 10.5px;
            color: #047857;
            margin-top: 2px;
        }

        .pricing-amount {
            font-size: 23px;
            font-weight: 800;
            color: #065f46;
            letter-spacing: -0.5px;
            line-height: 1;
        }

        /* Assinaturas */
        .signatures {
            margin-top: 22px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .sig-box {
            text-align: center;
        }

        .sig-line {
            border-top: 1px solid #475569;
            margin-top: 28px;
            margin-bottom: 4px;
        }

        .sig-name {
            font-size: 11.5px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.25;
        }

        .sig-title {
            font-size: 10px;
            color: #64748b;
            line-height: 1.25;
        }

        .sig-date {
            margin-top: 25px;
            font-size: 10.5px;
            font-weight: 500;
            color: #475569;
            letter-spacing: 0.5px;
        }

        .footer-note {
            text-align: center;
            font-size: 8.5px;
            color: #94a3b8;
            margin-top: 30px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .footer-company {
            font-weight: 600;
            color: #475569;
            font-size: 8.5px;
            margin-bottom: 2px;
            line-height: 1.3;
        }

        .footer-address {
            font-size: 8px;
            color: #64748b;
            margin-bottom: 3px;
            line-height: 1.25;
        }

        .footer-meta {
            font-size: 7.5px;
            color: #94a3b8;
            letter-spacing: 0.2px;
        }

        /* Responsividade Mobile & PWA */
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
            }

            .proposal-container {
                padding: 18px 14px;
                border-radius: 8px;
            }

            .grid-2 {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .pricing-box {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .signatures {
                grid-template-columns: 1fr;
                gap: 20px;
                margin-top: 20px;
            }
        }

        /* Impressão Direta e Exportação em PDF Limpa */
        @media print {
            @page {
                size: A4 portrait;
                margin: 7mm 12mm 7mm 12mm;
            }

            html, body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 11px !important;
                line-height: 1.35 !important;
            }

            .pwa-action-bar,
            .no-print,
            .pwa-toast {
                display: none !important;
            }

            .document-viewport {
                padding: 0 !important;
                margin: 0 !important;
            }

            .proposal-container {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
                border-radius: 0 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .header {
                padding-bottom: 8px !important;
                margin-bottom: 10px !important;
            }

            .header-logo-img {
                height: 38px !important;
            }

            .brand-title {
                font-size: 16px !important;
            }

            .brand-sub {
                font-size: 10px !important;
            }

            .section-title {
                font-size: 11px !important;
                padding: 3.5px 8px !important;
                margin-top: 12px !important;
                margin-bottom: 10px !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                background: #f0f9ff !important;
            }

            .grid-2 {
                gap: 5px 16px !important;
            }

            .info-group {
                margin-bottom: 3px !important;
            }

            .info-label {
                font-size: 8.5px !important;
            }

            .info-val {
                font-size: 11.5px !important;
            }

            .items-table {
                margin-top: 4px !important;
                margin-bottom: 4px !important;
            }

            .items-table th {
                padding: 4px 8px !important;
                font-size: 9.5px !important;
            }

            .items-table td {
                padding: 4px 8px !important;
                font-size: 11px !important;
            }

            .method-box {
                margin-top: 4px !important;
            }

            .terms-list {
                margin: 2px 0 !important;
                padding-left: 14px !important;
                font-size: 10px !important;
                line-height: 1.35 !important;
            }

            .terms-list li {
                margin-bottom: 1.5px !important;
            }

            .preventive-box {
                margin-top: 2px !important;
            }

            .preventive-intro {
                font-size: 9.5px !important;
                margin-bottom: 2px !important;
            }

            .preventive-table {
                margin: 0 !important;
            }

            .preventive-col {
                padding: 0 8px 0 0 !important;
            }

            .preventive-col:last-child {
                padding: 0 0 0 8px !important;
            }

            .pricing-box {
                padding: 9px 15px !important;
                margin-top: 8px !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                background: #f0fdf4 !important;
                border-color: #86efac !important;
            }

            .pricing-label {
                font-size: 11px !important;
            }

            .pricing-desc {
                font-size: 10px !important;
            }

            .pricing-amount {
                font-size: 22px !important;
            }

            .signatures {
                margin-top: 14px !important;
                gap: 28px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .sig-line {
                margin-top: 20px !important;
                margin-bottom: 3px !important;
            }

            .sig-name {
                font-size: 11px !important;
            }

            .sig-title {
                font-size: 9.5px !important;
            }

            .sig-date {
                margin-top: 25px !important;
                font-size: 9.5px !important;
                font-weight: 600 !important;
                color: #475569 !important;
            }

            .footer-note {
                margin-top: 30px !important;
                padding-top: 8px !important;
                border-top: 1px solid #e2e8f0 !important;
                font-size: 8px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .footer-company {
                font-size: 8px !important;
                margin-bottom: 1px !important;
            }

            .footer-address {
                font-size: 7.5px !important;
                margin-bottom: 2px !important;
            }

            .footer-meta {
                font-size: 7px !important;
            }

            .watermark-container {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 65%;
                max-width: 460px;
                opacity: 0.045;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>

    {{-- Barra de Navegação Superior Nativa PWA --}}
    @if (!$isPdfEngine)
    <header class="pwa-action-bar no-print">
        <div class="pwa-bar-inner">
            <div class="pwa-bar-left">
                <a href="{{ route('contratos.show', $contrato) }}" class="pwa-btn pwa-btn-back"
                    onclick="if (history.length > 1) { history.back(); return false; }">
                    <svg class="pwa-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Voltar</span>
                </a>
            </div>

            <div class="pwa-bar-center">
                <span class="pwa-title">{{ $ehProposta ? 'Proposta Comercial' : 'Contrato' }} #{{ $contrato->numero }}</span>
                <span class="pwa-subtitle">{{ $contrato->cliente->nome }}</span>
            </div>

            <div class="pwa-bar-right">
                <button type="button" class="pwa-btn pwa-btn-share" onclick="compartilharDocumento()">
                    <svg class="pwa-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                    <span>Compartilhar</span>
                </button>

                <button type="button" class="pwa-btn pwa-btn-primary" onclick="window.print()">
                    <svg class="pwa-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Imprimir / PDF</span>
                </button>
            </div>
        </div>
    </header>
    @endif

    <div class="document-viewport">
        <div class="proposal-container">

            {{-- Marca d'água oficial Alaris Service --}}
            @if ($podeExibirImg)
                <div class="watermark-container" aria-hidden="true">
                    <img src="{{ $logoDataUri }}" alt="" class="watermark-logo">
                </div>
            @endif

            <div class="proposal-content">

                {{-- Header da Proposta --}}
                <div class="header">
                    <div class="logo-box">
                        @if ($podeExibirImg)
                            <img src="{{ $logoDataUri }}" alt="{{ $empresa['nomeFantasia'] }}" class="header-logo-img">
                        @else
                            <div style="width: 38px; height: 38px; background: #23b4eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #ffffff; font-weight: bold; font-size: 18px;">A</div>
                        @endif
                        <div style="border-left: 1px solid #e5e7eb; padding-left: 14px;">
                            <h2 class="brand-title">{{ $empresa['nomeFantasia'] }}</h2>
                            <p class="brand-sub">{{ $empresa['slogan'] }}</p>
                            <div style="font-size: 10px; color: #64748b; margin-top: 2px;">
                                <span><strong>CNPJ:</strong> {{ $empresa['cnpj'] }}</span>
                                @if($empresa['telefone']) &bull; <span><strong>Tel:</strong> {{ $empresa['telefone'] }}</span> @endif
                                @if($empresa['email']) &bull; <span>{{ $empresa['email'] }}</span> @endif
                                @if(!empty($empresa['website'])) &bull; <span>{{ $empresa['website'] }}</span> @endif
                            </div>
                            <div style="font-size: 9.5px; color: #94a3b8; margin-top: 1px;">
                                <span>{{ $empresa['enderecoCompleto'] }}</span>
                                @if($empresa['licenca']) &bull; <span>{{ $empresa['licenca'] }}</span> @endif
                            </div>
                        </div>
                    </div>
                    <div class="meta-box">
                        <div class="proposal-badge">
                            {{ $ehProposta ? 'PROPOSTA COMERCIAL' : 'CONTRATO FORMAL' }} Nº {{ $contrato->numero }}
                        </div>
                        <div class="meta-date">
                            Data de Emissão: {{ $dataEmissao }}
                        </div>
                        @if ($ehProposta)
                            <div class="meta-validity">
                                Validade da Proposta: 15 dias corridos
                            </div>
                        @endif
                    </div>
                </div>

                {{-- 1. Dados do Cliente / Contratante --}}
                <div class="section-title">1. Dados do Contratante (Cliente)</div>
                <div class="grid-2">
                    <div>
                        <div class="info-group">
                            <span class="info-label">Cliente / Razão Social</span>
                            <span class="info-val">{{ $contrato->cliente->nome }}</span>
                        </div>
                        <div class="info-group">
                            <span class="info-label">CPF / CNPJ</span>
                            <span class="info-val">{{ $contrato->cliente->documento_formatado ?: 'A fornecer' }}</span>
                        </div>
                        <div class="info-group">
                            <span class="info-label">Telefone / E-mail</span>
                            <span class="info-val">
                                {{ $contrato->cliente->telefone_formatado ?: '—' }} &bull;
                                {{ $contrato->cliente->email ?: '—' }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <div class="info-group">
                            <span class="info-label">Local de Atendimento</span>
                            <span class="info-val">{{ $contrato->cliente->endereco ?: 'Endereço cadastrado' }}</span>
                        </div>
                        <div class="info-group">
                            <span class="info-label">Município / Estado / CEP</span>
                            <span class="info-val">
                                {{ $contrato->cliente->cidade_estado }}
                                @if ($contrato->cliente->cep_formatado)
                                    &bull; CEP: {{ $contrato->cliente->cep_formatado }}
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                {{-- 2. Objeto & Escopo dos Serviços --}}
                <div class="section-title">2. Objeto & Escopo dos Serviços Contratados</div>

                @if ($contrato->itens->isNotEmpty())
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 55%;">Descrição do Serviço / Objeto</th>
                                <th style="width: 15%;">Segmento</th>
                                <th style="width: 10%;" class="text-center">Qtd.</th>
                                <th style="width: 15%;" class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($contrato->itens as $idx => $item)
                                <tr>
                                    <td class="text-center" style="color: #64748b;">{{ $idx + 1 }}</td>
                                    <td>
                                        <strong>{{ $item->descricao }}</strong>
                                    </td>
                                    <td>{{ $item->tipo_servico ? ucfirst(str_replace('_', ' ', $item->tipo_servico)) : $contrato->tipoServicoLabel() }}</td>
                                    <td class="text-center">{{ number_format($item->quantidade, 0, ',', '.') }} un</td>
                                    <td class="text-right font-bold">R$ {{ number_format($item->valor_total, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($contrato->descricao)
                        <div class="scope-description-box" style="margin: 4px 0 5px 0; font-size: 10.5px; color: #374151; line-height: 1.35;">
                            <strong style="color: #1f2937;">Cláusulas Adicionais / Detalhamento:</strong> {!! nl2br(e($contrato->descricao)) !!}
                        </div>
                    @endif
                @else
                    <div class="scope-card">
                        <h4>{{ $contrato->titulo ?: 'Controle Integrado de Pragas & Higienização Técnica' }}</h4>
                        <p>
                            {{ $contrato->descricao ?: 'Prestação de serviços contínuos com cronograma de inspeção, barreira química, controle de pragas urbanas e monitoramento técnico em conformidade com as normas sanitárias vigentes.' }}
                        </p>
                    </div>
                @endif

                <div class="method-box">
                    <p style="margin: 0 0 3px 0;"><strong>Metodologia Aplicada & Garantia de Conformidade:</strong></p>
                    <ul class="terms-list">
                        <li>Utilização de saneantes domissanitários de uso profissional devidamente registrados e autorizados pelo Ministério da Saúde e ANVISA.</li>
                        <li>Mapeamento de pontos críticos, identificação de focos e aplicação em conformidade com as Boas Práticas Operacionais e Ambientais.</li>
                        <li>Equipe técnica treinada e equipada com Equipamentos de Proteção Individual (EPIs) adequados.</li>
                        <li>Emissão de Certificado Técnico de Execução e Garantia com validade perante órgãos fiscalizadores.</li>
                    </ul>
                </div>

                @if ($contrato->comodatos->isNotEmpty())
                    <div class="comodato-box" style="margin-top: 4px; padding: 5px 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px;">
                        <span style="font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #475569; display: block; margin-bottom: 2px;">
                            Equipamentos Cedidos em Regime de Comodato:
                        </span>
                        <table style="width: 100%; border-collapse: collapse; font-size: 10px; border: none; margin: 0;">
                            @foreach ($contrato->comodatos as $cmd)
                                <tr>
                                    <td style="padding: 1px 0; border: none; color: #1e293b;">
                                        &bull; <strong>{{ $cmd->nome_equipamento }}</strong> ({{ $cmd->quantidade }} un)
                                        — Troca/Vistoria: A cada {{ $cmd->periodicidade_dias }} dias
                                        @if ($cmd->produto) &bull; Insumo: {{ $cmd->produto->nome }} @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                        <p style="margin: 2px 0 0 0; font-size: 8px; color: #64748b; font-style: italic;">
                            * Equipamentos de propriedade da Contratada, cedidos sob comodato durante a vigência contratual.
                        </p>
                    </div>
                @endif

                {{-- 3. Cronograma Técnico & Periodicidade --}}
                <div class="section-title">3. Cronograma de Atendimento & Vigência</div>
                <div class="grid-2">
                    <div>
                        <div class="info-group">
                            <span class="info-label">Frequência dos Atendimentos</span>
                            <span class="info-val">{{ $contrato->periodicidadeLabel() }}</span>
                        </div>
                        <div class="info-group">
                            <span class="info-label">Tipo de Vigência</span>
                            <span class="info-val">{{ $contrato->vigenciaLabel() }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="info-group">
                            <span class="info-label">Data de Início</span>
                            <span class="info-val">{{ $contrato->data_inicio->format('d/m/Y') }}</span>
                        </div>
                        @if ($contrato->tipo_vigencia === \App\Models\Contrato::VIGENCIA_DETERMINADO && $contrato->data_fim)
                            <div class="info-group">
                                <span class="info-label">Término Previsto</span>
                                <span class="info-val">{{ $contrato->data_fim->format('d/m/Y') }}</span>
                            </div>
                        @endif
                        @if ($contrato->dia_preferencial)
                            <div class="info-group">
                                <span class="info-label">Dia Preferencial</span>
                                <span class="info-val">Todo dia {{ $contrato->dia_preferencial }} de cada mês</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- 4. Investimento & Condições Comerciais (SIGILO ABSOLUTO DE CUSTOS INTERNOS) --}}
                <div class="section-title">4. Investimento & Condições de Pagamento</div>
                <div class="pricing-box">
                    <div>
                        <div class="pricing-label">{{ $contrato->modelo_cobranca === 'mensal' ? 'Investimento Mensal do Contrato' : 'Investimento Total Contratado' }}</div>
                        <div class="pricing-desc">
                            {{ $contrato->modeloCobrancaLabel() }} &bull; Assistência técnica e suporte inclusos
                        </div>
                    </div>
                    <div class="pricing-amount">
                        R$ {{ number_format($contrato->valor_total, 2, ',', '.') }}
                    </div>
                </div>

                <div style="margin-top: 10px;">
                    <p style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: #64748b; margin: 0 0 3px 0;">
                        Condições Comerciais:</p>
                    <ul class="terms-list">
                        <li>O valor engloba a execução integral dos atendimentos contratados, equipe especializada e emissão de certificados.</li>
                        @if ($contrato->condicoes_pagamento)
                            <li><strong>Faturamento:</strong> {{ $contrato->condicoes_pagamento }}.</li>
                        @endif
                        @if ($contrato->meioPagamento)
                            <li><strong>Meio de Pagamento:</strong> {{ $contrato->meioPagamento->nome }}.</li>
                        @endif
                        @if ($contrato->desconto > 0)
                            <li>Desconto comercial concedido já aplicado no valor final.</li>
                        @endif
                        @if ($contrato->observacoes_comerciais)
                            <li><strong>Observações específicas:</strong> {{ $contrato->observacoes_comerciais }}</li>
                        @endif
                    </ul>
                </div>

                {{-- 5. Recomendações Preventivas --}}
                <div class="section-title">5. Recomendações Preventivas</div>
                <div class="preventive-box">
                    <p class="preventive-intro">
                        Para auxiliar na eficácia do controle de pragas, recomendamos:
                    </p>
                    <table class="preventive-table">
                        <tr>
                            <td class="preventive-col">
                                <ul class="terms-list">
                                    <li>Manter ambientes limpos, higienizados e organizados.</li>
                                    <li>Evitar o acúmulo de lixo, restos de alimentos e materiais sem utilização.</li>
                                    <li>Manter lixeiras tampadas e realizar o descarte frequente dos resíduos.</li>
                                </ul>
                            </td>
                            <td class="preventive-col">
                                <ul class="terms-list">
                                    <li>Armazenar alimentos em recipientes fechados e adequados.</li>
                                    <li>Manter ralos protegidos e vedar possíveis pontos de acesso de pragas.</li>
                                    <li>Comunicar à equipe técnica qualquer sinal de infestação.</li>
                                </ul>
                            </td>
                        </tr>
                    </table>
                </div>

                {{-- Assinaturas e De Acordo --}}
                <div class="signatures">
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <div class="sig-name">{{ $empresa['razaoSocial'] }}</div>
                        <div class="sig-title">CNPJ: {{ $empresa['cnpj'] }} &bull; Departamento Comercial & Técnico</div>
                        <div class="sig-date" style="visibility: hidden;">
                            Data: _____ / _____ / _________
                        </div>
                    </div>
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <div class="sig-name">{{ $contrato->cliente->nome }}</div>
                        <div class="sig-title">
                            {{ $ehProposta ? 'De Acordo / Aceite da Proposta Comercial' : 'Contratante / Representante Legal' }}
                        </div>
                        <div class="sig-date">
                            Data: _____ / _____ / _________
                        </div>
                    </div>
                </div>

                <div class="footer-note">
                    <div class="footer-company">
                        {{ $empresa['razaoSocial'] }} &bull; CNPJ: {{ $empresa['cnpj'] }}
                        @if($empresa['telefone']) &bull; Tel: {{ $empresa['telefone'] }} @endif
                        @if($empresa['email']) &bull; {{ $empresa['email'] }} @endif
                        @if(!empty($empresa['website'])) &bull; {{ $empresa['website'] }} @endif
                    </div>
                    <div class="footer-address">
                        <span>{{ $empresa['enderecoCompleto'] }}</span>
                        @if($empresa['licenca']) &bull; <span>{{ $empresa['licenca'] }}</span> @endif
                    </div>
                    <div class="footer-meta">
                        {{ $contrato->numero }} &bull; Documento gerado eletronicamente pelo ERP Alaris em {{ now()->format('d/m/Y H:i') }} &bull; Página 1 de 1
                    </div>
                </div>

            </div> {{-- fim .proposal-content --}}
        </div> {{-- fim .proposal-container --}}
    </div> {{-- fim .document-viewport --}}

    <script>
        function compartilharDocumento() {
            const dados = {
                title: '{{ $ehProposta ? "Proposta Comercial" : "Contrato" }} #{{ $contrato->numero }} — Alaris Service',
                text: '{{ $ehProposta ? "Proposta Comercial" : "Contrato" }} #{{ $contrato->numero }} - Cliente: {{ addslashes($contrato->cliente->nome) }}',
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
