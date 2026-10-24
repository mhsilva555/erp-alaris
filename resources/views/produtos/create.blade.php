@extends('layouts.app')

@section('title', 'Novo Insumo / Produto')

@section('content')
<div class="max-w-2xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('produtos.store') }}" class="space-y-5">
            @csrf

            <div>
                <label class="form-label" for="nome">Nome Comercial do Insumo *</label>
                <input type="text" id="nome" name="nome" value="{{ old('nome') }}" placeholder="Ex: Fipronil 25CE, Raticida Bloco Parafinado" class="form-input" required>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="form-label" for="natureza">Natureza do Produto *</label>
                    <select id="natureza" name="natureza" class="form-select" required onchange="ajustarNatureza(this.value)">
                        <option value="liquido" {{ old('natureza', 'liquido') === 'liquido' ? 'selected' : '' }}>Líquido (Frasco / Galão)</option>
                        <option value="solido" {{ old('natureza') === 'solido' ? 'selected' : '' }}>Sólido / Pó (Pacote / Saco)</option>
                        <option value="unidade" {{ old('natureza') === 'unidade' ? 'selected' : '' }}>Unidade (Iscas / Peças)</option>
                    </select>
                </div>

                <div>
                    <label class="form-label" for="unidade_armazenamento">Unidade Estoque *</label>
                    <input type="text" id="unidade_armazenamento" name="unidade_armazenamento" value="{{ old('unidade_armazenamento', 'L') }}" class="form-input" placeholder="L, Kg ou Un" required>
                </div>

                <div>
                    <label class="form-label" for="unidade_aplicacao">Unidade Dose (Uso) *</label>
                    <input type="text" id="unidade_aplicacao" name="unidade_aplicacao" value="{{ old('unidade_aplicacao', 'mL') }}" class="form-input" placeholder="mL, g ou Un" required>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label" for="fator_conversao">Fator de Conversão *</label>
                    <input type="number" id="fator_conversao" name="fator_conversao" value="{{ old('fator_conversao', '1000') }}" step="0.0001" min="0.0001" class="form-input" required>
                    <p class="text-[11px] mt-1" style="color: var(--color-surface-400);">Quantidade de doses em 1 unidade de estoque (Ex: 1 L = 1000 mL).</p>
                </div>

                <div>
                    <label class="form-label" for="preco_custo_unitario">Preço de Custo Base (R$) *</label>
                    <input type="number" id="preco_custo_unitario" name="preco_custo_unitario" value="{{ old('preco_custo_unitario', '0.00') }}" step="0.01" min="0" class="form-input" required>
                    <p class="text-[11px] mt-1" style="color: var(--color-surface-400);">Custo de compra por unidade de armazenamento.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label" for="quantidade_estoque">Estoque Inicial Físico *</label>
                    <input type="number" id="quantidade_estoque" name="quantidade_estoque" value="{{ old('quantidade_estoque', '0') }}" step="0.01" min="0" class="form-input" required>
                </div>

                <div>
                    <label class="form-label" for="estoque_minimo">Estoque Mínimo de Alerta *</label>
                    <input type="number" id="estoque_minimo" name="estoque_minimo" value="{{ old('estoque_minimo', '0') }}" step="0.01" min="0" class="form-input" required>
                </div>
            </div>

            <div>
                <label class="form-label" for="descricao">Princípio Ativo / Registro ANVISA / Instruções</label>
                <textarea id="descricao" name="descricao" rows="3" class="form-textarea" placeholder="Princípio ativo, grupo químico, antídoto, lote...">{{ old('descricao') }}</textarea>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Cadastrar Insumo
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
}
</script>
@endsection
