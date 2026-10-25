@extends('layouts.app')

@section('title', 'Clientes & Funil de Prospecção')

@section('content')
    <div class="space-y-4">
        {{-- Header e Filtros --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('clientes.index') }}"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                <div class="relative sm:col-span-2 lg:col-span-4">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-surface-400);"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="busca" value="{{ request('busca') }}"
                        placeholder="Buscar por nome, CPF/CNPJ, telefone ou cidade..." class="form-input !pl-10">
                </div>
                <div class="lg:col-span-2">
                    <select name="status_prospeccao" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos os status...</option>
                        <option value="ativo" {{ request('status_prospeccao') === 'ativo' ? 'selected' : '' }}>Cliente Ativo</option>
                        <option value="em_negociacao" {{ request('status_prospeccao') === 'em_negociacao' ? 'selected' : '' }}>Em Negociação</option>
                        <option value="prospeccao" {{ request('status_prospeccao') === 'prospeccao' ? 'selected' : '' }}>Prospecção Ativa</option>
                        <option value="retornar_3_meses" {{ request('status_prospeccao') === 'retornar_3_meses' ? 'selected' : '' }}>Retornar em 3 Meses</option>
                        <option value="contrato_concorrente" {{ request('status_prospeccao') === 'contrato_concorrente' ? 'selected' : '' }}>Contrato Concorrente</option>
                        <option value="inativo" {{ request('status_prospeccao') === 'inativo' ? 'selected' : '' }}>Inativo</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <select name="periodicidade" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas as periodicidades...</option>
                        <option value="avulso" {{ request('periodicidade') === 'avulso' ? 'selected' : '' }}>Avulso / Pontual</option>
                        <option value="quinzenal" {{ request('periodicidade') === 'quinzenal' ? 'selected' : '' }}>Quinzenal</option>
                        <option value="mensal" {{ request('periodicidade') === 'mensal' ? 'selected' : '' }}>Mensal</option>
                        <option value="bimestral" {{ request('periodicidade') === 'bimestral' ? 'selected' : '' }}>Bimestral</option>
                        <option value="trimestral" {{ request('periodicidade') === 'trimestral' ? 'selected' : '' }}>Trimestral</option>
                        <option value="semestral" {{ request('periodicidade') === 'semestral' ? 'selected' : '' }}>Semestral</option>
                        <option value="anual" {{ request('periodicidade') === 'anual' ? 'selected' : '' }}>Anual</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <select name="origem" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas as origens...</option>
                        <option value="Trafego Pago" {{ request('origem') === 'Trafego Pago' ? 'selected' : '' }}>Tráfego Pago</option>
                        <option value="Indicacao" {{ request('origem') === 'Indicacao' ? 'selected' : '' }}>Indicação</option>
                        <option value="Prospeccao Ativa" {{ request('origem') === 'Prospeccao Ativa' ? 'selected' : '' }}>Prospecção Ativa</option>
                        <option value="Redes Sociais" {{ request('origem') === 'Redes Sociais' ? 'selected' : '' }}>Redes Sociais</option>
                        <option value="outros" {{ request('origem') === 'outros' ? 'selected' : '' }}>Outros</option>
                    </select>
                </div>
                <div class="lg:col-span-2 flex items-center gap-2">
                    @if (request()->hasAny(['busca', 'status_prospeccao', 'periodicidade', 'origem']))
                        <a href="{{ route('clientes.index') }}" class="btn btn-outline btn-sm py-2 px-2.5 text-xs shrink-0" title="Limpar todos os filtros">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                    @permissao('clientes.criar')
                    <a href="{{ route('clientes.create') }}" class="btn btn-primary flex-1 justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Novo</span>
                    </a>
                    @endpermissao
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Cliente / Contato</th>
                        <th>Status do Funil</th>
                        <th class="hidden md:table-cell">Periodicidade</th>
                        <th class="hidden lg:table-cell">Origem</th>
                        <th class="hidden sm:table-cell">Cidade/UF</th>
                        <th class="hidden xl:table-cell">Follow-up</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientes as $cliente)
                        <tr class="animate-fadeIn">
                            <td>
                                <a href="{{ route('clientes.show', $cliente) }}"
                                    class="font-medium text-white hover:underline">{{ $cliente->nome }}</a>
                                <div class="flex items-center gap-2 text-xs mt-0.5"
                                    style="color: var(--color-surface-400);">
                                    <span>{{ $cliente->telefone_formatado ?? $cliente->telefone ?? 'Sem telefone' }}</span>
                                    @if ($cliente->email)
                                        <span>&bull;</span>
                                        <span>{{ $cliente->email }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @php
                                    $badgeClass = match ($cliente->status_prospeccao) {
                                        'ativo' => 'badge-success',
                                        'em_negociacao' => 'badge-info',
                                        'prospeccao' => 'badge-purple',
                                        'retornar_3_meses' => 'badge-warning',
                                        'contrato_concorrente' => 'badge-danger',
                                        default => 'badge-neutral',
                                    };
                                    $statusLabels = [
                                        'ativo' => 'Ativo',
                                        'em_negociacao' => 'Em Negociação',
                                        'prospeccao' => 'Prospecção',
                                        'retornar_3_meses' => 'Retornar em 3 Meses',
                                        'contrato_concorrente' => 'Contrato Concorrente',
                                        'inativo' => 'Inativo',
                                    ];
                                @endphp
                                <span class="badge {{ $badgeClass }}">
                                    {{ $statusLabels[$cliente->status_prospeccao] ?? ucfirst($cliente->status_prospeccao) }}
                                </span>
                            </td>
                            <td class="hidden md:table-cell">
                                <span class="badge badge-neutral">
                                    {{ ucfirst($cliente->periodicidade ?? 'Avulso') }}
                                </span>
                            </td>
                            <td class="hidden lg:table-cell text-xs" style="color: var(--color-surface-400);">
                                {{ $cliente->origem ?? 'Outros' }}
                            </td>
                            <td class="hidden sm:table-cell text-xs" style="color: var(--color-surface-300);">
                                {{ $cliente->cidade ? $cliente->cidade . '/' . $cliente->estado : '—' }}
                            </td>
                            <td class="hidden xl:table-cell text-xs">
                                @if ($cliente->data_followup)
                                    <span
                                        class="font-medium {{ $cliente->data_followup->isPast() ? 'text-red-400' : 'text-amber-400' }}">
                                        {{ $cliente->data_followup->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span style="color: var(--color-surface-500);">—</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @permissao('comodatos.criar')
                                    <a href="{{ route('comodatos.create', ['cliente_id' => $cliente->id]) }}"
                                        class="btn btn-secondary btn-sm" title="Novo Comodato">
                                        <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </a>
                                    @endpermissao
                                    <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-secondary btn-sm"
                                        title="Ver Dossiê">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    @permissao('clientes.editar')
                                    <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-secondary btn-sm"
                                        title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    @endpermissao
                                    @permissao('clientes.deletar')
                                    <form id="delete-cliente-{{ $cliente->id }}"
                                        action="{{ route('clientes.destroy', $cliente) }}" method="POST"
                                        class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                            onclick="confirmDelete('delete-cliente-{{ $cliente->id }}')"
                                            class="btn btn-danger-soft btn-sm" title="Excluir">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                    @endpermissao
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <h4 class="empty-state-title">Nenhum cliente encontrado</h4>
                                    <p class="empty-state-desc">Ajuste os filtros de busca ou cadastre um novo cliente no
                                        sistema.</p>
                                    <a href="{{ route('clientes.create') }}"
                                        class="btn btn-primary btn-sm mt-3">Cadastrar Cliente</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginação --}}
        @if ($clientes->hasPages())
            <div class="pagination mt-4">
                {{ $clientes->links() }}
            </div>
        @endif
    </div>
@endsection
