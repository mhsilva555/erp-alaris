@extends('layouts.app')

@section('title', 'Editar Comodato')

@section('content')
<div class="max-w-2xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('comodatos.update', $comodato) }}" class="space-y-5">
            @csrf @method('PUT')

            <div class="space-y-4">
                <div>
                    <label class="form-label" for="cliente_id">Cliente Beneficiário *</label>
                    <select id="cliente_id" name="cliente_id" class="form-select" required>
                        @foreach($clientes as $cli)
                            <option value="{{ $cli->id }}" {{ old('cliente_id', $comodato->cliente_id) == $cli->id ? 'selected' : '' }}>
                                {{ $cli->nome }} {{ $cli->documento ? "({$cli->documento})" : '' }}
                            </option>
                        @endforeach
                    </select>
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
    });
</script>
@endsection
