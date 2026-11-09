@extends('layouts.app')

@section('title', 'Editar Comodato')

@section('content')
<div class="max-w-2xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('comodatos.update', $comodato) }}" class="space-y-5">
            @csrf @method('PUT')

            <div class="space-y-4">
                @php
                    $clienteAtualId = old('cliente_id', $comodato->cliente_id);
                    $contratoAtualId = old('contrato_id', $comodato->contrato_id);
                    $contratosDoCliente = $clienteAtualId
                        ? $contratos->where('cliente_id', (int) $clienteAtualId)
                        : collect();
                    $contratosJson = $contratos->map(fn ($c) => [
                        'id' => $c->id,
                        'cliente_id' => $c->cliente_id,
                        'periodicidade_dias' => $c->periodicidadeEmDias(),
                        'periodicidade_label' => $c->periodicidadeLabel(),
                        'label' => 'Contrato #' . $c->numero . ' — ' . ($c->titulo ?: $c->cliente->nome) . ' (' . $c->periodicidadeLabel() . ')',
                    ])->values();
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="cliente_id">Cliente Beneficiário *</label>
                        <select id="cliente_id" name="cliente_id" class="form-select" required onchange="aoMudarCliente(this.value)">
                            @foreach($clientes as $cli)
                                <option value="{{ $cli->id }}" {{ $clienteAtualId == $cli->id ? 'selected' : '' }}>
                                    {{ $cli->nome }} {{ $cli->documento ? "({$cli->documento})" : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="contrato_id">Contrato Vinculado (Opcional)</label>
                        <select id="contrato_id" name="contrato_id" class="form-select" {{ !$clienteAtualId ? 'disabled' : '' }} onchange="aoMudarContrato()">
                            <option value="">{{ $clienteAtualId ? 'Nenhum (Comodato Avulso)' : 'Selecione um cliente primeiro...' }}</option>
                            @foreach($contratosDoCliente as $ctr)
                                <option value="{{ $ctr->id }}"
                                    data-cliente="{{ $ctr->cliente_id }}"
                                    data-periodicidade-dias="{{ $ctr->periodicidadeEmDias() }}"
                                    data-periodicidade-label="{{ $ctr->periodicidadeLabel() }}"
                                    {{ $contratoAtualId == $ctr->id ? 'selected' : '' }}>
                                    Contrato #{{ $ctr->numero }} — {{ $ctr->titulo ?: $ctr->cliente->nome }} ({{ $ctr->periodicidadeLabel() }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] mt-1" style="color: var(--color-surface-400);">Lista apenas os contratos ativos do cliente selecionado.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="form-label !mb-0" for="equipamento_id">Equipamento do Inventário</label>
                            <a href="{{ route('equipamentos.create') }}" target="_blank" class="text-xs text-primary-400 hover:underline">+ Novo no Catálogo</a>
                        </div>
                        <select id="equipamento_id" name="equipamento_id" class="form-select">
                            <option value="">Personalizado / Não vinculado</option>
                            @foreach($equipamentos as $eq)
                                @php
                                    $disponivelParaEste = ($comodato->equipamento_id == $eq->id)
                                        ? ($eq->quantidade_disponivel + $comodato->quantidade)
                                        : $eq->quantidade_disponivel;
                                @endphp
                                <option value="{{ $eq->id }}"
                                        data-disponivel="{{ $disponivelParaEste }}"
                                        data-nome="{{ $eq->nome }}{{ $eq->modelo ? ' - ' . $eq->modelo : '' }}"
                                        {{ old('equipamento_id', $comodato->equipamento_id) == $eq->id ? 'selected' : '' }}>
                                    {{ $eq->nome }} {{ $eq->modelo ? "({$eq->modelo})" : '' }} — Disp: {{ $disponivelParaEste }} un
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" id="nome_equipamento" name="nome_equipamento" value="{{ old('nome_equipamento', $comodato->nome_equipamento) }}">
                        <p id="saldo_info" class="text-[11px] mt-1 text-emerald-400 font-medium"></p>
                    </div>

                    <div>
                        <label class="form-label" for="quantidade">Quantidade Instalada *</label>
                        <input type="number" id="quantidade" name="quantidade" value="{{ old('quantidade', $comodato->quantidade) }}" min="1" class="form-input" required>
                        <p id="qtd_erro" class="text-[11px] mt-1 text-red-400 font-medium hidden">A quantidade excede o estoque disponível!</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="produto_id">Insumo Químico Vinculado para Reposição</label>
                        <select id="produto_id" name="produto_id" class="form-select">
                            <option value="">Nenhum produto fixo vinculado...</option>
                            @foreach($produtos as $prod)
                                <option value="{{ $prod->id }}" {{ old('produto_id', $comodato->produto_id) == $prod->id ? 'selected' : '' }}>
                                    {{ $prod->nome }} ({{ $prod->unidade_armazenamento ?? $prod->unidade }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="periodicidade_dias">Ciclo de Reposição (em Dias) *</label>
                        <input type="number" id="periodicidade_dias" name="periodicidade_dias" value="{{ old('periodicidade_dias', $comodato->periodicidade_dias) }}" min="1" class="form-input" required>
                        <p id="periodicidade_hint" class="text-[11px] mt-1" style="color: var(--color-surface-400);"></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="form-label" for="data_instalacao">Data de Instalação *</label>
                        <input type="date" id="data_instalacao" name="data_instalacao" value="{{ old('data_instalacao', $comodato->data_instalacao->format('Y-m-d')) }}" class="form-input" required>
                    </div>

                    <div>
                        <label class="form-label" for="data_ultima_reposicao">Última Reposição</label>
                        <input type="date" id="data_ultima_reposicao" name="data_ultima_reposicao" value="{{ old('data_ultima_reposicao', $comodato->data_ultima_reposicao ? $comodato->data_ultima_reposicao->format('Y-m-d') : '') }}" class="form-input">
                    </div>

                    <div>
                        <label class="form-label" for="data_proxima_reposicao">Próxima Reposição *</label>
                        <input type="date" id="data_proxima_reposicao" name="data_proxima_reposicao" value="{{ old('data_proxima_reposicao', $comodato->data_proxima_reposicao->format('Y-m-d')) }}" class="form-input" required>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="observacoes">Observações Técnicas / Mapeamento dos Pontos</label>
                    <textarea id="observacoes" name="observacoes" rows="3" class="form-textarea">{{ old('observacoes', $comodato->observacoes) }}</textarea>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-3">
                <button type="submit" id="btn_submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Salvar Alterações
                </button>
                <a href="{{ route('comodatos.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const selectEquipamento = document.getElementById('equipamento_id');
        const hiddenNome = document.getElementById('nome_equipamento');
        const inputQtd = document.getElementById('quantidade');
        const saldoInfo = document.getElementById('saldo_info');
        const qtdErro = document.getElementById('qtd_erro');
        const btnSubmit = document.getElementById('btn_submit');

        function atualizarValidacao() {
            const selectedOption = selectEquipamento.options[selectEquipamento.selectedIndex];
            if (!selectedOption || !selectedOption.value) {
                saldoInfo.textContent = '';
                qtdErro.classList.add('hidden');
                inputQtd.removeAttribute('max');
                btnSubmit.removeAttribute('disabled');
                return;
            }

            const disponivel = parseInt(selectedOption.getAttribute('data-disponivel') || '0', 10);
            const nome = selectedOption.getAttribute('data-nome') || '';
            if (nome) hiddenNome.value = nome;

            saldoInfo.textContent = `Saldo disponível para este contrato: ${disponivel} un`;
            inputQtd.max = disponivel;

            const qtd = parseInt(inputQtd.value || '0', 10);
            if (qtd > disponivel) {
                qtdErro.textContent = `A quantidade solicitada (${qtd} un) excede o saldo disponível (${disponivel} un)!`;
                qtdErro.classList.remove('hidden');
                btnSubmit.setAttribute('disabled', 'disabled');
                btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                qtdErro.classList.add('hidden');
                btnSubmit.removeAttribute('disabled');
                btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }

        selectEquipamento.addEventListener('change', atualizarValidacao);
        inputQtd.addEventListener('input', atualizarValidacao);
        atualizarValidacao();

        const todosContratosAtivos = @json($contratosJson);

        window.aoMudarCliente = function(clienteId) {
            const contratoSelect = document.getElementById('contrato_id');
            if (!contratoSelect) return;

            const valorAnterior = contratoSelect.value;
            contratoSelect.innerHTML = '';

            if (!clienteId) {
                const optDefault = document.createElement('option');
                optDefault.value = '';
                optDefault.textContent = 'Selecione um cliente primeiro...';
                contratoSelect.appendChild(optDefault);
                contratoSelect.disabled = true;
                return;
            }

            contratoSelect.disabled = false;
            const optNenhum = document.createElement('option');
            optNenhum.value = '';
            optNenhum.textContent = 'Nenhum (Comodato Avulso)';
            contratoSelect.appendChild(optNenhum);

            const filtrados = todosContratosAtivos.filter(c => String(c.cliente_id) === String(clienteId));
            filtrados.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.setAttribute('data-cliente', c.cliente_id);
                opt.setAttribute('data-periodicidade-dias', c.periodicidade_dias);
                opt.setAttribute('data-periodicidade-label', c.periodicidade_label);
                opt.textContent = c.label;
                if (String(c.id) === String(valorAnterior)) {
                    opt.selected = true;
                }
                contratoSelect.appendChild(opt);
            });

            window.aoMudarContrato();
        };

        window.aoMudarContrato = function() {
            const contratoSelect = document.getElementById('contrato_id');
            const inputPer = document.getElementById('periodicidade_dias');
            const hintPer = document.getElementById('periodicidade_hint');
            if (!contratoSelect || !inputPer) return;

            const opt = contratoSelect.options[contratoSelect.selectedIndex];
            if (opt && opt.value) {
                const dias = opt.getAttribute('data-periodicidade-dias') || '30';
                const label = opt.getAttribute('data-periodicidade-label') || '';
                inputPer.value = dias;
                inputPer.readOnly = true;
                inputPer.classList.add('opacity-70');
                if (hintPer) {
                    hintPer.textContent = `Acompanha automaticamente a periodicidade do contrato (${label} — ${dias} dias).`;
                }
            } else {
                inputPer.readOnly = false;
                inputPer.classList.remove('opacity-70');
                if (hintPer) {
                    hintPer.textContent = 'Campo livre e editável para comodato avulso.';
                }
            }
        };

        const cliSelect = document.getElementById('cliente_id');
        if (cliSelect) {
            window.aoMudarCliente(cliSelect.value);
        }
        window.aoMudarContrato();
    });
</script>
@endsection
