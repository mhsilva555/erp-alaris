@extends('layouts.app')

@section('title', 'Equipamentos em Comodato')

@section('content')
    <div class="space-y-4">
        {{-- Header & Filtros --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('comodatos.index') }}"
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex-1 max-w-md relative">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-surface-400);"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="busca" value="{{ request('busca') }}"
                        placeholder="Buscar por equipamento ou cliente..." class="form-input !pl-10">
                </div>
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 text-xs font-medium cursor-pointer"
                        style="color: var(--color-surface-300);">
                        <input type="checkbox" name="vencendo" value="1" {{ request('vencendo') ? 'checked' : '' }}
                            onchange="this.form.submit()" class="rounded" style="accent-color: var(--color-primary-600);">
                        Reposição Próxima (até 48h)
                    </label>
                    <a href="{{ route('comodatos.create') }}" class="btn btn-primary shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Novo Comodato
                    </a>
                </div>
            </form>
        </div>

        {{-- Tabela de Equipamentos --}}
        <div class="card table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Equipamento</th>
                        <th>Cliente / Local</th>
                        <th>Quantidade</th>
                        <th class="hidden md:table-cell">Insumo Químico Vinculado</th>
                        <th>Periodicidade</th>
                        <th>Próxima Reposição</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($comodatos as $c)
                        <tr class="animate-fadeIn">
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('comodatos.show', $c) }}" class="font-medium text-white hover:underline hover:text-primary-400 transition">
                                        {{ $c->nome_equipamento }}
                                    </a>
                                    @if($c->equipamento)
                                        <span class="badge badge-neutral text-[10px]">{{ $c->equipamento->tipo_formatado }}</span>
                                    @endif
                                </div>
                                @if ($c->observacoes)
                                    <p class="text-xs mt-0.5 truncate max-w-xs" style="color: var(--color-surface-400);">
                                        {{ $c->observacoes }}</p>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('clientes.show', $c->cliente) }}"
                                    class="text-white hover:underline">{{ $c->cliente->nome }}</a>
                                <p class="text-xs" style="color: var(--color-surface-400);">
                                    {{ $c->cliente->cidade ? $c->cliente->cidade . '/' . $c->cliente->estado : '' }}</p>
                            </td>
                            <td>
                                <span class="font-semibold text-white">{{ $c->quantidade }}</span> un
                            </td>
                            <td class="hidden md:table-cell">
                                @if ($c->produto)
                                    <span class="badge badge-neutral">
                                        {{ $c->produto->nome }}
                                    </span>
                                @else
                                    <span class="text-xs" style="color: var(--color-surface-500);">Nenhum insumo
                                        atrelado</span>
                                @endif
                            </td>
                            <td class="text-xs" style="color: var(--color-surface-300);">
                                A cada {{ $c->periodicidade_dias }} dias
                            </td>
                            <td>
                                @if ($c->data_proxima_reposicao)
                                    @php
                                        $diasRestantes = now()
                                            ->startOfDay()
                                            ->diffInDays($c->data_proxima_reposicao->startOfDay(), false);
                                    @endphp
                                    <div>
                                        <span
                                            class="font-bold text-xs {{ $diasRestantes < 0 ? 'text-red-400' : ($diasRestantes <= 2 ? 'text-amber-400' : 'text-emerald-400') }}">
                                            {{ $c->data_proxima_reposicao->format('d/m/Y') }}
                                        </span>
                                        <span class="block text-[10px]" style="color: var(--color-surface-400);">
                                            @if ($diasRestantes < 0)
                                                Atrasada há {{ abs($diasRestantes) }} dia(s)
                                            @elseif($diasRestantes == 0)
                                                Hoje
                                            @else
                                                Em {{ $diasRestantes }} dia(s)
                                            @endif
                                        </span>
                                    </div>
                                @else
                                    <span class="text-xs" style="color: var(--color-surface-400);">Não agendada</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('visitas.create', [
                                        'cliente_id' => $c->cliente_id,
                                        'comodato_id' => $c->id,
                                        'tipo_visita' => 'execucao',
                                        'tipo_servico' => 'controle_pragas',
                                        'data' => $c->data_proxima_reposicao ? $c->data_proxima_reposicao->format('Y-m-d\T09:00') : now()->format('Y-m-d\T09:00'),
                                        'observacoes' => "Reposição de comodato: {$c->nome_equipamento} ({$c->quantidade} un)",
                                    ]) }}" class="btn btn-primary btn-sm text-xs" title="Agendar Ordem de Serviço de Reposição">
                                        + OS
                                    </a>
                                    <a href="{{ route('comodatos.show', $c) }}" class="btn btn-secondary btn-sm"
                                        title="Ver Detalhes do Comodato">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('comodatos.edit', $c) }}" class="btn btn-secondary btn-sm"
                                        title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <form id="delete-comodato-{{ $c->id }}"
                                        action="{{ route('comodatos.destroy', $c) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                            onclick="confirmDelete('delete-comodato-{{ $c->id }}')"
                                            class="btn btn-danger-soft btn-sm" title="Excluir">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
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
                                                d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <h4 class="empty-state-title">Nenhum comodato encontrado</h4>
                                    <p class="empty-state-desc">Cadastre equipamentos instalados em clientes para monitorar
                                        reposições preventivas.</p>
                                    <a href="{{ route('comodatos.create') }}" class="btn btn-primary btn-sm mt-3">Novo
                                        Comodato</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($comodatos->hasPages())
            <div class="pagination mt-4">
                {{ $comodatos->links() }}
            </div>
        @endif
    </div>
@endsection
