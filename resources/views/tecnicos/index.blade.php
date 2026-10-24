@extends('layouts.app')

@section('title', 'Técnicos')

@section('content')
    <div class="space-y-4">
        <div class="card p-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <form method="GET" action="{{ route('tecnicos.index') }}" class="flex-1 max-w-md">
                    <div class="relative">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-surface-400);"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" name="busca" value="{{ request('busca') }}"
                            placeholder="Buscar técnicos por nome ou especialidade..." class="form-input !pl-10">
                    </div>
                </form>
                <a href="{{ route('tecnicos.create') }}" class="btn btn-primary shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Novo Técnico
                </a>
            </div>
        </div>

        <div class="card table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th class="hidden sm:table-cell">Telefone</th>
                        <th class="hidden md:table-cell">E-mail</th>
                        <th class="hidden lg:table-cell">Especialidade</th>
                        <th>Status</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tecnicos as $tecnico)
                        <tr class="animate-fadeIn">
                            <td class="font-medium text-white">{{ $tecnico->nome }}</td>
                            <td class="hidden sm:table-cell" style="color: var(--color-surface-300);">
                                {{ $tecnico->telefone ?? '—' }}</td>
                            <td class="hidden md:table-cell" style="color: var(--color-surface-300);">
                                {{ $tecnico->email ?? '—' }}</td>
                            <td class="hidden lg:table-cell" style="color: var(--color-surface-300);">
                                {{ $tecnico->especialidade ?? '—' }}</td>
                            <td>
                                @if ($tecnico->ativo)
                                    <span class="badge badge-success">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Ativo
                                    </span>
                                @else
                                    <span class="badge badge-danger">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Inativo
                                    </span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('tecnicos.edit', $tecnico) }}" class="btn btn-secondary btn-sm"
                                        title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <form id="delete-tecnico-{{ $tecnico->id }}"
                                        action="{{ route('tecnicos.destroy', $tecnico) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                    </form>
                                    <button onclick="confirmDelete('delete-tecnico-{{ $tecnico->id }}')"
                                        class="btn btn-danger-soft btn-sm" title="Excluir">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
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
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zm-4 7a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <h4 class="empty-state-title">Nenhum técnico encontrado</h4>
                                    <p class="empty-state-desc">Cadastre operadores e aplicadores para alocar visitas e
                                        ordens de serviço.</p>
                                    <a href="{{ route('tecnicos.create') }}" class="btn btn-primary btn-sm mt-3">Cadastrar
                                        Técnico</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tecnicos->hasPages())
            <div class="pagination mt-4">
                {{ $tecnicos->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
