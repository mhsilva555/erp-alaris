@extends('layouts.app')

@section('title', 'Nova Ordem de Serviço / Visita')

@section('content')
    <div class="max-w-3xl">
        <div class="card p-6">
            <form method="POST" action="{{ route('visitas.store') }}" class="space-y-6" onsubmit="return window.validarEstoqueVisitaForm ? window.validarEstoqueVisitaForm(this) : true;">
                @csrf
                <input type="hidden" name="comodato_id" value="{{ old('comodato_id', request('comodato_id')) }}">

                @php
                    $contratoVinculado = (isset($contrato) && $contrato) ? $contrato : null;
                    if (! $contratoVinculado && (request('contrato_id') || old('contrato_id'))) {
                        $ctrId = request('contrato_id') ?: old('contrato_id');
                        $contratoVinculado = $contratos->firstWhere('id', $ctrId);
                    }

                    $ehMensalidadeFixa = $contratoVinculado && in_array($contratoVinculado->modelo_cobranca, [
                        \App\Models\Contrato::MODELO_MENSAL,
                        \App\Models\Contrato::MODELO_FIXO_ANUAL,
                        \App\Models\Contrato::MODELO_FECHAMENTO_PERIODO,
                    ]);

                    $valorCobradoInicial = '0.00';
                    if ($contratoVinculado && $contratoVinculado->modelo_cobranca === \App\Models\Contrato::MODELO_POR_VISITA) {
                        $valorCobradoInicial = number_format((float) $contratoVinculado->valor_total, 2, '.', '');
                    }

                    $diariaInicial = old('custo_mao_de_obra');
                    if ($diariaInicial === null) {
                        if ($contratoVinculado && $contratoVinculado->custo_diaria_padrao !== null) {
                            $diariaInicial = number_format((float) $contratoVinculado->custo_diaria_padrao, 2, '.', '');
                        } else {
                            $tec = $tecnicos->firstWhere('id', old('tecnico_id'));
                            $diariaInicial = ($tec && (float) $tec->valor_diaria_padrao > 0)
                                ? number_format((float) $tec->valor_diaria_padrao, 2, '.', '')
                                : number_format($diariaPadrao, 2, '.', '');
                        }
                    }

                    $deslocamentoInicial = old('custo_deslocamento');
                    if ($deslocamentoInicial === null) {
                        if ($contratoVinculado && $contratoVinculado->custo_deslocamento_padrao !== null) {
                            $deslocamentoInicial = number_format((float) $contratoVinculado->custo_deslocamento_padrao, 2, '.', '');
                        } else {
                            $deslocamentoInicial = '0.00';
                        }
                    }
                @endphp

                @if (isset($comodato) && $comodato)
                    <div class="p-4 rounded-lg border border-sky-500/30 bg-sky-500/10 flex items-start gap-3">
                        <svg class="w-5 h-5 text-sky-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-xs">
                            <span class="font-bold text-sky-700 dark:text-sky-300 block text-sm">Ordem de Serviço Vinculada
                                a Reposição de Comodato</span>
                            <p class="text-slate-600 dark:text-surface-300 mt-0.5">
                                Esta OS está atrelada ao equipamento <strong>{{ $comodato->nome_equipamento }}</strong>
                                ({{ $comodato->quantidade }} un) do cliente <strong>{{ $comodato->cliente->nome }}</strong>.
                                Ao finalizar esta visita, <strong>a reposição do comodato será confirmada e o próximo ciclo
                                    recalculado automaticamente</strong>.
                            </p>
                        </div>
                    </div>
                @endif

                @if (isset($contrato) && $contrato)
                    <input type="hidden" name="contrato_id" value="{{ $contrato->id }}">
                    <div
                        class="p-4 rounded-lg flex items-start gap-3 bg-indigo-50 border border-indigo-200 dark:bg-indigo-950/30 dark:border-indigo-800/50">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 shrink-0 mt-0.5" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <div class="text-xs">
                            <span class="font-bold text-indigo-700 dark:text-indigo-300 block text-sm">Ordem de Serviço Vinculada ao Contrato {{ $contrato->numero }}</span>
                            <p class="text-slate-600 dark:text-surface-300 mt-0.5">
                                Cliente: <strong>{{ $contrato->cliente->nome }}</strong> • Periodicidade: <strong>{{ $contrato->periodicidadeLabel() }}</strong> • Modelo: <strong>{{ $contrato->modeloCobrancaLabel() }}</strong> (R$ {{ number_format((float) $contrato->valor_total, 2, ',', '.') }}).
                                @if ($ehMensalidadeFixa)
                                    <span class="block mt-1 text-indigo-800 dark:text-indigo-200 font-medium">✨ Atendimento coberto pela mensalidade fixa do contrato. O valor cobrado avulso da O.S. foi mantido em R$ 0,00.</span>
                                @else
                                    <span class="block mt-1 text-indigo-800 dark:text-indigo-200 font-medium">Cobrança unitária por visita executada (R$ {{ number_format((float) $contrato->valor_total, 2, ',', '.') }}).</span>
                                @endif
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Informações Operacionais da Visita --}}
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-3"
                        style="color: var(--color-primary-400);">1. Agendamento & Alocação</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="form-label" for="cliente_id">Cliente *</label>
                            <select id="cliente_id" name="cliente_id" class="form-select" required onchange="window.sugerirContratoPorCliente && window.sugerirContratoPorCliente(this)">
                                <option value="">Selecione o cliente...</option>
                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}"
                                        {{ old('cliente_id', request('cliente_id', $contrato?->cliente_id)) == $cliente->id ? 'selected' : '' }}>
                                        {{ $cliente->nome }} {{ $cliente->documento ? "({$cliente->documento})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if (!isset($contrato) && isset($contratos) && $contratos->isNotEmpty())
                            <div class="sm:col-span-2">
                                <label class="form-label" for="contrato_id">Contrato de Origem (Opcional)</label>
                                <select id="contrato_id" name="contrato_id" class="form-select" onchange="window.atualizarContratoOrigem && window.atualizarContratoOrigem(this)">
                                    <option value="" data-valor="0.00" data-modelo="">Nenhum (Visita Avulsa / Pontual)</option>
                                    @foreach ($contratos as $ctr)
                                        <option value="{{ $ctr->id }}"
                                            data-valor="{{ number_format((float) $ctr->valor_total, 2, '.', '') }}"
                                            data-modelo="{{ $ctr->modelo_cobranca }}"
                                            data-modelo-label="{{ $ctr->modeloCobrancaLabel() }}"
                                            data-numero="{{ $ctr->numero }}"
                                            data-cliente-id="{{ $ctr->cliente_id }}"
                                            data-tipo-servico="{{ $ctr->tipo_servico }}"
                                            data-diaria="{{ $ctr->custo_diaria_padrao !== null ? number_format((float) $ctr->custo_diaria_padrao, 2, '.', '') : '' }}"
                                            data-deslocamento="{{ $ctr->custo_deslocamento_padrao !== null ? number_format((float) $ctr->custo_deslocamento_padrao, 2, '.', '') : '' }}"
                                            data-exige-nf="{{ $ctr->exige_nota_fiscal ? '1' : '0' }}"
                                            data-aliquota-nf="{{ $ctr->percentual_imposto }}"
                                            {{ old('contrato_id', request('contrato_id')) == $ctr->id ? 'selected' : '' }}>
                                            {{ $ctr->numero }} — {{ $ctr->cliente->nome }} ({{ $ctr->periodicidadeLabel() }} • {{ $ctr->modeloCobrancaLabel() }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label class="form-label" for="tipo_visita">Categoria da Visita *</label>
                            <select id="tipo_visita" name="tipo_visita" class="form-select" required>
                                @foreach (\App\Models\Visita::TIPOS_VISITA as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ old('tipo_visita', 'execucao') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label" for="tipo_servico">Linha de Serviço *</label>
                            <select id="tipo_servico" name="tipo_servico" class="form-select" required>
                                @foreach (\App\Models\Visita::TIPOS_SERVICO as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ old('tipo_servico', request('tipo_servico', 'controle_pragas')) === $key ? 'selected' : '' }}>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label" for="data_hora">Data e Horário *</label>
                            <input type="datetime-local" id="data_hora" name="data_hora"
                                value="{{ old('data_hora', request('data') ? (str_contains(request('data'), 'T') ? request('data') : request('data') . 'T09:00') : now()->format('Y-m-d\TH:i')) }}"
                                class="form-input" required>
                        </div>

                        <div>
                            <label class="form-label" for="status">Status Inicial *</label>
                            <select id="status" name="status" class="form-select" required>
                                @foreach (\App\Models\Visita::STATUSES as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ old('status', 'agendada') === $key ? 'selected' : '' }}>{{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label" for="tecnico_id">Técnico Responsável *</label>
                            <select id="tecnico_id" name="tecnico_id" class="form-select" required
                                onchange="atualizarDiariaTecnico()">
                                <option value="" data-diaria="{{ number_format($diariaPadrao, 2, '.', '') }}">
                                    Selecione o profissional...</option>
                                @foreach ($tecnicos as $tecnico)
                                    <option value="{{ $tecnico->id }}"
                                        data-diaria="{{ number_format($tecnico->valor_diaria_padrao > 0 ? $tecnico->valor_diaria_padrao : $diariaPadrao, 2, '.', '') }}"
                                        {{ old('tecnico_id', request('tecnico_id')) == $tecnico->id ? 'selected' : '' }}>
                                        {{ $tecnico->nome }}
                                        {{ $tecnico->especialidade ? "({$tecnico->especialidade})" : '' }}
                                        @permissao('tecnicos.acessar_dados')
                                            @if ($tecnico->valor_diaria_padrao > 0)
                                                — Diária: R$ {{ number_format($tecnico->valor_diaria_padrao, 2, ',', '.') }}
                                            @endif
                                        @endpermissao
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label" for="proxima_visita">Previsão de Retorno Preventivo</label>
                            <input type="datetime-local" id="proxima_visita" name="proxima_visita"
                                value="{{ old('proxima_visita') }}" class="form-input">
                        </div>

                        <div>
                            <label class="form-label" for="tipo_praga">Praga-Alvo / Foco</label>
                            <input type="text" id="tipo_praga" name="tipo_praga"
                                value="{{ old('tipo_praga', request('tipo_praga')) }}" class="form-input"
                                placeholder="Ex: Baratas germânicas, cupim de solo, ratos">
                        </div>

                        <div>
                            <label class="form-label" for="area_tratada">Área / Cômodos Tratados</label>
                            <input type="text" id="area_tratada" name="area_tratada"
                                value="{{ old('area_tratada', request('area_tratada')) }}" class="form-input"
                                placeholder="Ex: 180 m² - Cozinha e Perímetro externo">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="form-label" for="observacoes">Observações Técnicas / Instruções de
                                Segurança</label>
                            <textarea id="observacoes" name="observacoes" rows="2" class="form-textarea"
                                placeholder="Instruções para o técnico, tempo de reentrada do local...">{{ old('observacoes', request('observacoes')) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Insumos Fracionados Utilizados --}}
                <div class="pt-4 border-t" style="border-color: var(--color-surface-800);">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-white"
                                style="color: var(--color-primary-400);">2. Insumos Químicos & Doses Fracionadas</h3>
                            <p class="text-[11px]" style="color: var(--color-surface-400);">Informe as doses aplicadas em
                                mL, gramas ou unidades. O sistema valida o saldo e debita atomicamente do estoque ao criar a OS. Se a OS for cancelada, os insumos retornam ao estoque.</p>
                        </div>
                        <button type="button" onclick="addProdutoRow()" class="btn btn-secondary btn-sm shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Adicionar Insumo
                        </button>
                    </div>

                    @error('produtos')
                        <div class="mb-3 p-3 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs flex items-center gap-2 animate-fadeIn">
                            <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span class="font-medium">{{ $message }}</span>
                        </div>
                    @enderror

                    <div id="produtos-container" class="space-y-3">
                        @if (old('produtos'))
                            @foreach (old('produtos') as $i => $item)
                                @php
                                    $pSel = $produtos->firstWhere('id', $item['id'] ?? null);
                                    $unApl = $pSel->unidade_aplicacao ?? 'mL';
                                @endphp
                                <div class="produto-row p-3 rounded-lg border space-y-2 animate-fadeIn"
                                    style="border-color: var(--color-border); background-color: var(--color-surface-subtle);">
                                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                        <div class="flex-1 min-w-0">
                                            <select name="produtos[{{ $i }}][id]"
                                                class="form-select produto-select w-full" required
                                                onchange="window.atualizarProdutoRow && window.atualizarProdutoRow(this); recalcularFinanceiroVisita()">
                                                <option value="">Selecione o insumo / defensivo...</option>
                                                @foreach ($produtos as $p)
                                                    <option value="{{ $p->id }}"
                                                        {{ ($item['id'] ?? '') == $p->id ? 'selected' : '' }}
                                                        class="{{ $p->quantidade_estoque <= 0 ? 'text-rose-500 font-semibold' : '' }}">
                                                        {{ $p->nome }} ({{ $p->quantidade_estoque <= 0 ? 'SEM ESTOQUE' : 'Saldo: ' . $p->quantidade_estoque_formatada . ' ' . ($p->unidade_armazenamento ?? $p->unidade) }} &bull; Uso em:
                                                        {{ $p->unidade_aplicacao ?? 'mL' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="flex items-center gap-2 shrink-0">
                                            <div class="relative flex items-center">
                                                <input type="number" name="produtos[{{ $i }}][quantidade]"
                                                    step="any" min="0.0001"
                                                    value="{{ $item['quantidade'] ?? '' }}" placeholder="Qtd / Dose"
                                                    class="form-input produto-qtd text-left sm:text-right !pr-10" required
                                                    oninput="window.atualizarProdutoRow && window.atualizarProdutoRow(this); recalcularFinanceiroVisita()" />
                                                <span
                                                    class="produto-unidade-badge absolute right-2.5 text-xs font-semibold pointer-events-none text-slate-500">
                                                    {{ $unApl }}
                                                </span>
                                            </div>
                                            <button type="button"
                                                onclick="this.closest('.produto-row').remove(); recalcularFinanceiroVisita();"
                                                class="btn btn-danger-soft btn-sm p-2 shrink-0" title="Remover produto">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="produto-feedback text-[11px] flex items-center justify-between px-1"
                                        style="color: var(--color-slate-500);">
                                        <span class="produto-baixa-preview"></span>
                                        <span class="produto-custo-preview font-medium"
                                            style="color: var(--color-ink-900);"></span>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                {{-- Formação de Preço e Custos (Aba Financeira) --}}
                @permissao('visitas.acessar_dados')
                    <div class="pt-4 border-t" style="border-color: var(--color-surface-800);">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-white"
                                style="color: var(--color-primary-400);">3. Formação de Preço & Custos Operacionais</h3>
                            <div id="banner-contrato-incluso" class="text-xs text-sky-400 font-medium flex items-center gap-1.5 {{ $ehMensalidadeFixa ? '' : 'hidden' }}">
                                <svg class="w-3.5 h-3.5 text-sky-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Atendimento Incluso no Contrato <strong id="hint-contrato-numero">{{ $contratoVinculado?->numero }}</strong> (Faturamento via Mensalidade)</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="form-label" for="valor_cobrado">Valor Cobrado do Cliente (R$) *</label>
                                <div class="relative">
                                    <input type="number" id="valor_cobrado" name="valor_cobrado"
                                        value="{{ old('valor_cobrado', $valorCobradoInicial) }}" step="0.01" min="0"
                                        class="form-input font-bold text-white {{ $ehMensalidadeFixa ? 'opacity-70 cursor-not-allowed !bg-surface-900 pr-28' : '' }}"
                                        required oninput="recalcularFinanceiroVisita()"
                                        {{ $ehMensalidadeFixa ? 'readonly' : '' }}>
                                    <span id="badge-incluso-contrato" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-semibold text-sky-400 bg-sky-500/15 px-2 py-0.5 rounded pointer-events-none {{ $ehMensalidadeFixa ? '' : 'hidden' }}">
                                        Incluso no Contrato
                                    </span>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="form-label mb-0" for="custo_mao_de_obra">Diária / Mão de Obra Técnico (R$)</label>
                                    <span id="label-diaria-tecnico" class="text-[11px] text-emerald-400 font-medium"></span>
                                </div>
                                <input type="number" id="custo_mao_de_obra" name="custo_mao_de_obra"
                                    value="{{ $diariaInicial }}"
                                    step="0.01" min="0" class="form-input" oninput="recalcularFinanceiroVisita()">
                            </div>

                            <div>
                                <label class="form-label" for="custo_deslocamento">Deslocamento / Transporte (R$)</label>
                                <input type="number" id="custo_deslocamento" name="custo_deslocamento"
                                    value="{{ $deslocamentoInicial }}" step="0.01" min="0"
                                    class="form-input" oninput="recalcularFinanceiroVisita()">
                            </div>

                            <div>
                                <label class="form-label" for="gastos_adicionais">Gastos Extras / Adicionais (R$)</label>
                                <input type="number" id="gastos_adicionais" name="gastos_adicionais"
                                    value="{{ old('gastos_adicionais', '0.00') }}" step="0.01" min="0"
                                    class="form-input" oninput="recalcularFinanceiroVisita()">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="form-label" for="motivo_gastos_adicionais">Motivo dos Gastos Extras / Adicionais</label>
                                <input type="text" id="motivo_gastos_adicionais" name="motivo_gastos_adicionais"
                                    value="{{ old('motivo_gastos_adicionais') }}" class="form-input"
                                    placeholder="Ex: Pedágio, compras extras, estacionamento">
                            </div>
                        </div>

                        {{-- Tributação Centralizada no Contrato (Preço Fechado) --}}
                        <div id="container-nf-contrato" class="{{ $ehMensalidadeFixa ? '' : 'hidden' }} mt-4 p-3.5 rounded-lg flex items-center justify-between"
                            style="background: var(--color-surface-900); border: 1px solid var(--color-surface-800);">
                            <div class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-sky-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <span class="text-xs font-bold text-white block">Tributação Centralizada no Contrato (Preço Fechado)</span>
                                    <span class="text-[11px]" style="color: var(--color-surface-400);">O imposto (NFS-e) é descontado diretamente da receita única do contrato. Esta O.S. não gera imposto avulso.</span>
                                </div>
                            </div>
                            <span class="text-xs font-semibold text-sky-400 bg-sky-500/15 px-2.5 py-1 rounded shrink-0">Retido no Contrato</span>
                        </div>

                        {{-- Seleção de NF para Atendimentos Avulsos --}}
                        <div id="container-nf-avulsa" class="{{ $ehMensalidadeFixa ? 'hidden' : '' }} mt-4 flex items-center justify-between p-3 rounded-lg"
                            style="background: var(--color-surface-900); border: 1px solid var(--color-surface-800);">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="exige_nota_fiscal" name="exige_nota_fiscal" value="1"
                                    {{ old('exige_nota_fiscal') ? 'checked' : '' }} onchange="recalcularFinanceiroVisita()"
                                    class="rounded w-4 h-4">
                                <span class="text-sm font-medium text-white">Exige Nota Fiscal?</span>
                                <span class="text-xs" style="color: var(--color-surface-400);">(Aplica taxa configurada de
                                    {{ $aliquotaPadrao }}% no cálculo tributário)</span>
                            </label>
                        </div>

                        {{-- Painel de Custos / Resumo em Tempo Real --}}
                        <div class="mt-4 p-4 rounded-lg grid grid-cols-2 sm:grid-cols-4 gap-3 text-center"
                            style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-surface-400 block">Custo Insumos</span>
                                <span id="preview-custo-insumos" class="text-xs font-semibold text-white">R$ 0,00</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-surface-400 block" id="label-custo-operacional">
                                    {{ $ehMensalidadeFixa ? 'Custo Deste Atendimento' : 'Custo Operacional' }}
                                </span>
                                <span id="preview-custo-operacional" class="text-xs font-semibold text-white">R$ 0,00</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-surface-400 block" id="label-preview-imposto">
                                    {{ $ehMensalidadeFixa ? 'Tributação (NF)' : 'Impostos (NF)' }}
                                </span>
                                <span id="preview-imposto" class="text-xs font-semibold {{ $ehMensalidadeFixa ? 'text-sky-300' : 'text-amber-300' }}">
                                    {{ $ehMensalidadeFixa ? 'Incluso no Contrato' : 'R$ 0,00' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-surface-400 block" id="label-resultado-visita">
                                    {{ $ehMensalidadeFixa ? 'Impacto no Contrato' : 'Lucro Estimado' }}
                                </span>
                                <div id="container-resultado-visita">
                                    <span id="preview-lucro-bruto" class="text-sm font-bold {{ $ehMensalidadeFixa ? 'text-sky-400' : 'text-emerald-400' }}">
                                        {{ $ehMensalidadeFixa ? 'Atendimento Coberto' : 'R$ 0,00' }}
                                    </span>
                                    <span id="preview-margem" class="text-[10px] block {{ $ehMensalidadeFixa ? 'text-sky-400 font-medium' : 'text-emerald-400' }}">
                                        {{ $ehMensalidadeFixa ? 'Deduzido do Contrato' : '0%' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <input type="hidden" name="valor_cobrado" value="{{ old('valor_cobrado', $valorCobradoInicial) }}">
                    <input type="hidden" name="custo_mao_de_obra" value="{{ old('custo_mao_de_obra', '0.00') }}">
                    <input type="hidden" name="custo_deslocamento" value="{{ old('custo_deslocamento', '0.00') }}">
                    <input type="hidden" name="gastos_adicionais" value="{{ old('gastos_adicionais', '0.00') }}">
                @endpermissao

                <div class="flex items-center gap-3 pt-3">
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Cadastrar Ordem de Serviço
                    </button>
                    <a href="{{ route('visitas.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.__aliquotaImpostoPadrao = {{ $aliquotaPadrao }};
        window.__produtosPrecos = {
            @foreach ($produtos as $p)
                {{ $p->id }}: {{ (float) $p->preco_custo_unitario }},
            @endforeach
        };
        window.__produtosFatores = {
            @foreach ($produtos as $p)
                {{ $p->id }}: {{ (float) $p->fator_conversao }},
            @endforeach
        };
        window.__produtosMetadata = {
            @foreach ($produtos as $p)
                {{ $p->id }}: {
                    unidade_aplicacao: '{{ $p->unidade_aplicacao ?? 'mL' }}',
                    unidade_armazenamento: '{{ $p->unidade_armazenamento ?? $p->unidade }}',
                    fator: {{ (float) $p->fator_conversao }},
                    preco: {{ (float) $p->preco_custo_unitario }},
                    saldo: {{ (float) $p->quantidade_estoque }}
                },
            @endforeach
        };
        window.__produtosOptions =
            `@foreach ($produtos as $p)<option value="{{ $p->id }}" class="{{ $p->quantidade_estoque <= 0 ? 'text-rose-500 font-semibold' : '' }}">{{ $p->nome }} ({{ $p->quantidade_estoque <= 0 ? 'SEM ESTOQUE' : 'Saldo: ' . $p->quantidade_estoque_formatada . ' ' . ($p->unidade_armazenamento ?? $p->unidade) }} &bull; Uso em: {{ $p->unidade_aplicacao ?? 'mL' }})</option>@endforeach`;

        document.addEventListener('DOMContentLoaded', function() {
            if (window.recalcularFinanceiroVisita) {
                window.recalcularFinanceiroVisita();
            }
        });

        window.atualizarContratoOrigem = function(select) {
            if (!select) return;
            const opt = select.options[select.selectedIndex];
            if (!opt) return;

            const valor = opt.getAttribute('data-valor');
            const modelo = opt.getAttribute('data-modelo');
            const modeloLabel = opt.getAttribute('data-modelo-label') || 'Mensalidade Fixa';
            const numero = opt.getAttribute('data-numero') || '';
            const clienteId = opt.getAttribute('data-cliente-id');
            const tipoServico = opt.getAttribute('data-tipo-servico');

            const inputValor = document.getElementById('valor_cobrado');
            const bannerIncluso = document.getElementById('banner-contrato-incluso');
            const badgeIncluso = document.getElementById('badge-incluso-contrato');
            const hintNumero = document.getElementById('hint-contrato-numero');
            const containerNfContrato = document.getElementById('container-nf-contrato');
            const containerNfAvulsa = document.getElementById('container-nf-avulsa');
            const inputDiaria = document.getElementById('custo_mao_de_obra');
            const inputDeslocamento = document.getElementById('custo_deslocamento');

            const diariaContrato = opt.getAttribute('data-diaria');
            const deslocamentoContrato = opt.getAttribute('data-deslocamento');

            if (opt.value && modelo) {
                const ehMensal = ['mensal', 'fixo_anual', 'fechamento_periodo'].includes(modelo);
                if (ehMensal) {
                    if (bannerIncluso) bannerIncluso.classList.remove('hidden');
                    if (badgeIncluso) badgeIncluso.classList.remove('hidden');
                    if (containerNfContrato) containerNfContrato.classList.remove('hidden');
                    if (containerNfAvulsa) containerNfAvulsa.classList.add('hidden');
                    if (hintNumero) hintNumero.textContent = numero;
                    if (inputValor) {
                        inputValor.value = '0.00';
                        inputValor.setAttribute('readonly', 'readonly');
                        inputValor.classList.add('opacity-70', 'cursor-not-allowed', '!bg-surface-900', 'pr-28');
                    }
                    if (inputDiaria && diariaContrato) {
                        inputDiaria.value = parseFloat(diariaContrato).toFixed(2);
                    }
                    if (inputDeslocamento && deslocamentoContrato) {
                        inputDeslocamento.value = parseFloat(deslocamentoContrato).toFixed(2);
                    }
                } else {
                    // Por visita executada
                    if (bannerIncluso) bannerIncluso.classList.add('hidden');
                    if (badgeIncluso) badgeIncluso.classList.add('hidden');
                    if (containerNfContrato) containerNfContrato.classList.add('hidden');
                    if (containerNfAvulsa) containerNfAvulsa.classList.remove('hidden');
                    if (inputValor) {
                        inputValor.value = parseFloat(valor || 0).toFixed(2);
                        inputValor.removeAttribute('readonly');
                        inputValor.classList.remove('opacity-70', 'cursor-not-allowed', '!bg-surface-900', 'pr-28');
                    }
                }
            } else {
                // Nenhum contrato selecionado
                if (bannerIncluso) bannerIncluso.classList.add('hidden');
                if (badgeIncluso) badgeIncluso.classList.add('hidden');
                if (containerNfContrato) containerNfContrato.classList.add('hidden');
                if (containerNfAvulsa) containerNfAvulsa.classList.remove('hidden');
                if (inputValor) {
                    inputValor.removeAttribute('readonly');
                    inputValor.classList.remove('opacity-70', 'cursor-not-allowed', '!bg-surface-900', 'pr-28');
                }
            }

            if (clienteId) {
                const clienteSelect = document.getElementById('cliente_id');
                if (clienteSelect && (!clienteSelect.value || clienteSelect.value !== clienteId)) {
                    clienteSelect.value = clienteId;
                }
            }

            if (tipoServico) {
                const tipoServicoSelect = document.getElementById('tipo_servico');
                if (tipoServicoSelect && tipoServico) {
                    tipoServicoSelect.value = tipoServico;
                }
            }

            if (typeof window.recalcularFinanceiroVisita === 'function') {
                window.recalcularFinanceiroVisita();
            }
        };

        window.sugerirContratoPorCliente = function(selectCliente) {
            const contratoSelect = document.getElementById('contrato_id');
            if (!contratoSelect) return;

            const clienteId = selectCliente.value;
            if (!clienteId) return;

            const optAtual = contratoSelect.options[contratoSelect.selectedIndex];
            if (optAtual && optAtual.getAttribute('data-cliente-id') === clienteId) {
                return;
            }

            const opcoesCliente = Array.from(contratoSelect.options).filter(opt => opt.getAttribute('data-cliente-id') === clienteId);
            if (opcoesCliente.length === 1) {
                contratoSelect.value = opcoesCliente[0].value;
                window.atualizarContratoOrigem(contratoSelect);
            }
        };
    </script>
@endsection
