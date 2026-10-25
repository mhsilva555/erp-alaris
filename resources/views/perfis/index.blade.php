@extends('layouts.app')

@section('title', 'Perfis de Acesso & Permissões')

@section('content')
<div class="space-y-4">

    {{-- Indicadores do Módulo --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="card p-4">
            <p class="text-xs font-medium" style="color: var(--color-surface-400);">Perfis Cadastrados</p>
            <p class="text-2xl font-bold text-white mt-1">{{ $totalPerfis }}</p>
            <span class="text-[11px]" style="color: var(--color-surface-400);">Papéis de acesso no sistema</span>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium text-sky-400">Usuários Vinculados</p>
            <p class="text-2xl font-bold text-sky-300 mt-1">{{ $totalUsuariosVinculados }} <span class="text-xs font-normal" style="color: var(--color-surface-400);">contas</span></p>
            <span class="text-[11px]" style="color: var(--color-surface-400);">Usuários ativos com perfil</span>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium text-amber-400">Permissões Granulares</p>
            <p class="text-2xl font-bold text-amber-300 mt-1">{{ $totalPermissoes }} <span class="text-xs font-normal" style="color: var(--color-surface-400);">ações</span></p>
            <span class="text-[11px]" style="color: var(--color-surface-400);">Controle granular por módulo</span>
        </div>
    </div>

    {{-- Header de Busca e Ação --}}
    <div class="card p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <form method="GET" action="{{ route('perfis.index') }}" class="flex-1 max-w-md">
                <div class="relative">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-surface-400);"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="busca" value="{{ request('busca') }}"
                        placeholder="Buscar por perfil, slug ou descrição..." class="form-input !pl-10">
                </div>
            </form>

            <div class="flex items-center gap-2">
                @if (request('busca'))
                    <a href="{{ route('perfis.index') }}" class="btn btn-secondary text-xs">Limpar</a>
                @endif
                @permissao('perfis.criar')
                    <a href="{{ route('perfis.create') }}" class="btn btn-primary shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Novo Perfil
                    </a>
                @endpermissao
            </div>
        </div>
    </div>

    {{-- Tabela de Perfis --}}
    <div class="card table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Perfil</th>
                    <th>Tipo / Natureza</th>
                    <th class="hidden md:table-cell">Descrição</th>
                    <th class="text-center">Usuários</th>
                    <th class="text-center">Permissões</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($perfis as $perfil)
                    <tr class="animate-fadeIn">
                        <td>
                            <div class="font-medium text-white">{{ $perfil->nome }}</div>
                            <div class="text-xs font-mono mt-0.5" style="color: var(--color-surface-400);">{{ $perfil->slug }}</div>
                        </td>
                        <td>
                            @if ($perfil->is_sistema)
                                <span class="badge badge-info">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                    Sistema
                                </span>
                            @else
                                <span class="badge badge-success">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Personalizado
                                </span>
                            @endif
                        </td>
                        <td class="hidden md:table-cell" style="color: var(--color-surface-300);">
                            {{ $perfil->descricao ?? '—' }}
                        </td>
                        <td class="text-center">
                            <span class="badge badge-neutral text-xs">
                                {{ $perfil->usuarios_count }} {{ $perfil->usuarios_count === 1 ? 'usuário' : 'usuários' }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if ($perfil->slug === 'admin')
                                <span class="badge badge-success text-xs font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Todas (Total)
                                </span>
                            @else
                                <span class="badge badge-neutral text-xs font-semibold">
                                    {{ $perfil->permissoes_count }} ativas
                                </span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                @permissao('perfis.editar')
                                    <a href="{{ route('perfis.edit', $perfil) }}" class="btn btn-secondary btn-sm"
                                        title="Configurar Permissões">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                @endpermissao

                                @if (! $perfil->is_sistema)
                                    @permissao('perfis.deletar')
                                        <form id="delete-perfil-{{ $perfil->id }}"
                                            action="{{ route('perfis.destroy', $perfil) }}" method="POST" class="inline">
                                            @csrf @method('DELETE')
                                        </form>
                                        <button type="button" onclick="confirmDelete('delete-perfil-{{ $perfil->id }}')"
                                            class="btn btn-danger-soft btn-sm" title="Excluir Perfil">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endpermissao
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-12">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <h4 class="empty-state-title">Nenhum perfil encontrado</h4>
                                <p class="empty-state-desc">Cadastre perfis de acesso para delimitar permissões dos usuários do sistema.</p>
                                @permissao('perfis.criar')
                                    <a href="{{ route('perfis.create') }}" class="btn btn-primary btn-sm mt-3">Novo Perfil</a>
                                @endpermissao
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
