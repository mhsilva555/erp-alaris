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
                        <label class="form-label" for="nome_equipamento">Nome / Tipo do Equipamento *</label>
                        <input type="text" id="nome_equipamento" name="nome_equipamento" value="{{ old('nome_equipamento', $comodato->nome_equipamento) }}" class="form-input" required>
                    </div>

                    <div>
                        <label class="form-label" for="quantidade">Quantidade Instalada *</label>
                        <input type="number" id="quantidade" name="quantidade" value="{{ old('quantidade', $comodato->quantidade) }}" min="1" class="form-input" required>
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
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Salvar Alterações
                </button>
                <a href="{{ route('comodatos.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
