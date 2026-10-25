@extends('layouts.app')

@section('title', 'Ordens de Serviço & Visitas')

@section('content')
    <div id="pagina-visitas-index" class="space-y-4">
        {{-- Header & Filtros --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('visitas.index') }}" class="flex flex-wrap gap-2.5 sm:gap-3 items-center">
                <div class="relative flex-1 min-w-[180px] sm:min-w-[220px]">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-surface-400);"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Buscar por cliente..."
                        class="form-input !pl-10">
                </div>
                <div class="w-full sm:w-auto min-w-[130px]">
                    <select name="status" class="form-select w-full" onchange="this.form.submit()">
                        <option value="">Todos os status</option>
                        @foreach (\App\Models\Visita::STATUSES as $key => $label)
                            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>
                                {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full sm:w-auto min-w-[140px]">
                    <select name="tipo_visita" class="form-select w-full" onchange="this.form.submit()">
                        <option value="">Todas as categorias...</option>
                        @foreach (\App\Models\Visita::TIPOS_VISITA as $key => $label)
                            <option value="{{ $key }}" {{ request('tipo_visita') === $key ? 'selected' : '' }}>
                                {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <input type="date" name="data" value="{{ request('data') }}" class="form-input flex-1 sm:w-auto text-xs sm:text-sm"
                        onchange="this.form.submit()">
                    @if (request()->hasAny(['busca', 'status', 'tipo_visita', 'data']))
                        <a href="{{ route('visitas.index') }}" class="btn btn-outline btn-sm py-2 px-2.5 text-xs shrink-0" title="Limpar todos os filtros">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                    @permissao('visitas.criar')
                    <a href="{{ route('visitas.create') }}" class="btn btn-primary shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nova OS
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
                        <th>Data / Horário</th>
                        <th>Cliente</th>
                        <th>Categoria & Serviço</th>
                        <th class="hidden sm:table-cell">Técnico</th>
                        @permissao('visitas.acessar_dados')
                            <th class="hidden md:table-cell">Valor / Lucro</th>
                        @endpermissao
                        <th>Status</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visitas as $visita)
                        @php
                            $vBadge = match ($visita->status) {
                                'concluida' => 'badge-success',
                                'em_andamento' => 'badge-warning',
                                'cancelada' => 'badge-danger',
                                default => 'badge-info',
                            };
                        @endphp
                        <tr class="animate-fadeIn">
                            <td>
                                <span class="font-medium text-white">{{ $visita->data_hora->format('d/m/Y') }}</span>
                                <span class="text-xs ml-1 "
                                    style="color: var(--color-surface-400);">{{ $visita->data_hora->format('H:i') }}</span>
                            </td>
                            <td>
                                <a href="{{ route('clientes.show', $visita->cliente) }}"
                                    class="text-white hover:underline font-medium">{{ $visita->cliente->nome }}</a>
                                @if ($visita->tipo_praga)
                                    <p class="text-[11px] truncate" style="color: var(--color-surface-400);">Alvo:
                                        {{ $visita->tipo_praga }}</p>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-neutral">
                                    {{ $visita->tipoVisitaLabel() }}
                                </span>
                                <p class="text-[11px] mt-0.5" style="color: var(--color-surface-400);">
                                    {{ $visita->tipoServicoLabel() }}</p>
                            </td>
                            <td class="hidden sm:table-cell text-sm" style="color: var(--color-surface-200);">
                                {{ $visita->tecnico?->nome ?? '—' }}</td>
                            @permissao('visitas.acessar_dados')
                                <td class="hidden md:table-cell text-xs ">
                                    <span class="text-white font-bold">R$
                                        {{ number_format($visita->valor_cobrado, 2, ',', '.') }}</span>
                                    @if ($visita->valor_cobrado > 0)
                                        <span
                                            class="block text-[10px] {{ $visita->lucro_bruto >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                            Lucro: R$ {{ number_format($visita->lucro_bruto, 2, ',', '.') }}
                                            ({{ $visita->margemPercentual() }}%)
                                        </span>
                                    @endif
                                </td>
                            @endpermissao
                            <td>
                                <span class="badge {{ $vBadge }} whitespace-nowrap">
                                    {{ $visita->statusLabel() }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @permissao('visitas.gerar_os')
                                    <a href="{{ route('visitas.os', $visita) }}"
                                        class="btn btn-secondary btn-sm text-xs text-sky-400"
                                        title="Imprimir Ordem de Serviço">
                                        OS
                                    </a>
                                    @endpermissao
                                    @permissao('visitas.proposta_comercial')
                                        <a href="{{ route('visitas.proposta', $visita) }}"
                                            class="btn btn-secondary btn-sm text-xs text-emerald-400"
                                            title="Proposta do Cliente (Sem custos)">
                                            Proposta
                                        </a>
                                    @endpermissao
                                    @permissao('visitas.finalizar')
                                    @if ($visita->status !== 'concluida')
                                        <a href="{{ route('visitas.show', $visita) }}?finalizar=1"
                                            class="btn btn-secondary btn-sm text-emerald-400 hover:text-emerald-300"
                                            title="Finalizar Visita (Adicionar observação e gastos extras)">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </a>
                                    @endif
                                    @endpermissao
                                    @permissao('visitas.ver')
                                    <a href="{{ route('visitas.show', $visita) }}" class="btn btn-secondary btn-sm"
                                        title="Ver Detalhes">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    @endpermissao
                                    @permissao('visitas.editar')
                                    <a href="{{ route('visitas.edit', $visita) }}" class="btn btn-secondary btn-sm"
                                        title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    @endpermissao
                                    @permissao('visitas.deletar')
                                    <form id="delete-visita-{{ $visita->id }}"
                                        action="{{ route('visitas.destroy', $visita) }}" method="POST"
                                        class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                            onclick="confirmDelete('delete-visita-{{ $visita->id }}')"
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
                            <td colspan="{{ auth()->user() && auth()->user()->isAdmin() ? 7 : 6 }}"
                                class="text-center py-12">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                    <h4 class="empty-state-title">Nenhuma visita ou OS encontrada</h4>
                                    <p class="empty-state-desc">Ajuste os filtros de busca ou crie uma nova ordem de
                                        serviço.</p>
                                    <a href="{{ route('visitas.create') }}" class="btn btn-primary btn-sm mt-3">Nova
                                        Ordem de Serviço</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($visitas->hasPages())
            <div class="pagination mt-4">
                {{ $visitas->links() }}
            </div>
        @endif
    </div>
@endsection
