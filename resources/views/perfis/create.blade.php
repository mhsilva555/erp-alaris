@extends('layouts.app')

@section('title', 'Novo Perfil de Acesso')

@section('content')
<div class="space-y-4 max-w-5xl">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-white">Novo Perfil de Acesso</h1>
            <p class="text-xs" style="color: var(--color-surface-400);">
                Defina a identificação do perfil e selecione as permissões granulares em cada módulo.
            </p>
        </div>
        <a href="{{ route('perfis.index') }}" class="btn btn-secondary text-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Voltar
        </a>
    </div>

    <form method="POST" action="{{ route('perfis.store') }}" id="form-perfil" class="space-y-4">
        @csrf

        {{-- Dados Básicos --}}
        <div class="card p-5">
            <h2 class="text-sm font-semibold text-white mb-3">Identificação do Perfil</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label" for="nome">Nome do Perfil *</label>
                    <input type="text" id="nome" name="nome" value="{{ old('nome') }}"
                        class="form-input" placeholder="Ex: Supervisor Operacional, Assistente Comercial..." required>
                    <span class="text-[11px] block mt-1" style="color: var(--color-surface-400);">Nome visível no cadastro de usuários.</span>
                </div>

                <div>
                    <label class="form-label" for="descricao">Descrição / Finalidade</label>
                    <input type="text" id="descricao" name="descricao" value="{{ old('descricao') }}"
                        class="form-input" placeholder="Ex: Gestão de clientes e abertura de OS.">
                    <span class="text-[11px] block mt-1" style="color: var(--color-surface-400);">Resumo das atribuições deste perfil.</span>
                </div>
            </div>
        </div>

        {{-- Barra de Ações Rápidas Global --}}
        <div class="card p-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sticky top-3 z-20">
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-white">Seleção Rápida:</span>
                <span id="contador-selecionadas" class="badge badge-info text-xs font-semibold">0 selecionadas</span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="marcarTodasPermissoes(true)" class="btn btn-secondary btn-sm text-xs">
                    Marcar Todas
                </button>
                <button type="button" onclick="marcarTodasPermissoes(false)" class="btn btn-secondary btn-sm text-xs">
                    Desmarcar Todas
                </button>
                <button type="button" onclick="marcarApenasVisualizacao()" class="btn btn-secondary btn-sm text-xs">
                    Apenas Visualização (Ver)
                </button>
            </div>
        </div>

        {{-- Módulos e Permissões --}}
        <div class="space-y-4">
            @foreach ($catalogo as $moduloChave => $moduloInfo)
                @php
                    $permissoesModulo = $moduloInfo['permissoes'];
                    $chavesComuns = ["{$moduloChave}.ver", "{$moduloChave}.criar", "{$moduloChave}.editar", "{$moduloChave}.deletar", "{$moduloChave}.acessar_dados"];
                    $comuns = array_intersect_key($permissoesModulo, array_flip($chavesComuns));
                    $especificas = array_diff_key($permissoesModulo, array_flip($chavesComuns));
                @endphp

                <div class="card p-5">
                    {{-- Cabeçalho do Bloco --}}
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 mb-4"
                         style="border-bottom: 1px solid var(--color-border);">
                        <div>
                            <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-primary-600"></span>
                                {{ $moduloInfo['nome'] }}
                            </h3>
                            <p class="text-xs mt-0.5" style="color: var(--color-surface-400);">{{ $moduloInfo['descricao'] }}</p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" onclick="toggleModuloPermissoes('{{ $moduloChave }}', true)"
                                class="btn btn-secondary btn-sm text-xs">
                                Marcar Módulo
                            </button>
                            <button type="button" onclick="toggleModuloPermissoes('{{ $moduloChave }}', false)"
                                class="btn btn-ghost btn-sm text-xs">
                                Limpar
                            </button>
                        </div>
                    </div>

                    {{-- Permissões Comuns (5 colunas) --}}
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider block mb-2" style="color: var(--color-surface-400);">
                            Permissões Comuns do Módulo
                        </span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
                            @foreach ($comuns as $chave => $p)
                                @php
                                    $tipo = explode('.', $chave)[1] ?? '';
                                @endphp
                                <label class="p-2.5 rounded-lg border transition flex items-start gap-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-white/5"
                                       style="border-color: var(--color-border); background: var(--color-surface);">
                                    <input type="checkbox" name="permissoes[]" value="{{ $chave }}"
                                        data-modulo="{{ $moduloChave }}"
                                        data-tipo="{{ $tipo }}"
                                        onchange="atualizarContador()"
                                        {{ in_array($chave, old('permissoes', [])) ? 'checked' : '' }}
                                        class="rounded w-4 h-4 mt-0.5 permissao-checkbox"
                                        style="accent-color: var(--color-primary-600);">
                                    <div class="space-y-0.5">
                                        <span class="text-xs font-semibold text-white block">{{ $p['nome'] }}</span>
                                        <span class="text-[11px] block leading-tight" style="color: var(--color-surface-400);">{{ $p['descricao'] }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Permissões Específicas do Módulo --}}
                    @if (! empty($especificas))
                        <div class="mt-4 pt-3.5" style="border-top: 1px solid var(--color-border);">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-500 block mb-2">
                                Ações Específicas
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                                @foreach ($especificas as $chave => $p)
                                    <label class="p-2.5 rounded-lg border transition flex items-start gap-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-white/5"
                                           style="border-color: var(--color-border); background: var(--color-surface);">
                                        <input type="checkbox" name="permissoes[]" value="{{ $chave }}"
                                            data-modulo="{{ $moduloChave }}"
                                            data-tipo="especifica"
                                            onchange="atualizarContador()"
                                            {{ in_array($chave, old('permissoes', [])) ? 'checked' : '' }}
                                            class="rounded w-4 h-4 mt-0.5 permissao-checkbox"
                                            style="accent-color: var(--color-warning-500);">
                                        <div class="space-y-0.5">
                                            <span class="text-xs font-semibold text-white block">{{ $p['nome'] }}</span>
                                            <span class="text-[11px] block leading-tight" style="color: var(--color-surface-400);">{{ $p['descricao'] }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Ações de Envio --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('perfis.index') }}" class="btn btn-secondary">
                Cancelar
            </a>
            <button type="submit" class="btn btn-primary font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Salvar Perfil
            </button>
        </div>
    </form>
</div>

<script>
function atualizarContador() {
    const checkboxes = document.querySelectorAll('.permissao-checkbox');
    const marcadas = document.querySelectorAll('.permissao-checkbox:checked').length;
    const contador = document.getElementById('contador-selecionadas');
    if (contador) {
        contador.textContent = `${marcadas} de ${checkboxes.length} selecionadas`;
    }
}

function marcarTodasPermissoes(status) {
    document.querySelectorAll('.permissao-checkbox').forEach(cb => cb.checked = status);
    atualizarContador();
}

function toggleModuloPermissoes(modulo, status) {
    document.querySelectorAll(`.permissao-checkbox[data-modulo="${modulo}"]`).forEach(cb => cb.checked = status);
    atualizarContador();
}

function marcarApenasVisualizacao() {
    marcarTodasPermissoes(false);
    document.querySelectorAll('.permissao-checkbox[data-tipo="ver"]').forEach(cb => cb.checked = true);
    atualizarContador();
}

document.addEventListener('DOMContentLoaded', atualizarContador);
</script>
@endsection
