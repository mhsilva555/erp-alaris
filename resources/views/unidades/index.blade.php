@extends('layouts.app')

@section('title', 'Unidades da Empresa (Matriz e Filiais)')

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Unidades da Empresa (Matriz & Filiais)
                </h1>
                <p class="text-xs mt-1" style="color: var(--color-surface-400);">
                    Gerencie a Matriz e as Filiais da empresa para emissão personalizada no cabeçalho e rodapé de Propostas, Contratos e O.S.
                </p>
            </div>

            @permissao('unidades.criar')
                <a href="{{ route('unidades.create') }}" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nova Unidade / Filial
                </a>
            @endpermissao
        </div>

        {{-- Cards Executivos --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card">
                <span class="stat-label">Total de Unidades</span>
                <span class="stat-value">{{ $totais['total'] }}</span>
                <span class="text-xs mt-1 block" style="color: var(--color-surface-400);">Cadastradas no sistema</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Matriz</span>
                <span class="stat-value text-sky-400">{{ $totais['matrizes'] }}</span>
                <span class="text-xs mt-1 block" style="color: var(--color-surface-400);">Sede principal</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Filiais</span>
                <span class="stat-value text-indigo-400">{{ $totais['filiais'] }}</span>
                <span class="text-xs mt-1 block" style="color: var(--color-surface-400);">Unidades regionais</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Unidades Ativas</span>
                <span class="stat-value text-emerald-400">{{ $totais['ativas'] }}</span>
                <span class="text-xs mt-1 block" style="color: var(--color-surface-400);">Disponíveis para seleção</span>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('unidades.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                <div class="sm:col-span-2">
                    <label class="form-label text-xs" for="busca">Buscar Unidade</label>
                    <input type="text" id="busca" name="busca" value="{{ request('busca') }}"
                        class="form-input" placeholder="Nome da unidade, razão social, CNPJ ou cidade...">
                </div>
                <div>
                    <label class="form-label text-xs" for="tipo">Tipo de Unidade</label>
                    <select id="tipo" name="tipo" class="form-select">
                        <option value="">Todas (Matriz e Filiais)</option>
                        <option value="matriz" {{ request('tipo') === 'matriz' ? 'selected' : '' }}>Matriz</option>
                        <option value="filial" {{ request('tipo') === 'filial' ? 'selected' : '' }}>Filial</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="btn btn-primary flex-1 justify-center">Filtrar</button>
                    @if (request()->hasAny(['busca', 'tipo']))
                        <a href="{{ route('unidades.index') }}" class="btn btn-secondary">Limpar</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Tabela de Unidades --}}
        <div class="card overflow-hidden">
            @if ($unidades->isEmpty())
                <div class="p-10 text-center space-y-3">
                    <div class="w-12 h-12 rounded-full bg-sky-500/10 text-sky-400 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold text-white text-sm">Nenhuma unidade empresarial encontrada</p>
                        <p class="text-xs mt-1" style="color: var(--color-surface-400);">
                            Cadastre a Matriz e as Filiais para selecionar qual unidade constará nos PDFs de Propostas, Contratos e Ordens de Serviço.
                        </p>
                    </div>
                    @permissao('unidades.criar')
                        <div>
                            <a href="{{ route('unidades.create') }}" class="btn btn-primary btn-sm">
                                Cadastrar Primeira Unidade
                            </a>
                        </div>
                    @endpermissao
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Unidade / Tipo</th>
                                <th>Razão Social / Nome Fantasia</th>
                                <th>CNPJ</th>
                                <th>Cidade / Endereço</th>
                                <th>Contatos</th>
                                <th class="text-center">Vínculos</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($unidades as $unidade)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-white">{{ $unidade->nome }}</span>
                                            @if ($unidade->padrao)
                                                <span class="badge badge-info text-[10px]">Padrão</span>
                                            @endif
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge {{ $unidade->ehMatriz() ? 'badge-cyan' : 'badge-neutral' }} text-[10px]">
                                                {{ $unidade->ehMatriz() ? 'Matriz' : 'Filial' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-semibold text-white text-xs">{{ $unidade->razao_social }}</div>
                                        <div class="text-xs" style="color: var(--color-surface-400);">{{ $unidade->nome_fantasia }}</div>
                                    </td>
                                    <td class="font-semibold text-xs text-white">
                                        {{ $unidade->cnpj_formatado }}
                                    </td>
                                    <td>
                                        <div class="text-xs font-medium text-white">{{ $unidade->cidade_estado }}</div>
                                        <div class="text-xs truncate max-w-xs" style="color: var(--color-surface-400);">
                                            {{ $unidade->endereco ?: '—' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-xs text-white">{{ $unidade->telefone_formatado ?: '—' }}</div>
                                        <div class="text-xs" style="color: var(--color-surface-400);">{{ $unidade->email ?: '—' }}</div>
                                    </td>
                                    <td class="text-center text-xs">
                                        <span class="badge badge-neutral" title="Contratos e Propostas vinculados">
                                            {{ $unidade->contratos_count }} ctr
                                        </span>
                                        <span class="badge badge-neutral ml-1" title="Ordens de Serviço vinculadas">
                                            {{ $unidade->visitas_count }} OS
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($unidade->ativo)
                                            <span class="badge badge-success">Ativa</span>
                                        @else
                                            <span class="badge badge-danger">Inativa</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @permissao('unidades.editar')
                                                <a href="{{ route('unidades.edit', $unidade) }}" class="btn btn-secondary btn-sm">
                                                    Editar
                                                </a>
                                            @endpermissao

                                            @permissao('unidades.deletar')
                                                <form id="delete-unidade-{{ $unidade->id }}"
                                                    action="{{ route('unidades.destroy', $unidade) }}" method="POST"
                                                    class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                        onclick="if(confirm('Deseja remover a unidade {{ addslashes($unidade->nome) }}?')) document.getElementById('delete-unidade-{{ $unidade->id }}').submit();"
                                                        class="btn btn-danger-soft btn-sm" title="Excluir">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endpermissao
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($unidades->hasPages())
                    <div class="p-4 border-t" style="border-color: var(--color-surface-800);">
                        {{ $unidades->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
