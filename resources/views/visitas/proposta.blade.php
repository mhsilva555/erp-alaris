@php
    $logoPath = public_path('images/logo.png');
    $logoDataUri = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : asset('images/logo.png');
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
            background: #f3f4f6;
            margin: 0;
            padding: 24px;
            font-size: 13.5px;
            line-height: 1.5;
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
            border-bottom: 2px solid #0284c7;
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
            letter-spacing: 0.8px;
            color: #065f46;
            background: #f0fdf4;
            padding: 7px 12px;
            border-left: 3px solid #10b981;
            margin-top: 24px;
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
            font-weight: 800;
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
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn-print {
            background: #059669;
            color: white;
            border: none;
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .btn-print:hover {
            background: #047857;
        }
        @media print {
            body {
                background: white;
                padding: 0;
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
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">
            🖨️ Imprimir / Salvar em PDF
        </button>
    </div>

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
                    <div class="proposal-badge">PROPOSTA COMERCIAL Nº {{ str_pad($visita->id, 5, '0', STR_PAD_LEFT) }}</div>
                    <div style="font-size: 11px; color: #6b7280; margin-top: 4px;">
                        Data: {{ now()->format('d/m/Y') }}
                    </div>
                    <div style="font-size: 11px; color: #0284c7; font-weight: 600;">
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
                    <span class="info-val">{{ $visita->cliente->telefone ?? '—' }} • {{ $visita->cliente->email ?? '—' }}</span>
                </div>
            </div>
            <div>
                <div class="info-group">
                    <span class="info-label">Local de Atendimento</span>
                    <span class="info-val">{{ $visita->cliente->endereco ?? 'Endereço cadastrado' }}</span>
                </div>
                <div class="info-group">
                    <span class="info-label">Bairro / CEP</span>
                    <span class="info-val">{{ $visita->cliente->bairro ?? '—' }} • CEP: {{ $visita->cliente->cep ?? '—' }}</span>
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
                @if($visita->tipo_praga) com foco no controle e erradicação de <strong>{{ $visita->tipo_praga }}</strong>@endif,
                compreendendo o tratamento preventivo e corretivo das dependências
                @if($visita->area_tratada) (área estimada: <strong>{{ $visita->area_tratada }}</strong>)@endif.
            </p>
        </div>

        <div style="margin-top: 12px; font-size: 12.5px; color: #4b5563;">
            <p style="margin: 0 0 6px 0;"><strong>Metodologia Aplicada:</strong></p>
            <ul class="terms-list">
                <li>Utilização de saneantes domissanitários de uso profissional devidamente registrados e autorizados pelo Ministério da Saúde e ANVISA.</li>
                <li>Mapeamento de pontos críticos, identificação de focos e aplicação em conformidade com as Boas Práticas Operacionais e Ambientais.</li>
                <li>Equipe técnica treinada e equipada com Equipamentos de Proteção Individual (EPIs) adequados.</li>
                <li>Emissão de Certificado Técnico de Execução e Garantia com validade legal perante órgãos fiscalizadores.</li>
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
            <p style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: #6b7280; margin: 0 0 4px 0;">Condições Gerais:</p>
            <ul class="terms-list">
                <li>O valor engloba a execução integral do serviço especificado, equipe técnica habilitada e emissão do respectivo certificado.</li>
                <li>Forma de pagamento: Boleto bancário ou transferência Pix faturado conforme alinhamento comercial.</li>
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

</body>
</html>
