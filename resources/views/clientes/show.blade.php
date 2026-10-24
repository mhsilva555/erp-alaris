@extends('layouts.app')

@section('title', $cliente->nome)

@section('content')
<div class="space-y-6">
    {{-- Info Card --}}
    <div class="card p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-lg font-bold text-white preserve-white" style="background: linear-gradient(135deg, var(--color-primary-700), var(--color-primary-500));">
                    {{ mb_substr($cliente->nome, 0, 1) }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-bold text-white">{{ $cliente->nome }}</h2>
                        @php
                            $badgeClass = match($cliente->status_prospeccao) {
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
                    </div>
                    @if($cliente->documento)
                        <p class="text-xs" style="color: var(--color-surface-400);">{{ $cliente->documento }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('comodatos.create', ['cliente_id' => $cliente->id]) }}" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Alocar Comodato
                </a>
                <a href="{{ route('visitas.create', ['cliente_id' => $cliente->id]) }}" class="btn btn-primary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Nova Visita / OS
                </a>
                <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Editar
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm pt-4 border-t" style="border-color: var(--color-surface-800);">
            <div>
                <span class="form-label">Telefone / WhatsApp</span>
                <p class="text-white">{{ $cliente->telefone ?? '—' }}</p>
            </div>
            <div>
                <span class="form-label">E-mail</span>
                <p class="text-white">{{ $cliente->email ?? '—' }}</p>
            </div>
            <div>
                <span class="form-label">Origem do Lead</span>
                <p class="text-white">{{ $cliente->origem ?? 'Outros' }}</p>
            </div>
            <div>
                <span class="form-label">Periodicidade de Atendimento</span>
                <p class="text-white font-medium capitalize">{{ $cliente->periodicidade ?? 'Avulso' }}</p>
            </div>
            <div>
                <span class="form-label">Endereço</span>
                <p class="text-white">{{ $cliente->endereco ?? '—' }}</p>
            </div>
            <div>
                <span class="form-label">Cidade / UF</span>
                <p class="text-white">{{ $cliente->cidade ? $cliente->cidade . '/' . $cliente->estado : '—' }}</p>
            </div>
            <div>
                <span class="form-label">CEP</span>
                <p class="text-white">{{ $cliente->cep ?? '—' }}</p>
            </div>
            <div>
                <span class="form-label">Data de Follow-up (Retorno)</span>
                @if($cliente->data_followup)
                    <p class="font-bold {{ $cliente->data_followup->isPast() ? 'text-red-400' : 'text-amber-400' }}">
                        {{ $cliente->data_followup->format('d/m/Y') }}
                    </p>
                @else
                    <p class="text-white">—</p>
                @endif
            </div>
        </div>

        @if($cliente->observacoes)
            <div class="mt-4 pt-4 border-t" style="border-color: var(--color-surface-800);">
                <span class="form-label">Observações Técnicas / Acesso</span>
                <p class="text-sm mt-1 whitespace-pre-line" style="color: var(--color-surface-300);">{{ $cliente->observacoes }}</p>
            </div>
        @endif
    </div>

    {{-- Seção: Equipamentos em Comodato --}}
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Equipamentos em Comodato no Cliente ({{ $cliente->comodatos->count() }})
            </h3>
            <a href="{{ route('comodatos.create', ['cliente_id' => $cliente->id]) }}" class="btn btn-secondary btn-sm">
                + Adicionar Equipamento
            </a>
        </div>

        @if($cliente->comodatos->isEmpty())
            <div class="empty-state py-6">
                <div class="empty-state-icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <h4 class="empty-state-title">Nenhum comodato alocado</h4>
                <p class="empty-state-desc">Nenhum porta-isca ou armadilha foi instalado neste cliente ainda.</p>
            </div>
        @else
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Equipamento</th>
                            <th>Qtd</th>
                            <th>Insumo Químico Vinculado</th>
                            <th>Ciclo de Reposição</th>
                            <th>Próxima Reposição</th>
                            <th class="text-right">Ação Rápida</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cliente->comodatos as $c)
                            <tr class="animate-fadeIn">
                                <td class="font-medium text-white">{{ $c->nome_equipamento }}</td>
                                <td>{{ $c->quantidade }} un</td>
                                <td>{{ $c->produto?->nome ?? '—' }}</td>
                                <td>A cada {{ $c->periodicidade_dias }} dias</td>
                                <td>
                                    <span class="font-semibold {{ $c->data_proxima_reposicao->isPast() ? 'text-red-400' : ($c->data_proxima_reposicao->diffInDays(now()) <= 2 ? 'text-amber-400' : 'text-emerald-400') }}">
                                        {{ $c->data_proxima_reposicao->format('d/m/Y') }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <form action="{{ route('comodatos.reposicao', $c) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary btn-sm text-xs" title="Registrar que as iscas foram trocadas hoje">
                                            ✓ Registrar Troca
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Histórico de Visitas / O.S. --}}
    <div class="card p-6">
        <h3 class="text-base font-semibold text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5" style="color: var(--color-primary-400);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Histórico de Ordens de Serviço & Visitas
        </h3>

        @if($cliente->visitas->isEmpty())
            <div class="empty-state py-6">
                <div class="empty-state-icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <h4 class="empty-state-title">Nenhuma visita registrada</h4>
                <p class="empty-state-desc">Este cliente ainda não possui ordens de serviço executadas ou agendadas.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($cliente->visitas as $v)
                    <div class="p-4 rounded-lg transition" style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-white">{{ $v->data_hora->format('d/m/Y H:i') }}</span>
                                <span class="badge badge-neutral">{{ $v->tipoVisitaLabel() }}</span>
                                <span class="badge badge-neutral">{{ $v->tipoServicoLabel() }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                @php
                                    $vBadge = match($v->status) {
                                        'concluida' => 'badge-success',
                                        'em_andamento' => 'badge-warning',
                                        'cancelada' => 'badge-danger',
                                        default => 'badge-info',
                                    };
                                @endphp
                                <span class="badge {{ $vBadge }}">
                                    {{ $v->statusLabel() }}
                                </span>
                                <a href="{{ route('visitas.show', $v) }}" class="btn btn-secondary btn-sm text-xs">Ver OS</a>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs" style="color: var(--color-surface-400);">
                            <div>Técnico: <span class="text-white">{{ $v->tecnico?->nome ?? '—' }}</span></div>
                            <div>Alvo: <span class="text-white">{{ $v->tipo_praga ?? '—' }}</span></div>
                            <div>Área tratada: <span class="text-white">{{ $v->area_tratada ?? '—' }}</span></div>
                        </div>

                        @if($v->produtos->isNotEmpty())
                            <div class="mt-2 pt-2 border-t text-xs" style="border-color: var(--color-surface-800);">
                                <span class="font-medium text-white">Insumos aplicados: </span>
                                @foreach($v->produtos as $p)
                                    <span class="inline-block px-1.5 py-0.5 rounded bg-surface-800 text-surface-300 mr-1 mt-1">
                                        {{ $p->nome }} ({{ number_format($p->pivot->quantidade_aplicada ?? $p->pivot->quantidade ?? 0, 2, ',', '.') }} {{ $p->unidade_aplicacao ?? $p->unidade }})
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
