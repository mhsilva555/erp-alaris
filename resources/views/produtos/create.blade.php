@extends('layouts.app')

@section('title', 'Novo Insumo / Produto')

@section('content')
<div class="max-w-3xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('produtos.store') }}" class="space-y-6">
            @csrf

            {{-- 1. Identificação e Unidades de Medida --}}
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider mb-3" style="color: var(--color-primary-600);">
                    1. Identificação & Unidades de Medida
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="form-label" for="nome">Nome Comercial do Insumo *</label>
                        <input type="text" id="nome" name="nome" value="{{ old('nome') }}"
                            placeholder="Ex: Fipronil 25CE, Raticida Bloco Parafinado, Isca Gel Barata"
                            class="form-input" required autofocus>
                        <p class="form-hint">Nome exibido na lista de insumos e no seletor da Ordem de Serviço.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label" for="natureza">Tipo de Produto *</label>
                            <select id="natureza" name="natureza" class="form-select" required onchange="ajustarNatureza(this.value)">
                                <option value="liquido" {{ old('natureza', 'liquido') === 'liquido' ? 'selected' : '' }}>💧 Líquido (Frasco / Galão)</option>
                                <option value="solido" {{ old('natureza') === 'solido' ? 'selected' : '' }}>🧊 Sólido / Pó (Pacote / Saco)</option>
                                <option value="unidade" {{ old('natureza') === 'unidade' ? 'selected' : '' }}>📦 Unidade / Pacote (Peças / Iscas)</option>
                            </select>
                            <p class="form-hint">Define o comportamento de dosagem.</p>
                        </div>

                        <div>
                            <label class="form-label" for="unidade_armazenamento">Unidade do Estoque *</label>
                            <select id="unidade_armazenamento" name="unidade_armazenamento" class="form-select" required onchange="sugerirFatorConversao()">
                                <optgroup label="Líquidos">
                                    <option value="L" {{ old('unidade_armazenamento', 'L') === 'L' ? 'selected' : '' }}>L (Litro)</option>
                                    <option value="mL" {{ old('unidade_armazenamento') === 'mL' ? 'selected' : '' }}>mL (Mililitro)</option>
                                    <option value="Gal" {{ old('unidade_armazenamento') === 'Gal' ? 'selected' : '' }}>Gal (Galão)</option>
                                    <option value="Bombona" {{ old('unidade_armazenamento') === 'Bombona' ? 'selected' : '' }}>Bombona</option>
                                    <option value="Frasco" {{ old('unidade_armazenamento') === 'Frasco' ? 'selected' : '' }}>Frasco</option>
                                </optgroup>
                                <optgroup label="Sólidos / Pós">
                                    <option value="Kg" {{ old('unidade_armazenamento', 'Kg') === 'Kg' ? 'selected' : '' }}>Kg (Quilograma)</option>
                                    <option value="g" {{ old('unidade_armazenamento') === 'g' ? 'selected' : '' }}>g (Grama)</option>
                                    <option value="Saco" {{ old('unidade_armazenamento') === 'Saco' ? 'selected' : '' }}>Saco</option>
                                </optgroup>
                                <optgroup label="Contáveis / Embalagens">
                                    <option value="Un" {{ old('unidade_armazenamento', 'Un') === 'Un' ? 'selected' : '' }}>Un (Unidade)</option>
                                    <option value="Pct" {{ old('unidade_armazenamento') === 'Pct' ? 'selected' : '' }}>Pct (Pacote)</option>
                                    <option value="Cx" {{ old('unidade_armazenamento') === 'Cx' ? 'selected' : '' }}>Cx (Caixa)</option>
                                </optgroup>
                            </select>
                            <p class="form-hint">Como você compra e armazena.</p>
                        </div>

                        <div>
                            <label class="form-label" for="unidade_aplicacao">Unidade de Uso na OS *</label>
                            <select id="unidade_aplicacao" name="unidade_aplicacao" class="form-select" required onchange="sugerirFatorConversao()">
                                <optgroup label="Líquidos">
                                    <option value="mL" {{ old('unidade_aplicacao', 'mL') === 'mL' ? 'selected' : '' }}>mL (Mililitro)</option>
                                    <option value="L" {{ old('unidade_aplicacao') === 'L' ? 'selected' : '' }}>L (Litro)</option>
                                </optgroup>
                                <optgroup label="Sólidos">
                                    <option value="g" {{ old('unidade_aplicacao') === 'g' ? 'selected' : '' }}>g (Grama)</option>
                                    <option value="Kg" {{ old('unidade_aplicacao') === 'Kg' ? 'selected' : '' }}>Kg (Quilograma)</option>
                                </optgroup>
                                <optgroup label="Contáveis / Doses">
                                    <option value="Un" {{ old('unidade_aplicacao', 'Un') === 'Un' ? 'selected' : '' }}>Un (Unidade)</option>
                                    <option value="Pct" {{ old('unidade_aplicacao') === 'Pct' ? 'selected' : '' }}>Pct (Pacote)</option>
                                    <option value="Cx" {{ old('unidade_aplicacao') === 'Cx' ? 'selected' : '' }}>Cx (Caixa)</option>
                                    <option value="Bloco" {{ old('unidade_aplicacao') === 'Bloco' ? 'selected' : '' }}>Bloco</option>
                                    <option value="Sachê" {{ old('unidade_aplicacao') === 'Sachê' ? 'selected' : '' }}>Sachê</option>
                                    <option value="Pastilha" {{ old('unidade_aplicacao') === 'Pastilha' ? 'selected' : '' }}>Pastilha</option>
                                    <option value="Dose" {{ old('unidade_aplicacao') === 'Dose' ? 'selected' : '' }}>Dose</option>
                                </optgroup>
                            </select>
                            <p class="form-hint">Como o técnico lança na OS.</p>
                        </div>
                    </div>

                    {{-- Card Informativo Padronizado --}}
                    <div id="card-explicacao-unidades" class="alert alert-info !items-start gap-3">
                        <span class="text-base shrink-0 mt-0.5">💡</span>
                        <div class="text-xs leading-relaxed space-y-1">
                            <span class="font-semibold block text-ink-900">Como funcionará o controle deste produto:</span>
                            <p id="texto-explicacao-dinamico" class="text-ink-700"></p>
                        </div>
                    </div>

                    {{-- Opção Avançada de Fator de Conversão --}}
                    <div class="pt-1">
                        <button type="button" onclick="toggleConfigAvancada()" class="text-xs font-medium inline-flex items-center gap-1.5" style="color: var(--color-primary-600);">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span id="label-toggle-avancado">Opções avançadas (personalizar fator de conversão)</span>
                        </button>
                        <div id="painel-fator-avancado" class="hidden mt-3 pt-3 border-t" style="border-color: var(--color-border);">
                            <div class="max-w-xs">
                                <label class="form-label" for="fator_conversao">Fator de Conversão Numérico</label>
                                <input type="number" id="fator_conversao" name="fator_conversao" value="{{ old('fator_conversao', '1000') }}"
                                    step="0.0001" min="0.0001" class="form-input" oninput="atualizarCardExplicacao()">
                                <p id="fator_help" class="form-hint">Quantidade de doses por 1 unidade de estoque.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Saldo em Estoque & Preço de Custo --}}
            <div class="pt-4 border-t" style="border-color: var(--color-border);">
                <h3 class="text-xs font-bold uppercase tracking-wider mb-3" style="color: var(--color-primary-600);">
                    2. Saldo em Estoque & Preço de Custo
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    {{-- Estoque Inicial --}}
                    <div>
                        <label class="form-label" for="quantidade_estoque">Estoque Inicial Físico *</label>
                        <div class="relative">
                            <input type="number" id="quantidade_estoque" name="quantidade_estoque"
                                value="{{ old('quantidade_estoque', '0') }}" step="any" min="0"
                                class="form-input !pr-14" required>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-500 pointer-events-none badge-unidade-armazenamento">
                                L
                            </span>
                        </div>
                        <p class="form-hint">Saldo inicial disponível em <span class="badge-unidade-armazenamento">L</span>.</p>
                    </div>

                    {{-- Estoque Mínimo --}}
                    <div>
                        <label class="form-label" for="estoque_minimo">Estoque Mínimo de Alerta *</label>
                        <div class="relative">
                            <input type="number" id="estoque_minimo" name="estoque_minimo"
                                value="{{ old('estoque_minimo', '0') }}" step="any" min="0"
                                class="form-input !pr-14" required>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-500 pointer-events-none badge-unidade-armazenamento">
                                L
                            </span>
                        </div>
                        <p class="form-hint">Alerta quando o saldo for menor ou igual.</p>
                    </div>

                    {{-- Preço de Custo Base --}}
                    @permissao('produtos.acessar_dados')
                        <div>
                            <label class="form-label" for="preco_custo_unitario">Preço de Custo Base (R$) *</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500 pointer-events-none">
                                    R$
                                </span>
                                <input type="number" id="preco_custo_unitario" name="preco_custo_unitario"
                                    value="{{ old('preco_custo_unitario', '0.00') }}" step="0.01" min="0"
                                    class="form-input !pl-9 !pr-14" required>
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500 pointer-events-none">
                                    / <span class="badge-unidade-armazenamento">L</span>
                                </span>
                            </div>
                            <p class="form-hint">Custo pago por cada 1 <span class="badge-unidade-armazenamento">L</span>.</p>
                        </div>
                    @else
                        <input type="hidden" name="preco_custo_unitario" value="{{ old('preco_custo_unitario', '0.00') }}">
                    @endpermissao
                </div>
            </div>

            {{-- 3. Informações Regulatórias / Princípio Ativo --}}
            <div class="pt-4 border-t" style="border-color: var(--color-border);">
                <h3 class="text-xs font-bold uppercase tracking-wider mb-3" style="color: var(--color-primary-600);">
                    3. Detalhes & Registro Técnico
                </h3>

                <div>
                    <label class="form-label" for="descricao">Princípio Ativo / Registro ANVISA / Instruções</label>
                    <textarea id="descricao" name="descricao" rows="3" class="form-textarea"
                        placeholder="Princípio ativo, grupo químico, antídoto, número de lote, instruções de segurança...">{{ old('descricao') }}</textarea>
                </div>
            </div>

            {{-- Botões de Ação --}}
            <div class="flex items-center gap-3 pt-3 border-t" style="border-color: var(--color-border);">
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

    if (natureza === 'liquido') {
        unArm.value = 'L';
        unApl.value = 'mL';
    } else if (natureza === 'solido') {
        unArm.value = 'Kg';
        unApl.value = 'g';
    } else if (natureza === 'unidade') {
        unArm.value = 'Un';
        unApl.value = 'Un';
    }

    sugerirFatorConversao();
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
    } else if (unArm === 'Bombona' && unApl === 'L') {
        fator.value = '20';
    } else if (unArm === 'Bombona' && unApl === 'mL') {
        fator.value = '20000';
    } else if ((unArm === 'Pct' || unArm === 'Cx') && (unApl === 'Un' || unApl === 'Bloco' || unApl === 'Sachê')) {
        if (!fator.value || fator.value === '1000' || fator.value === '1') {
            fator.value = '10';
        }
    } else {
        if (!fator.value || parseFloat(fator.value) <= 0) {
            fator.value = '1';
        }
    }

    atualizarBadgesUnidade();
    atualizarCardExplicacao();
}

function atualizarBadgesUnidade() {
    const unArm = document.getElementById('unidade_armazenamento').value;
    document.querySelectorAll('.badge-unidade-armazenamento').forEach(el => {
        el.textContent = unArm;
    });
}

function atualizarCardExplicacao() {
    const unArm = document.getElementById('unidade_armazenamento').value;
    const unApl = document.getElementById('unidade_aplicacao').value;
    const fatorVal = parseFloat(document.getElementById('fator_conversao').value) || 1;
    const textoEl = document.getElementById('texto-explicacao-dinamico');
    const fatorHelp = document.getElementById('fator_help');

    if (fatorHelp) {
        fatorHelp.textContent = `1 ${unArm} de estoque equivale a ${fatorVal.toLocaleString('pt-BR')} ${unApl} de uso na OS.`;
    }

    if (!textoEl) return;

    if (unArm === 'L' && unApl === 'mL' && fatorVal === 1000) {
        textoEl.innerHTML = `• Controle do estoque e preço em <strong>Litros (${unArm})</strong>.<br>
            • Na Ordem de Serviço (OS), você informa a dosagem em <strong>Mililitros (${unApl})</strong>.<br>
            • <strong>Exemplo:</strong> Se você tiver <strong>1 L</strong> no estoque e usar <strong>50 mL</strong> na OS, o estoque baixará automaticamente <strong>0,05 L</strong> (restando 0,95 L) com custo proporcional.`;
    } else if (unArm === 'Kg' && unApl === 'g' && fatorVal === 1000) {
        textoEl.innerHTML = `• Controle do estoque e preço em <strong>Quilogramas (${unArm})</strong>.<br>
            • Na Ordem de Serviço (OS), você informa a dosagem em <strong>Gramas (${unApl})</strong>.<br>
            • <strong>Exemplo:</strong> Se você tiver <strong>1 Kg</strong> no estoque e usar <strong>100 g</strong> na OS, o estoque baixará <strong>0,10 Kg</strong> (restando 0,90 Kg).`;
    } else if (unArm === unApl) {
        textoEl.innerHTML = `• Controle de estoque e aplicação na mesma medida: <strong>${unArm}</strong>.<br>
            • Na Ordem de Serviço (OS), você pode lançar tanto números inteiros quanto <strong>fracionados</strong>.<br>
            • <strong>Exemplo:</strong> Se você tiver <strong>1 ${unArm}</strong> e usar meio ${unArm.toLowerCase()} (digite <strong>0,5</strong>), o estoque baixará <strong>0,5 ${unArm}</strong> (restando 0,5 ${unArm}).`;
    } else {
        const fracaoExemplo = (1 / fatorVal).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
        textoEl.innerHTML = `• 1 ${unArm} em estoque equivale a <strong>${fatorVal.toLocaleString('pt-BR')} ${unApl}</strong> na OS.<br>
            • Ao usar 1 ${unApl} na OS, o estoque baixará <strong>${fracaoExemplo} ${unArm}</strong>. Valores fracionados (como 0,5) são calculados com precisão.`;
    }
}

function toggleConfigAvancada() {
    const painel = document.getElementById('painel-fator-avancado');
    const label = document.getElementById('label-toggle-avancado');
    if (!painel) return;

    if (painel.classList.contains('hidden')) {
        painel.classList.remove('hidden');
        if (label) label.textContent = 'Ocultar opções avançadas de fator';
    } else {
        painel.classList.add('hidden');
        if (label) label.textContent = 'Opções avançadas (personalizar fator de conversão)';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    atualizarBadgesUnidade();
    atualizarCardExplicacao();
});
</script>
@endsection
