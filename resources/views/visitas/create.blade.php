@extends('layouts.app')

@section('title', 'Nova Ordem de Serviço / Visita')

@section('content')
    <div class="max-w-3xl">
        <div class="card p-6">
            <form method="POST" action="{{ route('visitas.store') }}" class="space-y-6" onsubmit="return window.validarEstoqueVisitaForm ? window.validarEstoqueVisitaForm(this) : true;">
                @csrf
                <input type="hidden" name="comodato_id" value="{{ old('comodato_id', request('comodato_id')) }}">

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

                {{-- Informações Operacionais da Visita --}}
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-3"
                        style="color: var(--color-primary-400);">1. Agendamento & Alocação</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="form-label" for="cliente_id">Cliente *</label>
                            <select id="cliente_id" name="cliente_id" class="form-select" required>
                                <option value="">Selecione o cliente...</option>
                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}"
                                        {{ old('cliente_id', request('cliente_id')) == $cliente->id ? 'selected' : '' }}>
                                        {{ $cliente->nome }} {{ $cliente->documento ? "({$cliente->documento})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

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
                        <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-3"
                            style="color: var(--color-primary-400);">3. Formação de Preço & Custos Operacionais</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="form-label" for="valor_cobrado">Valor Cobrado do Cliente (R$) *</label>
                                <input type="number" id="valor_cobrado" name="valor_cobrado"
                                    value="{{ old('valor_cobrado', '0.00') }}" step="0.01" min="0"
                                    class="form-input font-bold text-white" required oninput="recalcularFinanceiroVisita()">
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="form-label mb-0" for="custo_mao_de_obra">Diária / Mão de Obra Técnico
                                        (R$)</label>
                                    <span id="label-diaria-tecnico" class="text-[11px] text-emerald-400 font-medium"></span>
                                </div>
                                <input type="number" id="custo_mao_de_obra" name="custo_mao_de_obra"
                                    value="{{ old('custo_mao_de_obra', $tecnicos->firstWhere('id', old('tecnico_id'))?->valor_diaria_padrao > 0 ? $tecnicos->firstWhere('id', old('tecnico_id'))->valor_diaria_padrao : $diariaPadrao) }}"
                                    step="0.01" min="0" class="form-input" oninput="recalcularFinanceiroVisita()">
                            </div>

                            <div>
                                <label class="form-label" for="custo_deslocamento">Deslocamento / Transporte (R$)</label>
                                <input type="number" id="custo_deslocamento" name="custo_deslocamento"
                                    value="{{ old('custo_deslocamento', '0.00') }}" step="0.01" min="0"
                                    class="form-input" oninput="recalcularFinanceiroVisita()">
                            </div>

                            <div>
                                <label class="form-label" for="gastos_adicionais">Gastos Extras / Adicionais (R$)</label>
                                <input type="number" id="gastos_adicionais" name="gastos_adicionais"
                                    value="{{ old('gastos_adicionais', '0.00') }}" step="0.01" min="0"
                                    class="form-input" oninput="recalcularFinanceiroVisita()">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="form-label" for="motivo_gastos_adicionais">Motivo dos Gastos Extras /
                                    Adicionais</label>
                                <input type="text" id="motivo_gastos_adicionais" name="motivo_gastos_adicionais"
                                    value="{{ old('motivo_gastos_adicionais') }}" class="form-input"
                                    placeholder="Ex: Pedágio, compras extras, estacionamento">
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between p-3 rounded-lg"
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

                        {{-- Painel de Lucro em Tempo Real --}}
                        <div class="mt-4 p-4 rounded-lg grid grid-cols-2 sm:grid-cols-4 gap-3 text-center"
                            style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-surface-400 block">Custo
                                    Insumos</span>
                                <span id="preview-custo-insumos" class="text-xs font-semibold text-white">R$ 0,00</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-surface-400 block">Custo
                                    Operacional</span>
                                <span id="preview-custo-operacional" class="text-xs font-semibold text-white">R$ 0,00</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-surface-400 block">Impostos
                                    (NF)</span>
                                <span id="preview-imposto" class="text-xs font-semibold text-amber-300">R$ 0,00</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-surface-400 block">Lucro
                                    Estimado</span>
                                <span id="preview-lucro-bruto" class="text-sm font-bold text-emerald-400">R$ 0,00</span>
                                <span id="preview-margem" class="text-[10px] block text-emerald-400">0%</span>
                            </div>
                        </div>
                    </div>
                @else
                    <input type="hidden" name="valor_cobrado" value="{{ old('valor_cobrado', '0.00') }}">
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
    </script>
@endsection
