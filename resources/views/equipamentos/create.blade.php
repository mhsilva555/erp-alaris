@extends('layouts.app')

@section('title', 'Novo Equipamento')

@section('content')
<div class="max-w-2xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('equipamentos.store') }}" class="space-y-5">
            @csrf

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="nome">Nome do Equipamento *</label>
                        <input type="text" id="nome" name="nome" value="{{ old('nome') }}"
                               placeholder="Ex: Porta-Iscas Túnel Ratoeiro, Armadilha Luminosa 30W" class="form-input" required>
                    </div>

                    <div>
                        <label class="form-label" for="modelo">Modelo / Fabricante</label>
                        <input type="text" id="modelo" name="modelo" value="{{ old('modelo') }}"
                               placeholder="Ex: Modelo T-500, Rogama, Plastcor" class="form-input">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="tipo">Tipo / Categoria *</label>
                        <select id="tipo" name="tipo" class="form-select" required>
                            @foreach($tipos as $valor => $rotulo)
                                <option value="{{ $valor }}" {{ old('tipo') === $valor ? 'selected' : '' }}>{{ $rotulo }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="valor_unitario">Valor Unitário de Custo (R$)</label>
                        <input type="number" step="0.01" min="0" id="valor_unitario" name="valor_unitario"
                               value="{{ old('valor_unitario') }}" placeholder="0,00" class="form-input">
                        <p class="text-[11px] mt-1" style="color: var(--color-surface-400);">Custo de reposição patrimonial.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="quantidade_total">Quantidade Total Adquirida *</label>
                        <input type="number" id="quantidade_total" name="quantidade_total" value="{{ old('quantidade_total', 10) }}" min="0" class="form-input" required>
                        <p class="text-[11px] mt-1" style="color: var(--color-surface-400);">Estoque físico total da empresa.</p>
                    </div>

                    <div>
                        <label class="form-label" for="quantidade_disponivel">Quantidade Disponível Inicial *</label>
                        <input type="number" id="quantidade_disponivel" name="quantidade_disponivel" value="{{ old('quantidade_disponivel', 10) }}" min="0" class="form-input" required>
                        <p class="text-[11px] mt-1" style="color: var(--color-surface-400);">Saldo inicial no depósito pronto para novos comodatos.</p>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="descricao">Descrição / Especificações Técnicas</label>
                    <textarea id="descricao" name="descricao" rows="3" class="form-textarea" placeholder="Ex: Chave de segurança plástica inclusa, bandeja interna removível, etc...">{{ old('descricao') }}</textarea>
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-2 text-sm text-white cursor-pointer">
                        <input type="checkbox" name="ativo" value="1" {{ old('ativo', '1') ? 'checked' : '' }}
                               class="rounded" style="accent-color: var(--color-primary-600);">
                        <span>Equipamento Ativo para novas alocações em Comodato</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4" style="border-top: 1px solid var(--color-surface-800);">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Cadastrar Equipamento
                </button>
                <a href="{{ route('equipamentos.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Sincronizar quantidade disponível inicial com quantidade total quando o usuário digita
    const inputTotal = document.getElementById('quantidade_total');
    const inputDisponivel = document.getElementById('quantidade_disponivel');
    let userEditedDisponivel = false;

    inputDisponivel.addEventListener('input', () => {
        userEditedDisponivel = true;
    });

    inputTotal.addEventListener('input', () => {
        if (!userEditedDisponivel) {
            inputDisponivel.value = inputTotal.value;
        }
    });
</script>
@endsection
