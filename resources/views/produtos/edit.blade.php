@extends('layouts.app')

@section('title', 'Editar Insumo')

@section('content')
<div class="max-w-2xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('produtos.update', $produto) }}" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <label class="form-label" for="nome">Nome Comercial do Insumo *</label>
                <input type="text" id="nome" name="nome" value="{{ old('nome', $produto->nome) }}" class="form-input" required>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="form-label" for="natureza">Natureza do Produto *</label>
                    <select id="natureza" name="natureza" class="form-select" required onchange="ajustarNatureza(this.value)">
                        <option value="liquido" {{ old('natureza', $produto->natureza) === 'liquido' ? 'selected' : '' }}>Líquido (Frasco / Galão)</option>
                        <option value="solido" {{ old('natureza', $produto->natureza) === 'solido' ? 'selected' : '' }}>Sólido / Pó (Pacote / Saco)</option>
                        <option value="unidade" {{ old('natureza', $produto->natureza) === 'unidade' ? 'selected' : '' }}>Unidade (Iscas / Peças)</option>
                    </select>
                </div>

                @php
                    $unArmVal = old('unidade_armazenamento', $produto->unidade_armazenamento ?? $produto->unidade ?? 'L');
                    $unAplVal = old('unidade_aplicacao', $produto->unidade_aplicacao ?? 'mL');
                    $unidadesPadraoArm = ['L', 'mL', 'Gal', 'Bombona', 'Frasco', 'Kg', 'g', 'Un', 'Cx', 'Pct'];
                    $unidadesPadraoApl = ['mL', 'L', 'g', 'Kg', 'Un', 'Dose', 'Sachê', 'Pastilha', 'Bloco'];
                @endphp
                <div>
                    <label class="form-label" for="unidade_armazenamento">Unidade Estoque *</label>
                    <select id="unidade_armazenamento" name="unidade_armazenamento" class="form-select" required onchange="sugerirFatorConversao()">
                        <optgroup label="Líquidos">
                            <option value="L" {{ $unArmVal === 'L' ? 'selected' : '' }}>L (Litro)</option>
                            <option value="mL" {{ $unArmVal === 'mL' ? 'selected' : '' }}>mL (Mililitro)</option>
                            <option value="Gal" {{ $unArmVal === 'Gal' ? 'selected' : '' }}>Gal (Galão)</option>
                            <option value="Bombona" {{ $unArmVal === 'Bombona' ? 'selected' : '' }}>Bombona</option>
                            <option value="Frasco" {{ $unArmVal === 'Frasco' ? 'selected' : '' }}>Frasco</option>
                        </optgroup>
                        <optgroup label="Sólidos / Pós">
                            <option value="Kg" {{ $unArmVal === 'Kg' ? 'selected' : '' }}>Kg (Quilograma)</option>
                            <option value="g" {{ $unArmVal === 'g' ? 'selected' : '' }}>g (Grama)</option>
                        </optgroup>
                        <optgroup label="Contáveis / Embalagens">
                            <option value="Un" {{ $unArmVal === 'Un' ? 'selected' : '' }}>Un (Unidade)</option>
                            <option value="Cx" {{ $unArmVal === 'Cx' ? 'selected' : '' }}>Cx (Caixa)</option>
                            <option value="Pct" {{ $unArmVal === 'Pct' ? 'selected' : '' }}>Pct (Pacote)</option>
                        </optgroup>
                        @if (!in_array($unArmVal, $unidadesPadraoArm) && !empty($unArmVal))
                            <option value="{{ $unArmVal }}" selected>{{ $unArmVal }}</option>
                        @endif
                    </select>
                </div>

                <div>
                    <label class="form-label" for="unidade_aplicacao">Unidade Dose (Uso) *</label>
                    <select id="unidade_aplicacao" name="unidade_aplicacao" class="form-select" required onchange="sugerirFatorConversao()">
                        <optgroup label="Líquidos">
                            <option value="mL" {{ $unAplVal === 'mL' ? 'selected' : '' }}>mL (Mililitro)</option>
                            <option value="L" {{ $unAplVal === 'L' ? 'selected' : '' }}>L (Litro)</option>
                        </optgroup>
                        <optgroup label="Sólidos">
                            <option value="g" {{ $unAplVal === 'g' ? 'selected' : '' }}>g (Grama)</option>
                            <option value="Kg" {{ $unAplVal === 'Kg' ? 'selected' : '' }}>Kg (Quilograma)</option>
                        </optgroup>
                        <optgroup label="Contáveis / Doses">
                            <option value="Un" {{ $unAplVal === 'Un' ? 'selected' : '' }}>Un (Unidade)</option>
                            <option value="Dose" {{ $unAplVal === 'Dose' ? 'selected' : '' }}>Dose</option>
                            <option value="Sachê" {{ $unAplVal === 'Sachê' ? 'selected' : '' }}>Sachê</option>
                            <option value="Pastilha" {{ $unAplVal === 'Pastilha' ? 'selected' : '' }}>Pastilha</option>
                            <option value="Bloco" {{ $unAplVal === 'Bloco' ? 'selected' : '' }}>Bloco</option>
                        </optgroup>
                        @if (!in_array($unAplVal, $unidadesPadraoApl) && !empty($unAplVal))
                            <option value="{{ $unAplVal }}" selected>{{ $unAplVal }}</option>
                        @endif
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label" for="fator_conversao">Fator de Conversão *</label>
                    <input type="number" id="fator_conversao" name="fator_conversao" value="{{ old('fator_conversao', $produto->fator_conversao ?? 1000) }}" step="0.0001" min="0.0001" class="form-input" required oninput="atualizarAjudaFator()">
                    <p id="fator_help" class="text-[11px] mt-1" style="color: var(--color-surface-400);">Quantidade de doses por 1 unidade de estoque.</p>
                </div>

                <div>
                    <label class="form-label" for="preco_custo_unitario">Preço de Custo Base (R$) *</label>
                    <input type="number" id="preco_custo_unitario" name="preco_custo_unitario" value="{{ old('preco_custo_unitario', $produto->preco_custo_unitario) }}" step="0.01" min="0" class="form-input" required>
                    <p class="text-[11px] mt-1" style="color: var(--color-surface-400);">Custo de compra por unidade de armazenamento.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label" for="quantidade_estoque">Estoque Atual Físico *</label>
                    <input type="number" id="quantidade_estoque" name="quantidade_estoque" value="{{ old('quantidade_estoque', $produto->quantidade_estoque) }}" step="0.01" min="0" class="form-input" required>
                </div>

                <div>
                    <label class="form-label" for="estoque_minimo">Estoque Mínimo de Alerta *</label>
                    <input type="number" id="estoque_minimo" name="estoque_minimo" value="{{ old('estoque_minimo', $produto->estoque_minimo) }}" step="0.01" min="0" class="form-input" required>
                </div>
            </div>

            <div>
                <label class="form-label" for="descricao">Princípio Ativo / Registro ANVISA / Instruções</label>
                <textarea id="descricao" name="descricao" rows="3" class="form-textarea">{{ old('descricao', $produto->descricao) }}</textarea>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Salvar Alterações
                </button>
                <a href="{{ route('produtos.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
function ajustarNatureza(natureza) {
    const unArm = document.getElementById('unidade_armazenamento');
    const unApl = document.getElementById('unidade_aplicacao');
    const fator = document.getElementById('fator_conversao');

    if (natureza === 'liquido') {
        unArm.value = 'L';
        unApl.value = 'mL';
        fator.value = '1000';
    } else if (natureza === 'solido') {
        unArm.value = 'Kg';
        unApl.value = 'g';
        fator.value = '1000';
    } else if (natureza === 'unidade') {
        unArm.value = 'Un';
        unApl.value = 'Un';
        fator.value = '1';
    }
    atualizarAjudaFator();
}

function sugerirFatorConversao() {
    const unArm = document.getElementById('unidade_armazenamento').value;
    const unApl = document.getElementById('unidade_aplicacao').value;
    const fator = document.getElementById('fator_conversao');

    if (unArm === 'L' && unApl === 'mL') {
        fator.value = '1000';
    } else if (unArm === 'Kg' && unApl === 'g') {
        fator.value = '1000';
    } else if (unArm === unApl) {
        fator.value = '1';
    } else if (unArm === 'Gal' && unApl === 'L') {
        fator.value = '5';
    } else if (unArm === 'Gal' && unApl === 'mL') {
        fator.value = '5000';
    }
    atualizarAjudaFator();
}

function atualizarAjudaFator() {
    const unArm = document.getElementById('unidade_armazenamento').value;
    const unApl = document.getElementById('unidade_aplicacao').value;
    const fator = document.getElementById('fator_conversao').value;
    const help = document.getElementById('fator_help');
    if (help) {
        help.textContent = `Quantidade de doses por 1 ${unArm} (Ex: 1 ${unArm} = ${fator || 1000} ${unApl}).`;
    }
}
</script>
@endsection
