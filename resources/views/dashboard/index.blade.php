@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="space-y-6">

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @permissao('dashboard.card_clientes')
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="stat-label">Clientes Cadastrados</p>
                            <p class="stat-value">{{ $totalClientes }}</p>
                            <p class="stat-helper">Base ativa & prospecção</p>
                        </div>
                        <div class="stat-icon" style="background: rgba(43,143,255,.12); color: var(--color-primary-400);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            @endpermissao

            @permissao('dashboard.card_comodatos')
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="stat-label">Equipamentos em Comodato</p>
                            <p class="stat-value">{{ $totalComodatos }}</p>
                            <p class="stat-helper">Porta-iscas e armadilhas</p>
                        </div>
                        <div class="stat-icon" style="background: rgba(14,165,233,.12); color: #38bdf8;">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                </div>
            @endpermissao

            @permissao('dashboard.card_visitas_hoje')
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="stat-label">Visitas Hoje</p>
                            <p class="stat-value text-emerald-400">{{ $visitasHoje }}</p>
                            <p class="stat-helper">Roteiro programado</p>
                        </div>
                        <div class="stat-icon" style="background: rgba(16,185,129,.12); color: var(--color-success);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                    </div>
                </div>
            @endpermissao

            @permissao('dashboard.card_visitas_mes')
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="stat-label">Visitas no Mês</p>
                            <p class="stat-value text-purple-400">{{ $visitasMes }}</p>
                            <p class="stat-helper">Volume operacional</p>
                        </div>
                        <div class="stat-icon" style="background: rgba(168,85,247,.12); color: #c084fc;">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                </div>
            @endpermissao
        </div>

        {{-- Alertas Prioritários e Operacionais --}}
        @permissao('dashboard.alertas')
            @if (
                $visitasAtrasadas->isNotEmpty() ||
                    $estoqueBaixo->isNotEmpty() ||
                    $comodatoVencendo->isNotEmpty() ||
                    $followupsProximos->isNotEmpty())
                <div class="card p-5">
                    <h2 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M12 3a9 9 0 110 18 9 9 0 010-18z" />
                        </svg>
                        Alertas e Ações Requeridas
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach ($visitasAtrasadas as $v)
                            <a href="{{ route('visitas.show', $v) }}" class="flex items-center gap-3 p-3 rounded-lg transition"
                                style="background: rgba(239,68,68,.08);"
                                onmouseover="this.style.background='rgba(239,68,68,.15)'"
                                onmouseout="this.style.background='rgba(239,68,68,.08)'">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: var(--color-danger);"></span>
                                <span class="text-xs flex-1"><strong class="text-red-400">Visita atrasada:</strong>
                                    {{ $v->cliente->nome }} — {{ $v->data_hora->format('d/m/Y H:i') }}
                                    ({{ $v->tecnico?->nome ?? 'Sem técnico' }})</span>
                            </a>
                        @endforeach

                        @foreach ($comodatoVencendo as $c)
                            <a href="{{ route('comodatos.edit', $c) }}"
                                class="flex items-center gap-3 p-3 rounded-lg transition"
                                style="background: rgba(14,165,233,.08);"
                                onmouseover="this.style.background='rgba(14,165,233,.15)'"
                                onmouseout="this.style.background='rgba(14,165,233,.08)'">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0 bg-sky-400"></span>
                                <span class="text-xs flex-1"><strong class="text-sky-300">Reposição Comodato:</strong>
                                    {{ $c->nome_equipamento }} em {{ $c->cliente->nome }} (Vence
                                    {{ $c->data_proxima_reposicao->format('d/m') }})</span>
                            </a>
                        @endforeach

                        @foreach ($followupsProximos as $f)
                            <a href="{{ route('clientes.show', $f) }}"
                                class="flex items-center gap-3 p-3 rounded-lg transition"
                                style="background: rgba(168,85,247,.08);"
                                onmouseover="this.style.background='rgba(168,85,247,.15)'"
                                onmouseout="this.style.background='rgba(168,85,247,.08)'">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0 bg-purple-400"></span>
                                <span class="text-xs flex-1"><strong class="text-purple-300">Follow-up Prospecção:</strong>
                                    {{ $f->nome }} (Contato previsto em {{ $f->data_followup->format('d/m/Y') }})</span>
                            </a>
                        @endforeach

                        @foreach ($estoqueBaixo as $p)
                            <a href="{{ route('produtos.edit', $p) }}"
                                class="flex items-center gap-3 p-3 rounded-lg transition"
                                style="background: rgba(245,158,11,.08);"
                                onmouseover="this.style.background='rgba(245,158,11,.15)'"
                                onmouseout="this.style.background='rgba(245,158,11,.08)'">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0"
                                    style="background: var(--color-warning);"></span>
                                <span class="text-xs flex-1"><strong class="text-amber-300">Estoque Crítico:</strong>
                                    {{ $p->nome }} — {{ number_format($p->quantidade_estoque, 2, ',', '.') }}
                                    {{ $p->unidade_armazenamento ?? $p->unidade }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endpermissao

        @algumapermissao(['dashboard.proximas_visitas', 'dashboard.historico_visitas'])
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                {{-- Próximas visitas --}}
                @permissao('dashboard.proximas_visitas')
                    <div class="card p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                                <svg class="w-4 h-4" style="color: var(--color-primary-400);" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Próximas Visitas (48h)
                            </h2>
                            <a href="{{ route('agendamentos.index') }}"
                                class="text-xs text-blue-400 hover:text-blue-300 transition">Ver agenda completa &rarr;</a>
                        </div>
                        @forelse($proximasVisitas as $v)
                            <a href="{{ route('visitas.show', $v) }}"
                                class="flex items-center justify-between py-2.5 border-b transition-colors"
                                style="border-color: var(--color-surface-800);" onmouseover="this.style.opacity='.8'"
                                onmouseout="this.style.opacity='1'">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium text-white">{{ $v->cliente->nome }}</p>
                                        <span
                                            class="badge badge-neutral text-[10px] px-1.5 py-0.5">{{ $v->tipoVisitaLabel() }}</span>
                                    </div>
                                    <p class="text-xs" style="color: var(--color-surface-400);">
                                        {{ $v->tecnico?->nome ?? 'Sem técnico' }} &bull; {{ $v->tipoServicoLabel() }}</p>
                                </div>
                                <span class="badge badge-info whitespace-nowrap">
                                    {{ $v->data_hora->format('d/m H:i') }}
                                </span>
                            </a>
                        @empty
                            <div class="empty-state py-6">
                                <p class="empty-state-desc">Nenhuma visita agendada para as próximas 48 horas.</p>
                            </div>
                        @endforelse
                    </div>
                @endpermissao

                {{-- Visitas recentes --}}
                @permissao('dashboard.historico_visitas')
                    <div class="card p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                                <svg class="w-4 h-4" style="color: var(--color-success);" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Histórico Recente de Visitas
                            </h2>
                            <a href="{{ route('visitas.index') }}"
                                class="text-xs text-blue-400 hover:text-blue-300 transition">Ver todas &rarr;</a>
                        </div>
                        @forelse($visitasRecentes as $v)
                            <a href="{{ route('visitas.show', $v) }}"
                                class="flex items-center justify-between py-2.5 border-b transition-colors"
                                style="border-color: var(--color-surface-800);" onmouseover="this.style.opacity='.8'"
                                onmouseout="this.style.opacity='1'">
                                <div>
                                    <p class="text-sm font-medium text-white">{{ $v->cliente->nome }}</p>
                                    <p class="text-xs" style="color: var(--color-surface-400);">
                                        {{ $v->data_hora->format('d/m/Y H:i') }} &bull; {{ $v->tecnico?->nome ?? '—' }}</p>
                                </div>
                                @php
                                    $vBadge = match ($v->status) {
                                        'concluida' => 'badge-success',
                                        'em_andamento' => 'badge-warning',
                                        'cancelada' => 'badge-danger',
                                        default => 'badge-info',
                                    };
                                @endphp
                                <span class="badge {{ $vBadge }} whitespace-nowrap">
                                    {{ $v->statusLabel() }}
                                </span>
                            </a>
                        @empty
                            <div class="empty-state py-6">
                                <p class="empty-state-desc">Nenhuma visita registrada recentemente.</p>
                            </div>
                        @endforelse
                    </div>
                @endpermissao
            </div>
        @endalgumapermissao
    </div>
@endsection
