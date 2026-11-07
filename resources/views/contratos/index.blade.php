@extends('layouts.app')

@section('title', 'Contratos de Prestação de Serviços')

@section('content')
    <div class="space-y-5">
        {{-- Header & Estatísticas --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">Total de Contratos</p>
                        <p class="stat-value">{{ $totais['total'] }}</p>
                        <p class="stat-helper">{{ $totais['rascunhos'] }} em elaboração / rascunho</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(35, 180, 235, 0.15); color: #23b4eb;">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="card stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">Contratos Ativos</p>
                        <p class="stat-value text-emerald-400">{{ $totais['ativos'] }}</p>
                        <p class="stat-helper">Gerando agendamentos regulares</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="card stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">Contratos Suspensos</p>
                        <p class="stat-value text-amber-400">{{ $totais['suspensos'] }}</p>
                        <p class="stat-helper">Visitas futuras pausadas</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="card stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">Receita Recorrente Estimada</p>
                        <p class="stat-value font-mono text-white">R$ {{ number_format($totais['faturamento_mensal'], 2, ',', '.') }}</p>
                        <p class="stat-helper">Base de contratos ativos</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(168, 85, 247, 0.15); color: #a855f7;">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtros e Ações --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('contratos.index') }}"
                class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 xl:grid-cols-12 gap-3 items-center">
                <div class="relative sm:col-span-2 md:col-span-4 xl:col-span-4">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-surface-400);"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="busca" value="{{ request('busca') }}"
                        placeholder="Buscar por número, título ou cliente..." class="form-input !pl-10">
                </div>

                <div class="md:col-span-1 xl:col-span-2">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos os status...</option>
                        @foreach (\App\Models\Contrato::STATUSES as $key => $label)
                            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-1 xl:col-span-2">
                    <select name="tipo_vigencia" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas as vigências...</option>
                        @foreach (\App\Models\Contrato::VIGENCIAS as $key => $label)
                            <option value="{{ $key }}" {{ request('tipo_vigencia') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-1 xl:col-span-2">
                    <select name="cliente_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos os clientes...</option>
                        @foreach ($clientes as $cli)
                            <option value="{{ $cli->id }}" {{ request('cliente_id') == $cli->id ? 'selected' : '' }}>
                                {{ $cli->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-1 xl:col-span-2 flex items-center gap-2">
                    @if (request()->hasAny(['busca', 'status', 'tipo_vigencia', 'cliente_id']))
                        <a href="{{ route('contratos.index') }}" class="btn btn-outline btn-sm py-2 px-2.5 text-xs shrink-0" title="Limpar filtros">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                    @permissao('contratos.criar')
                    <a href="{{ route('contratos.create') }}" class="btn btn-primary flex-1 justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Novo Contrato
                    </a>
                    @endpermissao
                </div>
            </form>
        </div>

        {{-- Tabela de Contratos --}}
        <div class="card table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Número / Título</th>
                        <th>Cliente</th>
                        <th>Vigência & Datas</th>
                        <th>Periodicidade</th>
                        <th class="hidden md:table-cell">Visitas (Total/Exec)</th>
                        <th>Valor & Cobrança</th>
                        <th>Status</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contratos as $contrato)
                        <tr class="animate-fadeIn">
                            <td>
                                <a href="{{ route('contratos.show', $contrato) }}" class="font-bold text-white hover:underline text-sm block">
                                    {{ $contrato->numero }}
                                </a>
                                @if($contrato->titulo)
                                    <span class="text-xs truncate block max-w-xs" style="color: var(--color-surface-400);">{{ $contrato->titulo }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('clientes.show', $contrato->cliente) }}" class="font-medium text-white hover:underline block">
                                    {{ $contrato->cliente->nome }}
                                </a>
                                <span class="text-xs" style="color: var(--color-surface-400);">{{ $contrato->cliente->cidade_estado }}</span>
                            </td>
                            <td>
                                <span class="font-medium text-white text-xs">{{ $contrato->vigenciaLabel() }}</span>
                                <div class="text-[11px]" style="color: var(--color-surface-400);">
                                    Início: {{ $contrato->data_inicio->format('d/m/Y') }}
                                    @if($contrato->data_fim)
                                        • Fim: {{ $contrato->data_fim->format('d/m/Y') }}
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-neutral text-xs">{{ $contrato->periodicidadeLabel() }}</span>
                                <div class="text-[11px] mt-0.5" style="color: var(--color-surface-400);">
                                    {{ $contrato->tipoServicoLabel() }}
                                </div>
                            </td>
                            <td class="hidden md:table-cell text-xs">
                                <span class="font-bold text-white">{{ $contrato->visitas_count }}</span> agendada(s)
                                <div class="text-[11px] text-emerald-400">
                                    {{ $contrato->visitas_concluidas_count }} concluída(s)
                                </div>
                            </td>
                            <td>
                                <div class="font-mono font-bold text-white text-sm">
                                    R$ {{ number_format($contrato->valor_total, 2, ',', '.') }}
                                </div>
                                <span class="text-[11px] block" style="color: var(--color-surface-400);">{{ $contrato->modeloCobrancaLabel() }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $contrato->statusBadge() }}">
                                    {{ $contrato->statusLabel() }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('contratos.show', $contrato) }}" class="btn btn-secondary btn-sm text-xs" title="Ver detalhes do contrato">
                                        Detalhes
                                    </a>
                                    @if($contrato->podeEditar())
                                        @permissao('contratos.editar')
                                        <a href="{{ route('contratos.edit', $contrato) }}" class="btn btn-outline btn-sm text-xs" title="Editar contrato">
                                            Editar
                                        </a>
                                        @endpermissao
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state py-10">
                                    <div class="empty-state-icon">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <h4 class="empty-state-title">Nenhum contrato encontrado</h4>
                                    <p class="empty-state-desc">Ajuste os filtros de pesquisa ou cadastre o primeiro contrato de prestação de serviços.</p>
                                    @permissao('contratos.criar')
                                    <a href="{{ route('contratos.create') }}" class="btn btn-primary btn-sm mt-3">
                                        Criar Novo Contrato
                                    </a>
                                    @endpermissao
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
