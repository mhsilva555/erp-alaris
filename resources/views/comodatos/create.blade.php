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
                        <label class="form-label" for="nome_equipamento">Nome / Tipo do Equipamento *</label>
                        <input type="text" id="nome_equipamento" name="nome_equipamento" value="{{ old('nome_equipamento') }}"
                               placeholder="Ex: Porta-iscas modelo túnel, Armadilha Luminosa" class="form-input" required>
                    </div>

                    <div>
                        <label class="form-label" for="quantidade">Quantidade Instalada *</label>
                        <input type="number" id="quantidade" name="quantidade" value="{{ old('quantidade', 10) }}" min="1" class="form-input" required>
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
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Cadastrar Comodato
                </button>
                <a href="{{ route('comodatos.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
