@extends('layouts.app')

@section('title', 'Detalhes do Comodato - ' . $comodato->nome_equipamento)

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="card p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider mb-1" style="color: var(--color-surface-400);">
                    <a href="{{ route('comodatos.index') }}" class="hover:underline">Equipamentos em Comodato</a>
                    <span>/</span>
                    <span style="color: var(--color-primary-400);">Detalhes</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-7 h-7 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    {{ $comodato->nome_equipamento }}
                </h1>
                <p class="text-sm mt-1" style="color: var(--color-surface-400);">
                    Instalado em <strong>{{ $comodato->cliente->nome }}</strong> • {{ $comodato->quantidade }} {{ $comodato->quantidade === 1 ? 'unidade' : 'unidades' }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('comodatos.index') }}" class="btn btn-secondary btn-sm">
                    ← Voltar
                </a>
                <a href="{{ route('comodatos.edit', $comodato) }}" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Editar
                </a>
                <a href="{{ route('visitas.create', [
                    'cliente_id' => $comodato->cliente_id,
                    'comodato_id' => $comodato->id,
                    'tipo_visita' => 'execucao',
                    'tipo_servico' => 'controle_pragas',
                    'data' => $comodato->data_proxima_reposicao ? $comodato->data_proxima_reposicao->format('Y-m-d\T09:00') : now()->format('Y-m-d\T09:00'),
                    'observacoes' => "Reposição de comodato: {$comodato->nome_equipamento} ({$comodato->quantidade} un)",
                ]) }}" class="btn btn-primary btn-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Agendar Ordem de Serviço
                </a>
            </div>
        </div>
    </div>

    {{-- Conteúdo Principal (2 Colunas) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Coluna Esquerda: Dados do Equipamento & Histórico de OS --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Card de Informações Gerais --}}
            <div class="card p-6">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4 pb-2 border-b flex items-center gap-2" style="border-color: var(--color-border);">
                    <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Dados do Comodato
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="text-xs uppercase tracking-wider font-semibold" style="color: var(--color-surface-400);">Equipamento Instalado</span>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white mt-0.5">
                            {{ $comodato->nome_equipamento }}
                        </p>
                        @if ($comodato->equipamento)
                            <p class="text-xs text-slate-500 mt-0.5">
                                Catálogo: {{ $comodato->equipamento->nome }} {{ $comodato->equipamento->modelo ? "({$comodato->equipamento->modelo})" : '' }}
                            </p>
                        @endif
                    </div>

                    <div>
                        <span class="text-xs uppercase tracking-wider font-semibold" style="color: var(--color-surface-400);">Quantidade em Posse do Cliente</span>
                        <p class="text-sm font-bold text-slate-900 dark:text-white mt-0.5">
                            {{ $comodato->quantidade }} {{ $comodato->quantidade === 1 ? 'unidade' : 'unidades' }}
                        </p>
                    </div>

                    <div>
                        <span class="text-xs uppercase tracking-wider font-semibold" style="color: var(--color-surface-400);">Insumo Químico Vinculado</span>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white mt-0.5">
                            @if ($comodato->produto)
                                <span class="badge badge-neutral">
                                    {{ $comodato->produto->nome }}
                                </span>
                            @else
                                <span class="text-slate-500 italic">Nenhum produto fixado</span>
                            @endif
                        </p>
                    </div>

                    <div>
                        <span class="text-xs uppercase tracking-wider font-semibold" style="color: var(--color-surface-400);">Periodicidade de Reposição</span>
                        <p class="text-sm font-bold text-slate-900 dark:text-white mt-0.5">
                            A cada {{ $comodato->periodicidade_dias }} dias
                        </p>
                    </div>
                </div>

                @if ($comodato->observacoes)
                    <div class="mt-4 pt-4 border-t" style="border-color: var(--color-border);">
                        <span class="text-xs uppercase tracking-wider font-semibold" style="color: var(--color-surface-400);">Observações e Localização no Cliente</span>
                        <p class="text-sm text-slate-700 dark:text-slate-300 mt-1 whitespace-pre-line">
                            {{ $comodato->observacoes }}
                        </p>
                    </div>
                @endif
            </div>

            {{-- Histórico de Ordens de Serviço Vinculadas --}}
            <div class="card p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5 pb-4 border-b" style="border-color: var(--color-border);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 bg-emerald-500/10 text-emerald-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                    Histórico de Ordens de Serviço
                                </h3>
                                <span class="badge badge-neutral text-xs">
                                    {{ $visitas->total() }} {{ $visitas->total() === 1 ? 'OS' : 'OSs' }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-surface-400 mt-0.5">
                                A reposição do comodato é baixada automaticamente ao concluir a OS de campo.
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('visitas.create', [
                        'cliente_id' => $comodato->cliente_id,
                        'comodato_id' => $comodato->id,
                        'tipo_visita' => 'execucao',
                        'tipo_servico' => 'controle_pragas',
                        'data' => $comodato->data_proxima_reposicao ? $comodato->data_proxima_reposicao->format('Y-m-d\T09:00') : now()->format('Y-m-d\T09:00'),
                        'observacoes' => "Reposição de comodato: {$comodato->nome_equipamento} ({$comodato->quantidade} un)",
                    ]) }}" class="btn btn-secondary btn-sm text-xs flex items-center gap-1.5 self-start sm:self-auto shrink-0">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Nova OS de Reposição</span>
                    </a>
                </div>

                @if ($visitas->isEmpty())
                    <div class="empty-state py-8">
                        <div class="empty-state-icon">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h4 class="empty-state-title">Nenhuma Ordem de Serviço registrada</h4>
                        <p class="empty-state-desc max-w-md mx-auto">
                            Quando uma OS vinculada a este comodato for agendada e concluída pelo técnico, o histórico será listado aqui e o ciclo de reposição será atualizado automaticamente.
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('visitas.create', [
                                'cliente_id' => $comodato->cliente_id,
                                'comodato_id' => $comodato->id,
                                'tipo_visita' => 'execucao',
                                'tipo_servico' => 'controle_pragas',
                                'data' => $comodato->data_proxima_reposicao ? $comodato->data_proxima_reposicao->format('Y-m-d\T09:00') : now()->format('Y-m-d\T09:00'),
                                'observacoes' => "Reposição de comodato: {$comodato->nome_equipamento} ({$comodato->quantidade} un)",
                            ]) }}" class="btn btn-primary btn-sm inline-flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Gerar Primeira OS de Reposição</span>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="w-20">OS #</th>
                                    <th>Data & Horário</th>
                                    <th>Técnico Responsável</th>
                                    <th>Insumos / Aplicação</th>
                                    <th>Status</th>
                                    <th class="text-right w-28">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($visitas as $v)
                                    @php
                                        $badgeClass = match ($v->status) {
                                            'concluida' => 'badge-success',
                                            'em_andamento' => 'badge-warning',
                                            'agendada' => 'badge-info',
                                            'cancelada' => 'badge-neutral',
                                            default => 'badge-neutral',
                                        };
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-surface-800/40 transition-colors">
                                        <td>
                                            <a href="{{ route('visitas.show', $v) }}" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-surface-800 dark:hover:bg-surface-700 text-slate-800 dark:text-surface-200 transition-colors">
                                                #{{ $v->id }}
                                            </a>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <div>
                                                    <div class="font-semibold text-slate-900 dark:text-white text-xs">
                                                        {{ $v->data_hora->format('d/m/Y') }}
                                                    </div>
                                                    <div class="text-[11px] text-slate-500 dark:text-surface-400">
                                                        {{ $v->data_hora->format('H:i') }}h
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($v->tecnico)
                                                <div class="flex items-center gap-2">
                                                    <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[11px] uppercase shrink-0 bg-primary-500/10 text-primary-500">
                                                        {{ mb_substr($v->tecnico->nome, 0, 1) }}
                                                    </div>
                                                    <span class="text-xs font-medium text-slate-800 dark:text-surface-200">
                                                        {{ $v->tecnico->nome }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-xs text-slate-400 italic">Não atribuído</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($v->produtos->isNotEmpty())
                                                <div class="flex flex-wrap gap-1 max-w-xs">
                                                    @foreach ($v->produtos as $prod)
                                                        <span class="badge badge-neutral text-[11px] py-0.5" title="Quantidade aplicada">
                                                            {{ $prod->nome }} ({{ number_format($prod->pivot->quantidade_aplicada ?? ($prod->pivot->quantidade ?? 0), 1, ',', '.') }} {{ $prod->unidade_aplicacao ?? $prod->unidade }})
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @elseif ($comodato->produto)
                                                <span class="text-xs text-slate-500 dark:text-surface-400 flex items-center gap-1" title="Insumo padrão do comodato">
                                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                                    </svg>
                                                    {{ $comodato->produto->nome }}
                                                </span>
                                            @else
                                                <span class="text-xs text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $badgeClass }}">
                                                {{ $v->statusLabel() }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('visitas.show', $v) }}" class="btn btn-secondary btn-sm text-xs inline-flex items-center gap-1">
                                                <span>Abrir OS</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($visitas->hasPages())
                        <div class="mt-4 pt-3 border-t" style="border-color: var(--color-border);">
                            {{ $visitas->links() }}
                        </div>
                    @endif
                @endif
            </div>

        </div>

        {{-- Coluna Direita: Status dos Ciclos & Dados do Cliente --}}
        <div class="space-y-6">

            {{-- Card de Ciclos e Próxima Reposição --}}
            <div class="card p-6">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4 pb-2 border-b flex items-center gap-2" style="border-color: var(--color-border);">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Prazos de Reposição
                </h3>

                <div class="space-y-4">
                    @php
                        $diasRestantes = $comodato->data_proxima_reposicao
                            ? now()->startOfDay()->diffInDays($comodato->data_proxima_reposicao->startOfDay(), false)
                            : null;
                    @endphp

                    <div class="p-3.5 rounded-lg border {{ $diasRestantes !== null && $diasRestantes < 0 ? 'bg-red-500/10 border-red-500/30' : ($diasRestantes !== null && $diasRestantes <= 2 ? 'bg-amber-500/10 border-amber-500/30' : 'bg-emerald-500/10 border-emerald-500/30') }}">
                        <span class="text-xs uppercase tracking-wider font-semibold block" style="color: var(--color-surface-400);">Próxima Reposição Agendada</span>
                        <div class="flex items-baseline justify-between mt-1">
                            <span class="text-lg font-bold {{ $diasRestantes !== null && $diasRestantes < 0 ? 'text-red-500' : ($diasRestantes !== null && $diasRestantes <= 2 ? 'text-amber-500' : 'text-emerald-500') }}">
                                {{ $comodato->data_proxima_reposicao ? $comodato->data_proxima_reposicao->format('d/m/Y') : 'Não agendada' }}
                            </span>
                            @if ($diasRestantes !== null)
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $diasRestantes < 0 ? 'bg-red-500/20 text-red-600 dark:text-red-300' : ($diasRestantes == 0 ? 'bg-amber-500/20 text-amber-600 dark:text-amber-300' : 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-300') }}">
                                    @if ($diasRestantes < 0)
                                        Atrasada há {{ abs($diasRestantes) }}d
                                    @elseif ($diasRestantes == 0)
                                        Hoje
                                    @else
                                        Em {{ $diasRestantes }}d
                                    @endif
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="p-3 rounded-lg border" style="border-color: var(--color-border); background: var(--color-surface-subtle);">
                            <span class="block text-slate-500 font-medium">Instalado em:</span>
                            <span class="font-bold text-slate-900 dark:text-white mt-0.5 block">
                                {{ $comodato->data_instalacao ? $comodato->data_instalacao->format('d/m/Y') : '-' }}
                            </span>
                        </div>
                        <div class="p-3 rounded-lg border" style="border-color: var(--color-border); background: var(--color-surface-subtle);">
                            <span class="block text-slate-500 font-medium">Última reposição:</span>
                            <span class="font-bold text-slate-900 dark:text-white mt-0.5 block">
                                {{ $comodato->data_ultima_reposicao ? $comodato->data_ultima_reposicao->format('d/m/Y') : 'Nunca' }}
                            </span>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-500">
                        O sistema recalcula automaticamente a próxima data adicionando <strong>{{ $comodato->periodicidade_dias }} dias</strong> assim que a OS correspondente for finalizada.
                    </p>
                </div>
            </div>

            {{-- Card do Cliente --}}
            <div class="card p-6">
                <div class="flex items-center justify-between mb-4 pb-2 border-b" style="border-color: var(--color-border);">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        Cliente Beneficiário
                    </h3>
                    <a href="{{ route('clientes.show', $comodato->cliente) }}" class="text-xs font-semibold text-primary-500 hover:underline">
                        Ver Cliente →
                    </a>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-slate-500 block">Razão Social / Nome:</span>
                        <span class="font-bold text-sm text-slate-900 dark:text-white block mt-0.5">
                            {{ $comodato->cliente->nome }}
                        </span>
                    </div>

                    @if ($comodato->cliente->documento)
                        <div>
                            <span class="text-slate-500 block">Documento:</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200 block mt-0.5">
                                {{ $comodato->cliente->documento_formatado }}
                            </span>
                        </div>
                    @endif

                    @if ($comodato->cliente->telefone)
                        <div>
                            <span class="text-slate-500 block">Telefone:</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200 block mt-0.5">
                                {{ $comodato->cliente->telefone_formatado }}
                            </span>
                        </div>
                    @endif

                    @if ($comodato->cliente->endereco_completo)
                        <div>
                            <span class="text-slate-500 block">Endereço de Instalação:</span>
                            <span class="text-slate-700 dark:text-slate-300 block mt-0.5">
                                {{ $comodato->cliente->endereco_completo }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
