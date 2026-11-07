@extends('layouts.app')

@section('title', "Contrato {$contrato->numero}")

@section('content')
    <div class="space-y-6">
        {{-- Header com Ações Rápidas --}}
        <div class="card p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <a href="{{ route('contratos.index') }}" class="text-xs font-semibold text-primary-400 hover:underline flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Todos os Contratos
                        </a>
                        <span class="text-xs" style="color: var(--color-surface-600);">/</span>
                        <span class="text-xs font-mono font-bold text-white">{{ $contrato->numero }}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-bold text-white">{{ $contrato->numero }}</h1>
                        <span class="badge {{ $contrato->statusBadge() }}">
                            {{ $contrato->statusLabel() }}
                        </span>
                        <span class="badge badge-neutral">
                            {{ $contrato->vigenciaLabel() }}
                        </span>
                    </div>

                    <p class="text-xs mt-1" style="color: var(--color-surface-400);">
                        Cliente: <a href="{{ route('clientes.show', $contrato->cliente) }}" class="font-semibold text-primary-400 hover:underline">{{ $contrato->cliente->nome }}</a>
                        @if($contrato->titulo)
                            • {{ $contrato->titulo }}
                        @endif
                    </p>
                </div>

                {{-- Barra de Ações Operacionais --}}
                <div class="flex flex-wrap items-center gap-2">
                    @if($contrato->podeAtivar())
                        @permissao('contratos.ativar')
                        <form action="{{ route('contratos.ativar', $contrato) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Ativar Contrato
                            </button>
                        </form>
                        @endpermissao
                    @endif

                    @if($contrato->podeSuspender())
                        @permissao('contratos.suspender')
                        <form action="{{ route('contratos.suspender', $contrato) }}" method="POST" class="inline"
                            onsubmit="return confirm('Deseja realmente suspender este contrato? As visitas futuras ainda não executadas serão pausadas.');">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm text-amber-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Suspender
                            </button>
                        </form>
                        @endpermissao
                    @endif

                    @if($contrato->estaAtivo())
                        @permissao('contratos.gerar_visitas')
                        <form action="{{ route('contratos.gerar-visitas', $contrato) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="btn btn-outline btn-sm text-xs" title="Abastecer a agenda com os próximos ciclos de visitas">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Abastecer Agenda (+90d)
                            </button>
                        </form>
                        @endpermissao

                        @permissao('visitas.criar')
                        <a href="{{ route('visitas.create', ['cliente_id' => $contrato->cliente_id, 'contrato_id' => $contrato->id]) }}"
                            class="btn btn-primary btn-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            + Nova OS Manual
                        </a>
                        @endpermissao
                    @endif

                    @if($contrato->podeEditar())
                        @permissao('contratos.editar')
                        <a href="{{ route('contratos.edit', $contrato) }}" class="btn btn-secondary btn-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Editar
                        </a>
                        @endpermissao
                    @endif

                    @if($contrato->podeEncerrar())
                        @permissao('contratos.suspender')
                        <form action="{{ route('contratos.encerrar', $contrato) }}" method="POST" class="inline"
                            onsubmit="return confirm('Deseja realmente encerrar este contrato? Nenhuma nova visita será gerada e as futuras pendentes serão canceladas.');">
                            @csrf
                            <button type="submit" class="btn btn-outline btn-sm text-xs">
                                Encerrar
                            </button>
                        </form>
                        @endpermissao
                    @endif

                    @if($contrato->podeCancelar())
                        @permissao('contratos.cancelar')
                        <form action="{{ route('contratos.cancelar', $contrato) }}" method="POST" class="inline"
                            onsubmit="return confirm('ATENÇÃO: Deseja cancelar definitivamente este contrato? Visitas futuras pendentes serão canceladas.');">
                            @csrf
                            <button type="submit" class="btn btn-danger-soft btn-sm text-xs">
                                Cancelar
                            </button>
                        </form>
                        @endpermissao
                    @endif
                </div>
            </div>
        </div>

        {{-- 4 Cards de Métricas --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">Total de Visitas no Contrato</p>
                        <p class="stat-value text-white">{{ $metricas['total_visitas'] }}</p>
                        <p class="stat-helper">Geradas pelo cronograma</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(35, 180, 235, 0.15); color: #23b4eb;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                </div>
            </div>

            <div class="card stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">Ordens de Serviço Concluídas</p>
                        <p class="stat-value text-emerald-400">{{ $metricas['concluidas'] }}</p>
                        <p class="stat-helper">Executadas em campo</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                </div>
            </div>

            <div class="card stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">Visitas Agendadas / Pendentes</p>
                        <p class="stat-value text-sky-400">{{ $metricas['agendadas'] }}</p>
                        <p class="stat-helper">Aguardando atendimento</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(14, 165, 233, 0.15); color: #0ea5e9;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>

            <div class="card stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stat-label">Valor Recorrente do Contrato</p>
                        <p class="stat-value font-mono text-white">R$ {{ number_format($contrato->valor_total, 2, ',', '.') }}</p>
                        <p class="stat-helper">{{ $contrato->modeloCobrancaLabel() }}</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(168, 85, 247, 0.15); color: #a855f7;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detalhes em Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Coluna 1: Dados do Cliente & Condições --}}
            <div class="space-y-6 lg:col-span-1">
                {{-- Cliente --}}
                <div class="card p-5 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white" style="color: var(--color-primary-400);">
                        Dados do Cliente
                    </h3>
                    <div>
                        <span class="form-label">Razão Social / Nome</span>
                        <a href="{{ route('clientes.show', $contrato->cliente) }}" class="font-bold text-white hover:underline block text-sm">
                            {{ $contrato->cliente->nome }}
                        </a>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="form-label">CPF / CNPJ</span>
                            <span class="text-white">{{ $contrato->cliente->documento_formatado ?? $contrato->cliente->documento ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="form-label">Telefone / WhatsApp</span>
                            <span class="text-white">{{ $contrato->cliente->telefone_formatado ?? $contrato->cliente->telefone ?? '—' }}</span>
                        </div>
                    </div>
                    <div>
                        <span class="form-label">Endereço de Execução</span>
                        <span class="text-xs text-white block">{{ $contrato->cliente->endereco ?? '—' }}</span>
                        <span class="text-xs" style="color: var(--color-surface-400);">{{ $contrato->cliente->cidade_estado }}</span>
                    </div>
                </div>

                {{-- Vigência & Periodicidade --}}
                <div class="card p-5 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white" style="color: var(--color-primary-400);">
                        Vigência & Periodicidade
                    </h3>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="form-label">Tipo de Vigência</span>
                            <span class="text-white font-semibold">{{ $contrato->vigenciaLabel() }}</span>
                        </div>
                        <div>
                            <span class="form-label">Periodicidade</span>
                            <span class="badge badge-neutral text-xs">{{ $contrato->periodicidadeLabel() }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="form-label">Data de Início</span>
                            <span class="text-white font-medium">{{ $contrato->data_inicio->format('d/m/Y') }}</span>
                        </div>
                        <div>
                            <span class="form-label">Data de Término</span>
                            <span class="text-white font-medium">
                                {{ $contrato->data_fim ? $contrato->data_fim->format('d/m/Y') : 'Indeterminada' }}
                            </span>
                        </div>
                    </div>

                    @if($contrato->dia_preferencial)
                        <div class="text-xs" style="color: var(--color-surface-400);">
                            Dia preferencial do mês: <strong class="text-white">Todo dia {{ $contrato->dia_preferencial }}</strong>
                        </div>
                    @endif

                    @if($contrato->proxima_geracao_ate)
                        <div class="p-2.5 rounded bg-surface-950 text-[11px]" style="border: 1px solid var(--color-surface-800); color: var(--color-surface-400);">
                            Agenda abastecida automaticamente até: <strong class="text-white">{{ $contrato->proxima_geracao_ate->format('d/m/Y') }}</strong>
                        </div>
                    @endif
                </div>

                {{-- Condições Comerciais --}}
                <div class="card p-5 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white" style="color: var(--color-primary-400);">
                        Condições Comerciais
                    </h3>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="form-label">Modelo de Cobrança</span>
                            <span class="text-white font-medium">{{ $contrato->modeloCobrancaLabel() }}</span>
                        </div>
                        <div>
                            <span class="form-label">Meio de Pagamento</span>
                            <span class="text-white font-medium">
                                @if($contrato->meioPagamento)
                                    <span class="inline-flex items-center gap-1 text-emerald-400 font-semibold">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                        {{ $contrato->meioPagamento->nome }}
                                    </span>
                                @else
                                    <span class="text-slate-400">Não especificado</span>
                                @endif
                            </span>
                        </div>
                        <div class="col-span-2">
                            <span class="form-label">Condições de Pagamento</span>
                            <span class="text-white">{{ $contrato->condicoes_pagamento ?? 'Padrão' }}</span>
                        </div>
                    </div>

                    @if($contrato->desconto > 0)
                        <div class="text-xs text-amber-400">
                            Desconto comercial aplicado: <strong>R$ {{ number_format($contrato->desconto, 2, ',', '.') }}</strong>
                        </div>
                    @endif

                    @if($contrato->observacoes_comerciais)
                        <div class="pt-2 border-t" style="border-color: var(--color-surface-800);">
                            <span class="form-label">Observações de Faturamento</span>
                            <p class="text-xs whitespace-pre-line text-white mt-0.5">{{ $contrato->observacoes_comerciais }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Coluna 2 e 3: Itens Contratados & Histórico de Visitas --}}
            <div class="space-y-6 lg:col-span-2">
                {{-- Serviços Contratados --}}
                <div class="card p-6 space-y-4">
                    <h3 class="text-base font-semibold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Serviços Contratados ({{ $contrato->itens->count() }})
                    </h3>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Descrição do Serviço</th>
                                    <th>Tipo de Serviço</th>
                                    <th class="text-right">Qtd</th>
                                    <th class="text-right">Valor Unit.</th>
                                    <th class="text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contrato->itens as $item)
                                    <tr>
                                        <td class="font-medium text-white">{{ $item->descricao }}</td>
                                        <td>
                                            <span class="badge badge-neutral text-xs">{{ $item->tipoServicoLabel() }}</span>
                                        </td>
                                        <td class="text-right font-mono">{{ number_format($item->quantidade, 2, ',', '.') }}</td>
                                        <td class="text-right font-mono">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                                        <td class="text-right font-mono font-bold text-white">R$ {{ number_format($item->valor_total, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                @if($contrato->desconto > 0)
                                    <tr>
                                        <td colspan="4" class="text-right text-xs" style="color: var(--color-surface-400);">Desconto Comercial:</td>
                                        <td class="text-right font-mono text-red-400 text-xs">- R$ {{ number_format($contrato->desconto, 2, ',', '.') }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td colspan="4" class="text-right font-bold text-white uppercase text-xs">Total do Contrato:</td>
                                    <td class="text-right font-mono font-bold text-emerald-400 text-base">R$ {{ number_format($contrato->valor_total, 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Visitas e Ordens de Serviço Vinculadas --}}
                <div class="card p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Ordens de Serviço & Visitas do Contrato ({{ $contrato->visitas->count() }})
                        </h3>
                        @if($contrato->estaAtivo())
                            @permissao('visitas.criar')
                            <a href="{{ route('visitas.create', ['cliente_id' => $contrato->cliente_id, 'contrato_id' => $contrato->id]) }}"
                                class="btn btn-secondary btn-sm text-xs">
                                + Agendar OS Avulsa
                            </a>
                            @endpermissao
                        @endif
                    </div>

                    @if($contrato->visitas->isEmpty())
                        <div class="empty-state py-6">
                            <div class="empty-state-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <h4 class="empty-state-title">Nenhuma visita gerada ainda</h4>
                            <p class="empty-state-desc">
                                @if($contrato->status === \App\Models\Contrato::STATUS_RASCUNHO)
                                    Este contrato está em modo rascunho. Ative o contrato para gerar automaticamente as primeiras visitas na agenda.
                                @else
                                    Clique no botão de abastecer agenda ou aguarde a rotina automática.
                                @endif
                            </p>
                            @if($contrato->podeAtivar())
                                <form action="{{ route('contratos.ativar', $contrato) }}" method="POST" class="mt-3">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">Ativar e Gerar Visitas</button>
                                </form>
                            @endif
                        </div>
                    @else
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Data / Horário</th>
                                        <th>OS / Tipo</th>
                                        <th>Técnico Responsável</th>
                                        <th>Status da OS</th>
                                        <th class="text-right">Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contrato->visitas as $v)
                                        @php
                                            $vBadge = match ($v->status) {
                                                'concluida' => 'badge-success',
                                                'em_andamento' => 'badge-warning',
                                                'cancelada' => 'badge-danger',
                                                default => 'badge-info',
                                            };
                                        @endphp
                                        <tr class="animate-fadeIn">
                                            <td>
                                                <span class="font-bold text-white text-xs block">{{ $v->data_hora->format('d/m/Y') }}</span>
                                                <span class="text-[11px]" style="color: var(--color-surface-400);">{{ $v->data_hora->format('H:i') }}</span>
                                            </td>
                                            <td>
                                                <span class="font-semibold text-white text-xs block">OS #{{ $v->id }}</span>
                                                <span class="text-[11px]" style="color: var(--color-surface-400);">{{ $v->tipoServicoLabel() }}</span>
                                            </td>
                                            <td>
                                                <span class="text-xs text-white">{{ $v->tecnico?->nome ?? 'Não atribuído' }}</span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $vBadge }}">
                                                    {{ $v->statusLabel() }}
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    @permissao('visitas.ver')
                                                    <a href="{{ route('visitas.show', $v) }}" class="btn btn-secondary btn-sm text-xs">
                                                        Ver OS
                                                    </a>
                                                    @endpermissao
                                                    @permissao('visitas.gerar_os')
                                                    <a href="{{ route('visitas.os', $v) }}" class="btn btn-outline btn-sm text-xs text-sky-400" title="Imprimir OS">
                                                        Imprimir
                                                    </a>
                                                    @endpermissao
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
