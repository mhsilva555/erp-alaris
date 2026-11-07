@extends('layouts.app')

@section('title', 'Novo Contrato de Prestação de Serviços')

@section('content')
    <div class="max-w-5xl mx-auto space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('contratos.index') }}" class="text-xs font-semibold text-primary-400 hover:underline flex items-center gap-1 mb-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Voltar para Contratos
                </a>
                <h1 class="text-xl font-bold text-white">Cadastrar Novo Contrato</h1>
                <p class="text-xs" style="color: var(--color-surface-400);">Defina as condições comerciais, vigência e periodicidade para geração automática de visitas.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="p-4 rounded-lg bg-red-950/40 border border-red-800 text-red-200 text-sm">
                <p class="font-bold mb-1">Por favor, corrija os erros abaixo:</p>
                <ul class="list-disc pl-5 space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('contratos.store') }}" id="form-contrato" class="space-y-6">
            @csrf

            {{-- 1. Identificação & Cliente --}}
            <div class="card p-6 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-white" style="color: var(--color-primary-400);">
                    1. Identificação do Contrato & Cliente
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="form-label" for="numero">Número / Código do Contrato *</label>
                        <input type="text" id="numero" name="numero" value="{{ old('numero', $proximoNumero) }}" required
                            class="form-input font-mono font-bold" placeholder="Ex: CTR-2026-0001">
                        <span class="form-hint">Identificador único sequencial</span>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="form-label" for="cliente_id">Cliente Vinculado *</label>
                        <select id="cliente_id" name="cliente_id" class="form-select" required>
                            <option value="">Selecione o cliente...</option>
                            @foreach ($clientes as $cli)
                                <option value="{{ $cli->id }}"
                                    {{ old('cliente_id', $clientePreSelecionado?->id) == $cli->id ? 'selected' : '' }}>
                                    {{ $cli->nome }} {{ $cli->documento ? "({$cli->documento})" : '' }} • {{ $cli->cidade_estado }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="form-label" for="titulo">Título / Objeto Resumido</label>
                        <input type="text" id="titulo" name="titulo" value="{{ old('titulo') }}"
                            class="form-input" placeholder="Ex: Controle Integrado de Pragas - Unidade Fabril">
                    </div>

                    <div>
                        <label class="form-label" for="status">Situação Inicial *</label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="ativo" {{ old('status', 'ativo') === 'ativo' ? 'selected' : '' }}>Ativo (Gera agendamentos imediatamente)</option>
                            <option value="rascunho" {{ old('status') === 'rascunho' ? 'selected' : '' }}>Rascunho (Sem geração de visitas)</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- 2. Vigência & Periodicidade de Atendimento --}}
            <div class="card p-6 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-white" style="color: var(--color-primary-400);">
                    2. Vigência & Frequência dos Atendimentos
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="form-label" for="tipo_vigencia">Tipo de Vigência *</label>
                        <select id="tipo_vigencia" name="tipo_vigencia" class="form-select" required onchange="toggleVigencia()">
                            <option value="indeterminado" {{ old('tipo_vigencia', 'indeterminado') === 'indeterminado' ? 'selected' : '' }}>
                                Prazo Indeterminado (Abastecimento contínuo a cada 90 dias)
                            </option>
                            <option value="determinado" {{ old('tipo_vigencia') === 'determinado' ? 'selected' : '' }}>
                                Prazo Determinado (Com data de término)
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="data_inicio">Data de Início da Vigência *</label>
                        <input type="date" id="data_inicio" name="data_inicio" value="{{ old('data_inicio', now()->toDateString()) }}" required
                            class="form-input">
                    </div>

                    <div id="wrapper-data-fim" class="{{ old('tipo_vigencia') === 'determinado' ? '' : 'hidden' }}">
                        <label class="form-label" for="data_fim">Data de Término da Vigência *</label>
                        <input type="date" id="data_fim" name="data_fim" value="{{ old('data_fim') }}"
                            class="form-input">
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3 flex items-center gap-2 pt-1">
                        <input type="checkbox" id="permite_renovacao" name="permite_renovacao" value="1"
                            {{ old('permite_renovacao', '1') ? 'checked' : '' }} class="form-checkbox rounded">
                        <label for="permite_renovacao" class="text-xs text-white cursor-pointer select-none">
                            Permite renovação automática ao término do prazo
                        </label>
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3 border-t pt-4" style="border-color: var(--color-surface-800);"></div>

                    <div>
                        <label class="form-label" for="periodicidade">Periodicidade de Atendimento *</label>
                        <select id="periodicidade" name="periodicidade" class="form-select" required onchange="togglePeriodicidadeConfig()">
                            @foreach (\App\Models\Contrato::PERIODICIDADES as $key => $label)
                                <option value="{{ $key }}" {{ old('periodicidade', 'mensal') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="wrapper-dia-preferencial">
                        <label class="form-label" for="dia_preferencial">Dia Preferencial do Mês (1 a 31)</label>
                        <input type="number" id="dia_preferencial" name="dia_preferencial" min="1" max="31"
                            value="{{ old('dia_preferencial', now()->day) }}" class="form-input">
                        <span class="form-hint">Para meses com menos dias (ex: 28 ou 30), ajusta automaticamente para o último dia do mês.</span>
                    </div>

                    <div id="wrapper-dia-semana" class="hidden">
                        <label class="form-label" for="dia_semana">Dia da Semana Fixo</label>
                        <select id="dia_semana" name="dia_semana" class="form-select">
                            @foreach (\App\Models\Contrato::DIAS_SEMANA as $k => $d)
                                <option value="{{ $k }}" {{ old('dia_semana', 1) == $k ? 'selected' : '' }}>
                                    {{ $d }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="wrapper-intervalo-dias" class="hidden">
                        <label class="form-label" for="periodicidade_dias">Intervalo em Dias *</label>
                        <input type="number" id="periodicidade_dias" name="periodicidade_dias" min="1"
                            value="{{ old('periodicidade_dias', 30) }}" class="form-input" placeholder="Ex: 45">
                        <span class="form-hint">Frequência exata em número de dias entre as visitas</span>
                    </div>

                    <div>
                        <label class="form-label" for="tipo_servico">Linha de Serviço Principal *</label>
                        <select id="tipo_servico" name="tipo_servico" class="form-select" required>
                            @foreach (\App\Models\Visita::TIPOS_SERVICO as $key => $label)
                                <option value="{{ $key }}" {{ old('tipo_servico', 'controle_pragas') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- 3. Serviços Contratados (Itens) --}}
            <div class="card p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-white" style="color: var(--color-primary-400);">
                            3. Serviços Contratados
                        </h3>
                        <p class="text-xs" style="color: var(--color-surface-400);">Especifique os serviços incluídos no contrato e seus respectivos valores.</p>
                    </div>
                    <button type="button" onclick="adicionarLinhaItem()" class="btn btn-secondary btn-sm">
                        + Adicionar Serviço
                    </button>
                </div>

                <div class="table-container">
                    <table class="data-table" id="tabela-itens">
                        <thead>
                            <tr>
                                <th style="width: 45%;">Descrição do Serviço</th>
                                <th style="width: 20%;">Tipo</th>
                                <th style="width: 15%;">Qtd</th>
                                <th style="width: 15%;">Valor Unit. (R$)</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody id="tbody-itens">
                            <tr class="item-row" data-index="0">
                                <td>
                                    <input type="text" name="itens[0][descricao]" required class="form-input text-xs"
                                        placeholder="Ex: Desinsetização e Desratização Geral de Áreas Comuns"
                                        value="{{ old('itens.0.descricao', 'Controle Integrado de Pragas e Vetores') }}">
                                </td>
                                <td>
                                    <select name="itens[0][tipo_servico]" class="form-select text-xs">
                                        <option value="controle_pragas">Controle de Pragas</option>
                                        <option value="limpeza">Higienização e Limpeza</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0.01" name="itens[0][quantidade]" required
                                        class="form-input text-xs item-qtd" value="{{ old('itens.0.quantidade', '1.00') }}"
                                        oninput="recalcularTotais()">
                                </td>
                                <td>
                                    <input type="text" name="itens[0][valor_unitario]" required
                                        class="form-input text-xs item-vlr font-mono" value="{{ old('itens.0.valor_unitario', '350,00') }}"
                                        placeholder="0,00" oninput="recalcularTotais()">
                                </td>
                                <td class="text-center">
                                    <button type="button" onclick="removerLinhaItem(this)" class="btn btn-danger-soft btn-sm p-1" title="Remover item">
                                        &times;
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 4. Condições Comerciais & Faturamento --}}
            <div class="card p-6 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-white" style="color: var(--color-primary-400);">
                    4. Condições Comerciais & Fechamento
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="form-label" for="modelo_cobranca">Modelo de Cobrança *</label>
                        <select id="modelo_cobranca" name="modelo_cobranca" class="form-select" required>
                            @foreach (\App\Models\Contrato::MODELOS_COBRANCA as $k => $l)
                                <option value="{{ $k }}" {{ old('modelo_cobranca', 'mensal') === $k ? 'selected' : '' }}>
                                    {{ $l }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="meio_pagamento_id">Meio de Pagamento</label>
                        <select id="meio_pagamento_id" name="meio_pagamento_id" class="form-select">
                            <option value="">Selecione o meio...</option>
                            @foreach ($meiosPagamento as $meio)
                                <option value="{{ $meio->id }}" {{ old('meio_pagamento_id') == $meio->id ? 'selected' : '' }}>
                                    {{ $meio->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="condicoes_pagamento">Condições de Pagamento</label>
                        <input type="text" id="condicoes_pagamento" name="condicoes_pagamento" value="{{ old('condicoes_pagamento', '') }}"
                            class="form-input" placeholder="Ex: Boleto 15 dias, PIX no ato">
                    </div>

                    <div>
                        <label class="form-label" for="desconto">Desconto Comercial (R$)</label>
                        <input type="text" id="desconto" name="desconto" value="{{ old('desconto', '0,00') }}"
                            class="form-input font-mono" placeholder="0,00" oninput="recalcularTotais()">
                    </div>

                    <div class="sm:col-span-2 lg:col-span-4 p-4 rounded-xl flex items-center justify-between" style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider block" style="color: var(--color-surface-400);">Valor Total do Contrato</span>
                            <span class="text-xs text-slate-400">Total apurado dos serviços contratados subtraindo o desconto comercial</span>
                        </div>
                        <div class="text-2xl font-mono font-bold text-emerald-400" id="display-total">
                            R$ 350,00
                        </div>
                    </div>

                    <div class="sm:col-span-2 lg:col-span-4">
                        <label class="form-label" for="observacoes_comerciais">Observações Comerciais & Faturamento</label>
                        <textarea id="observacoes_comerciais" name="observacoes_comerciais" rows="2" class="form-input"
                            placeholder="Instruções de faturamento, dados de nota fiscal ou acordos específicos...">{{ old('observacoes_comerciais') }}</textarea>
                    </div>

                    <div class="sm:col-span-2 lg:col-span-4">
                        <label class="form-label" for="descricao">Descrição Completa / Cláusulas Adicionais</label>
                        <textarea id="descricao" name="descricao" rows="2" class="form-input"
                            placeholder="Detalhamento operacional, garantias contratadas ou escopo técnico...">{{ old('descricao') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Barra de Submissão --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('contratos.index') }}" class="btn btn-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary px-6">
                    Salvar e Ativar Contrato
                </button>
            </div>
        </form>
    </div>

    <script>
        function toggleVigencia() {
            const tipo = document.getElementById('tipo_vigencia').value;
            const wrapper = document.getElementById('wrapper-data-fim');
            const input = document.getElementById('data_fim');
            if (tipo === 'determinado') {
                wrapper.classList.remove('hidden');
                input.required = true;
            } else {
                wrapper.classList.add('hidden');
                input.required = false;
                input.value = '';
            }
        }

        function togglePeriodicidadeConfig() {
            const p = document.getElementById('periodicidade').value;
            const wDiaPref = document.getElementById('wrapper-dia-preferencial');
            const wDiaSem = document.getElementById('wrapper-dia-semana');
            const wIntervalo = document.getElementById('wrapper-intervalo-dias');

            wDiaPref.classList.add('hidden');
            wDiaSem.classList.add('hidden');
            wIntervalo.classList.add('hidden');

            if (p === 'semanal') {
                wDiaSem.classList.remove('hidden');
            } else if (p === 'personalizado') {
                wIntervalo.classList.remove('hidden');
            } else if (['mensal', 'bimestral', 'trimestral', 'semestral', 'anual'].includes(p)) {
                wDiaPref.classList.remove('hidden');
            }
        }

        let itemIndex = 1;
        function adicionarLinhaItem() {
            const tbody = document.getElementById('tbody-itens');
            const tr = document.createElement('tr');
            tr.className = 'item-row animate-fadeIn';
            tr.dataset.index = itemIndex;
            tr.innerHTML = `
                <td>
                    <input type="text" name="itens[${itemIndex}][descricao]" required class="form-input text-xs"
                        placeholder="Ex: Reforço mensal de baratas e roedores">
                </td>
                <td>
                    <select name="itens[${itemIndex}][tipo_servico]" class="form-select text-xs">
                        <option value="controle_pragas">Controle de Pragas</option>
                        <option value="limpeza">Higienização e Limpeza</option>
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" min="0.01" name="itens[${itemIndex}][quantidade]" required
                        class="form-input text-xs item-qtd" value="1.00" oninput="recalcularTotais()">
                </td>
                <td>
                    <input type="text" name="itens[${itemIndex}][valor_unitario]" required
                        class="form-input text-xs item-vlr font-mono" value="0,00" oninput="recalcularTotais()">
                </td>
                <td class="text-center">
                    <button type="button" onclick="removerLinhaItem(this)" class="btn btn-danger-soft btn-sm p-1" title="Remover item">
                        &times;
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            itemIndex++;
            recalcularTotais();
        }

        function removerLinhaItem(btn) {
            const rows = document.querySelectorAll('#tbody-itens .item-row');
            if (rows.length <= 1) {
                alert('O contrato deve conter pelo menos um serviço.');
                return;
            }
            btn.closest('tr').remove();
            recalcularTotais();
        }

        function parseMoeda(str) {
            if (!str) return 0;
            return parseFloat(str.toString().replace(/\./g, '').replace(',', '.')) || 0;
        }

        function formatarMoeda(num) {
            return num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function recalcularTotais() {
            let subtotal = 0;
            document.querySelectorAll('#tbody-itens .item-row').forEach(row => {
                const qtd = parseFloat(row.querySelector('.item-qtd').value) || 0;
                const vlr = parseMoeda(row.querySelector('.item-vlr').value);
                subtotal += (qtd * vlr);
            });

            const desconto = parseMoeda(document.getElementById('desconto').value);
            const total = Math.max(0, subtotal - desconto);

            document.getElementById('display-total').innerText = 'R$ ' + formatarMoeda(total);
        }

        document.addEventListener('DOMContentLoaded', () => {
            toggleVigencia();
            togglePeriodicidadeConfig();
            recalcularTotais();
        });
    </script>
@endsection
