@extends('layouts.app')

@section('title', 'Lixeira de Propostas e Contratos')

@section('content')
    <div class="space-y-5">
        {{-- Header & Navegação --}}
        <div class="card p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <a href="{{ route('contratos.index') }}" class="text-xs font-semibold text-primary-400 hover:underline flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Contratos & Propostas
                        </a>
                        <span class="text-xs" style="color: var(--color-surface-600);">/</span>
                        <span class="text-xs font-bold text-white">Lixeira</span>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-lg bg-rose-500/15 text-rose-400 shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-white flex items-center gap-2">
                                Lixeira de Propostas e Contratos
                                <span class="badge badge-danger text-xs">{{ $totalLixeira }}</span>
                            </h1>
                            <p class="text-xs mt-0.5" style="color: var(--color-surface-400);">
                                Registros na lixeira não geram visitas na agenda nem movimentam o caixa. Você pode restaurá-los ou excluí-los permanentemente.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('contratos.index') }}" class="btn btn-secondary btn-sm text-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Voltar aos Contratos
                    </a>

                    @if($totalLixeira > 0)
                        @permissao('contratos.deletar')
                        <form action="{{ route('contratos.esvaziar-lixeira') }}" method="POST" class="inline"
                            onsubmit="return confirm('ATENÇÃO: Deseja esvaziar a lixeira e excluir definitivamente todos os registros que não possuem atendimentos executados? Esta ação é irreversível.');">
                            @csrf
                            <button type="submit" class="btn btn-danger-soft btn-sm text-xs font-semibold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Esvaziar Lixeira
                            </button>
                        </form>
                        @endpermissao
                    @endif
                </div>
            </div>
        </div>

        {{-- Filtros da Lixeira --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('contratos.lixeira') }}"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                <div class="relative sm:col-span-2 lg:col-span-6 xl:col-span-4">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-surface-400);"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="busca" value="{{ request('busca') }}"
                        placeholder="Buscar por número, título ou cliente..." class="form-input !pl-10">
                </div>

                <div class="lg:col-span-4 xl:col-span-3">
                    <select name="tipo" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos os tipos...</option>
                        <option value="proposta" {{ request('tipo') === 'proposta' ? 'selected' : '' }}>Apenas Propostas Comerciais</option>
                        <option value="contrato" {{ request('tipo') === 'contrato' ? 'selected' : '' }}>Apenas Contratos</option>
                    </select>
                </div>

                <div class="sm:col-span-2 lg:col-span-2 xl:col-span-5 flex items-center justify-end gap-1.5">
                    @if (request()->hasAny(['busca', 'tipo']))
                        <a href="{{ route('contratos.lixeira') }}" class="btn btn-outline btn-sm py-2 px-2.5 text-xs shrink-0" title="Limpar filtros">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Limpar
                        </a>
                    @endif
                    <button type="submit" class="btn btn-secondary btn-sm text-xs">
                        Filtrar
                    </button>
                </div>
            </form>
        </div>

        {{-- Tabela de Itens na Lixeira --}}
        <div class="card table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Número / Título</th>
                        <th>Tipo</th>
                        <th>Cliente</th>
                        <th>Periodicidade</th>
                        <th>Valor & Cobrança</th>
                        <th>Removido em</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contratos as $contrato)
                        <tr class="animate-fadeIn">
                            <td class="whitespace-nowrap">
                                <a href="{{ route('contratos.show', $contrato->id) }}" class="font-bold text-white hover:underline text-sm block">
                                    {{ $contrato->numero }}
                                </a>
                                @if($contrato->titulo)
                                    <span class="text-xs truncate block max-w-[200px]" style="color: var(--color-surface-400);">{{ $contrato->titulo }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">
                                @if($contrato->ehProposta())
                                    <span class="badge badge-warning text-xs">Proposta Comercial</span>
                                @else
                                    <span class="badge badge-neutral text-xs">Contrato ({{ $contrato->statusLabel() }})</span>
                                @endif
                            </td>
                            <td>
                                @if($contrato->cliente)
                                    <a href="{{ route('clientes.show', $contrato->cliente) }}" class="font-medium text-white hover:underline block">
                                        {{ $contrato->cliente->nome }}
                                    </a>
                                    <span class="text-xs" style="color: var(--color-surface-400);">{{ $contrato->cliente->cidade_estado }}</span>
                                @else
                                    <span class="text-xs" style="color: var(--color-surface-500);">(Cliente não encontrado)</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-neutral text-xs">{{ $contrato->periodicidadeLabel() }}</span>
                                <div class="text-[11px] mt-0.5" style="color: var(--color-surface-400);">
                                    {{ $contrato->tipoServicoLabel() }}
                                </div>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="font-bold text-white text-sm">
                                    R$ {{ number_format($contrato->valor_total, 2, ',', '.') }}
                                </div>
                                <span class="text-[11px] block" style="color: var(--color-surface-400);">{{ $contrato->modeloCobrancaLabel() }}</span>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="text-xs text-rose-300 font-medium">
                                    {{ $contrato->deleted_at?->format('d/m/Y H:i') }}
                                </div>
                                <span class="text-[10px]" style="color: var(--color-surface-400);">
                                    {{ $contrato->deleted_at?->diffForHumans() }}
                                </span>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    @permissao('contratos.deletar')
                                    {{-- Restaurar --}}
                                    <form action="{{ route('contratos.restaurar', $contrato->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm text-xs font-bold"
                                            title="Restaurar este registro da lixeira">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            Restaurar
                                        </button>
                                    </form>

                                    {{-- Excluir Definitivamente --}}
                                    <form action="{{ route('contratos.force-delete', $contrato->id) }}" method="POST" class="inline"
                                        onsubmit="return confirm('ATENÇÃO: Deseja excluir definitivamente o registro {{ $contrato->numero }}? Esta ação não pode ser desfeita.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger-soft btn-sm"
                                            title="Excluir permanentemente do banco de dados">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                    @endpermissao

                                    {{-- Ver detalhes --}}
                                    <a href="{{ route('contratos.show', $contrato->id) }}" class="btn btn-secondary btn-sm text-xs" title="Ver detalhes do registro">
                                        Detalhes
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state py-10">
                                    <div class="empty-state-icon text-slate-500">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </div>
                                    <h4 class="empty-state-title">A lixeira está vazia</h4>
                                    <p class="empty-state-desc">Nenhuma proposta comercial ou contrato foi movido para a lixeira.</p>
                                    <a href="{{ route('contratos.index') }}" class="btn btn-secondary btn-sm mt-3">
                                        Voltar aos Contratos Ativos
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginação --}}
        @if($contratos->hasPages())
            <div class="mt-4">
                {{ $contratos->links() }}
            </div>
        @endif
    </div>
@endsection
