@extends('layouts.app')

@section('title', 'Alocar Equipamento em Comodato')

@section('content')
<div class="max-w-2xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('comodatos.store') }}" class="space-y-5">
            @csrf

            <div class="space-y-4">
                <div>
                    <label class="form-label" for="cliente_id">Cliente Beneficiário *</label>
                    <select id="cliente_id" name="cliente_id" class="form-select" required>
                        <option value="">Selecione o cliente...</option>
                        @foreach($clientes as $cli)
                            <option value="{{ $cli->id }}" {{ (old('cliente_id', $clienteId) == $cli->id) ? 'selected' : '' }}>
                                {{ $cli->nome }} {{ $cli->documento ? "({$cli->documento})" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="form-label !mb-0" for="equipamento_id">Equipamento do Inventário *</label>
                            <a href="{{ route('equipamentos.create') }}" target="_blank" class="text-xs text-primary-400 hover:underline">+ Novo no Catálogo</a>
                        </div>
                        <select id="equipamento_id" name="equipamento_id" class="form-select" required>
                            <option value="">Selecione um equipamento...</option>
                            @foreach($equipamentos as $eq)
                                <option value="{{ $eq->id }}"
                                        data-disponivel="{{ $eq->quantidade_disponivel }}"
                                        data-nome="{{ $eq->nome }}{{ $eq->modelo ? ' - ' . $eq->modelo : '' }}"
                                        {{ (old('equipamento_id', request('equipamento_id')) == $eq->id) ? 'selected' : '' }}>
                                    {{ $eq->nome }} {{ $eq->modelo ? "({$eq->modelo})" : '' }} — Disp: {{ $eq->quantidade_disponivel }} un
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" id="nome_equipamento" name="nome_equipamento" value="{{ old('nome_equipamento') }}">
                        <p id="saldo_info" class="text-[11px] mt-1 text-emerald-400 font-medium"></p>
                    </div>

                    <div>
                        <label class="form-label" for="quantidade">Quantidade a Alocar *</label>
                        <input type="number" id="quantidade" name="quantidade" value="{{ old('quantidade', 1) }}" min="1" class="form-input" required>
                        <p id="qtd_erro" class="text-[11px] mt-1 text-red-400 font-medium hidden">A quantidade excede o estoque disponível!</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="produto_id">Insumo Químico Vinculado para Reposição</label>
                        <select id="produto_id" name="produto_id" class="form-select">
                            <option value="">Nenhum produto fixo vinculado...</option>
                            @foreach($produtos as $prod)
                                <option value="{{ $prod->id }}" {{ old('produto_id') == $prod->id ? 'selected' : '' }}>
                                    {{ $prod->nome }} ({{ $prod->unidade_armazenamento ?? $prod->unidade }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] mt-1" style="color: var(--color-surface-400);">Insumo que sairá do estoque na data de troca das iscas.</p>
                    </div>

                    <div>
                        <label class="form-label" for="periodicidade_dias">Ciclo de Reposição (em Dias) *</label>
                        <input type="number" id="periodicidade_dias" name="periodicidade_dias" value="{{ old('periodicidade_dias', 15) }}" min="1" class="form-input" required>
                        <p class="text-[11px] mt-1" style="color: var(--color-surface-400);">Ex: 15 dias, 30 dias. Campo livre e editável.</p>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="data_instalacao">Data de Implantação / Instalação *</label>
                    <input type="date" id="data_instalacao" name="data_instalacao" value="{{ old('data_instalacao', now()->toDateString()) }}" class="form-input" required>
                </div>

                <div>
                    <label class="form-label" for="observacoes">Observações Técnicas / Mapeamento dos Pontos</label>
                    <textarea id="observacoes" name="observacoes" rows="3" class="form-textarea" placeholder="Ex: Pontos 01 a 10 no muro perimetral externo, pontos 11 e 12 na casa de máquinas...">{{ old('observacoes') }}</textarea>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-3">
                <button type="submit" id="btn_submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Cadastrar Comodato
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
            hiddenNome.value = nome;

            saldoInfo.textContent = `Saldo disponível no inventário: ${disponivel} un`;
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
