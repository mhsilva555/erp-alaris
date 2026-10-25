@extends('layouts.app')

@section('title', 'Editar Ordem de Serviço #' . $visita->id)

@section('content')
    <div class="max-w-3xl">
        <div class="card p-6">
            <form method="POST" action="{{ route('visitas.update', $visita) }}" class="space-y-6">
                @csrf @method('PUT')

                {{-- Informações Operacionais da Visita --}}
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-3"
                        style="color: var(--color-primary-400);">1. Agendamento & Alocação</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="form-label" for="cliente_id">Cliente *</label>
                            <select id="cliente_id" name="cliente_id" class="form-select" required>
                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}"
                                        {{ old('cliente_id', $visita->cliente_id) == $cliente->id ? 'selected' : '' }}>
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
                                        {{ old('tipo_visita', $visita->tipo_visita) === $key ? 'selected' : '' }}>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label" for="tipo_servico">Linha de Serviço *</label>
                            <select id="tipo_servico" name="tipo_servico" class="form-select" required>
                                @foreach (\App\Models\Visita::TIPOS_SERVICO as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ old('tipo_servico', $visita->tipo_servico) === $key ? 'selected' : '' }}>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label" for="data_hora">Data e Horário *</label>
                            <input type="datetime-local" id="data_hora" name="data_hora"
                                value="{{ old('data_hora', $visita->data_hora->format('Y-m-d\TH:i')) }}" class="form-input"
                                required>
                        </div>

                        <div>
                            <label class="form-label" for="status">Status *</label>
                            <select id="status" name="status" class="form-select" required>
                                @foreach (\App\Models\Visita::STATUSES as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ old('status', $visita->status) === $key ? 'selected' : '' }}>
                                        {{ $label }}</option>
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
                                        {{ old('tecnico_id', $visita->tecnico_id) == $tecnico->id ? 'selected' : '' }}>
                                        {{ $tecnico->nome }}
                                        {{ $tecnico->especialidade ? "({$tecnico->especialidade})" : '' }}
                                        @if ($tecnico->valor_diaria_padrao > 0)
                                            — Diária: R$ {{ number_format($tecnico->valor_diaria_padrao, 2, ',', '.') }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label" for="proxima_visita">Previsão de Retorno Preventivo</label>
                            <input type="datetime-local" id="proxima_visita" name="proxima_visita"
                                value="{{ old('proxima_visita', $visita->proxima_visita ? $visita->proxima_visita->format('Y-m-d\TH:i') : '') }}"
                                class="form-input">
                        </div>

                        <div>
                            <label class="form-label" for="tipo_praga">Praga-Alvo / Foco</label>
                            <input type="text" id="tipo_praga" name="tipo_praga"
                                value="{{ old('tipo_praga', $visita->tipo_praga) }}" class="form-input">
                        </div>

                        <div>
                            <label class="form-label" for="area_tratada">Área / Cômodos Tratados</label>
                            <input type="text" id="area_tratada" name="area_tratada"
                                value="{{ old('area_tratada', $visita->area_tratada) }}" class="form-input">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="form-label" for="observacoes">Observações Técnicas / Instruções de
                                Segurança</label>
                            <textarea id="observacoes" name="observacoes" rows="2" class="form-textarea">{{ old('observacoes', $visita->observacoes) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Insumos Fracionados Utilizados --}}
                <div class="pt-4 border-t" style="border-color: var(--color-surface-800);">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-white"
                                style="color: var(--color-primary-400);">2. Insumos Químicos & Doses Fracionadas</h3>
                            <p class="text-[11px]" style="color: var(--color-surface-400);">Doses informadas em mL, gramas
                                ou unidades. Ao concluir a OS, o sistema debitará com conversão fracionada.</p>
                        </div>
                        <button type="button" onclick="addProdutoRow()" class="btn btn-secondary btn-sm shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Adicionar Insumo
                        </button>
                    </div>

                    <div id="produtos-container" class="space-y-3">
                        @php
                            $produtosOld = old(
                                'produtos',
                                $visita->produtos
                                    ->map(fn($p) => ['id' => $p->id, 'quantidade' => $p->pivot->quantidade])
                                    ->toArray(),
                            );
                        @endphp
                        @if (!empty($produtosOld))
                            @foreach ($produtosOld as $i => $item)
                                <div class="produto-row flex items-center gap-3">
                                    <select name="produtos[{{ $i }}][id]" class="form-select produto-select"
                                        style="flex:1;min-width:0;width:auto;" required
                                        onchange="recalcularFinanceiroVisita()">
                                        <option value="">Selecione...</option>
                                        @foreach ($produtos as $p)
                                            <option value="{{ $p->id }}"
                                                {{ ($item['id'] ?? '') == $p->id ? 'selected' : '' }}>
                                                {{ $p->nome }} (Saldo:
                                                {{ number_format($p->quantidade_estoque, 2, ',', '.') }}
                                                {{ $p->unidade_armazenamento ?? $p->unidade }} &bull; Dose:
                                                {{ $p->unidade_aplicacao ?? 'mL' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="number" name="produtos[{{ $i }}][quantidade]"
                                        step="0.01" min="0.01" value="{{ $item['quantidade'] ?? '' }}"
                                        placeholder="Dose" class="form-input produto-qtd" style="width:7rem;flex:none;"
                                        required oninput="recalcularFinanceiroVisita()" />
                                    <button type="button"
                                        onclick="this.closest('.produto-row').remove(); recalcularFinanceiroVisita();"
                                        class="text-red-400 hover:text-red-300 transition p-1 shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                {{-- Formação de Preço e Custos (Aba Financeira) --}}
                <div class="pt-4 border-t" style="border-color: var(--color-surface-800);">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-3"
                        style="color: var(--color-primary-400);">3. Formação de Preço & Custos Operacionais</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label" for="valor_cobrado">Valor Cobrado do Cliente (R$) *</label>
                            <input type="number" id="valor_cobrado" name="valor_cobrado"
                                value="{{ old('valor_cobrado', $visita->valor_cobrado) }}" step="0.01" min="0"
                                class="form-input font-bold text-white" required oninput="recalcularFinanceiroVisita()">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="form-label mb-0" for="custo_mao_de_obra">Diária / Mão de Obra Técnico
                                    (R$)</label>
                                <span id="label-diaria-tecnico" class="text-[11px] text-emerald-400 font-medium"></span>
                            </div>
                            <input type="number" id="custo_mao_de_obra" name="custo_mao_de_obra"
                                value="{{ old('custo_mao_de_obra', $visita->custo_mao_de_obra) }}" step="0.01"
                                min="0" class="form-input" oninput="recalcularFinanceiroVisita()">
                        </div>

                        <div>
                            <label class="form-label" for="custo_deslocamento">Deslocamento / Transporte (R$)</label>
                            <input type="number" id="custo_deslocamento" name="custo_deslocamento"
                                value="{{ old('custo_deslocamento', $visita->custo_deslocamento) }}" step="0.01"
                                min="0" class="form-input" oninput="recalcularFinanceiroVisita()">
                        </div>

                        <div>
                            <label class="form-label" for="gastos_adicionais">Gastos Adicionais (R$)</label>
                            <input type="number" id="gastos_adicionais" name="gastos_adicionais"
                                value="{{ old('gastos_adicionais', $visita->gastos_adicionais) }}" step="0.01"
                                min="0" class="form-input" oninput="recalcularFinanceiroVisita()">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="form-label" for="motivo_gastos_adicionais">Motivo dos Gastos Adicionais</label>
                            <input type="text" id="motivo_gastos_adicionais" name="motivo_gastos_adicionais"
                                value="{{ old('motivo_gastos_adicionais', $visita->motivo_gastos_adicionais) }}"
                                class="form-input" placeholder="Ex: Pedágio, compras extras, estacionamento">
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between p-3 rounded-lg"
                        style="background: var(--color-surface-900); border: 1px solid var(--color-surface-800);">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="exige_nota_fiscal" name="exige_nota_fiscal" value="1"
                                {{ old('exige_nota_fiscal', $visita->exige_nota_fiscal) ? 'checked' : '' }}
                                onchange="recalcularFinanceiroVisita()" class="rounded w-4 h-4">
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
                            <span id="preview-custo-insumos" class="text-xs font-semibold text-white">R$
                                {{ number_format($visita->custo_produtos, 2, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-surface-400 block">Custo
                                Operacional</span>
                            <span id="preview-custo-operacional" class="text-xs font-semibold text-white">R$
                                {{ number_format($visita->custoOperacionalTotal(), 2, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-surface-400 block">Impostos
                                (NF)</span>
                            <span id="preview-imposto" class="text-xs font-semibold text-amber-300">R$
                                {{ number_format($visita->valor_imposto, 2, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-surface-400 block">Lucro
                                Estimado</span>
                            <span id="preview-lucro-bruto"
                                class="text-sm font-bold {{ $visita->lucro_bruto >= 0 ? 'text-emerald-400' : 'text-red-400' }}">R$
                                {{ number_format($visita->lucro_bruto, 2, ',', '.') }}</span>
                            <span id="preview-margem"
                                class="text-[10px] block {{ $visita->lucro_bruto >= 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ $visita->margemPercentual() }}%</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-3">
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Salvar Alterações
                    </button>
                    <a href="{{ route('visitas.show', $visita) }}" class="btn btn-secondary">Cancelar</a>
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
        window.__produtosOptions =
            `@foreach ($produtos as $p)<option value="{{ $p->id }}">{{ $p->nome }} (Saldo: {{ number_format($p->quantidade_estoque, 2, ',', '.') }} {{ $p->unidade_armazenamento ?? $p->unidade }} &bull; Dose: {{ $p->unidade_aplicacao ?? 'mL' }})</option>@endforeach`;

        document.addEventListener('DOMContentLoaded', function() {
            const sel = document.getElementById('tecnico_id');
            if (sel && sel.value) {
                const opt = sel.options[sel.selectedIndex];
                const labelDiaria = document.getElementById('label-diaria-tecnico');
                // if (opt && opt.dataset.diaria && labelDiaria) {
                //     labelDiaria.textContent = 'Diária padrão do técnico: R$ ' + parseFloat(opt.dataset.diaria)
                //         .toFixed(2).replace('.', ',');
                // }
            }
        });
    </script>
@endsection
