@extends('layouts.app')

@section('title', 'Detalhes da Visita #' . $visita->id)

@section('content')
    <div class="space-y-6 max-w-4xl">

        {{-- Top Actions Bar --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('visitas.index') }}" class="btn btn-secondary text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Voltar para Visitas
            </a>

            <div class="flex flex-wrap items-center gap-2">
                {{-- OS button --}}
                <a href="{{ route('visitas.os', $visita) }}" target="_blank" class="btn btn-secondary text-sm">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Imprimir OS Técnica
                </a>

                {{-- Proposta Comercial button --}}
                <a href="{{ route('visitas.proposta', $visita) }}" target="_blank" class="btn btn-purple-soft text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Proposta Comercial (Sigilosa)
                </a>

                <a href="{{ route('visitas.edit', $visita) }}" class="btn btn-primary text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Editar
                </a>

                @if (auth()->user()->isAdmin())
                    <form id="delete-visita-{{ $visita->id }}" action="{{ route('visitas.destroy', $visita) }}"
                        method="POST" class="inline">
                        @csrf @method('DELETE')
                    </form>
                    <button onclick="confirmDelete('delete-visita-{{ $visita->id }}')" class="btn btn-danger-soft btn-sm"
                        title="Excluir">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Excluir
                    </button>
                @endif
            </div>
        </div>

        {{-- Main Visit Card --}}
        <div class="card p-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                <div>
                    <div class="flex flex-wrap items-center gap-3 mb-2">
                        <h2 class="text-xl font-bold text-white">Visita / OS #{{ $visita->id }}</h2>
                        @php
                            $vBadge = match ($visita->status) {
                                'concluida' => 'badge-success',
                                'em_andamento' => 'badge-warning',
                                'cancelada' => 'badge-danger',
                                default => 'badge-info',
                            };
                        @endphp
                        <span class="badge {{ $vBadge }}">
                            {{ $visita->statusLabel() }}
                        </span>
                        <span class="badge badge-neutral">
                            {{ $visita->tipoVisitaLabel() }}
                        </span>
                        <span class="badge badge-cyan">
                            {{ $visita->tipoServicoLabel() }}
                        </span>
                    </div>
                    <p class="text-sm" style="color: var(--color-surface-400);">
                        Data do Agendamento: <strong
                            class="text-white">{{ $visita->data_hora->format('d/m/Y \à\s H:i') }}</strong>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 text-sm">
                <div>
                    <span class="form-label">Cliente</span>
                    <a href="{{ route('clientes.show', $visita->cliente) }}"
                        class="text-emerald-400 hover:underline font-semibold block">{{ $visita->cliente->nome }}</a>
                    <p class="text-xs mt-0.5" style="color: var(--color-surface-400);">
                        {{ $visita->cliente->telefone ?? 'Sem telefone' }} • {{ $visita->cliente->cidade_estado }}</p>
                </div>
                <div>
                    <span class="form-label">Técnico Responsável</span>
                    <p class="text-white font-medium">{{ $visita->tecnico?->nome ?? 'Não atribuído' }}</p>
                    <p class="text-xs mt-0.5" style="color: var(--color-surface-400);">
                        {{ $visita->tecnico?->especialidade ?? 'Geral' }}</p>
                </div>
                @if ($visita->tipo_praga)
                    <div>
                        <span class="form-label">Alvo / Tipo de Praga</span>
                        <p class="text-white font-medium">{{ $visita->tipo_praga }}</p>
                    </div>
                @endif
                @if ($visita->area_tratada)
                    <div>
                        <span class="form-label">Área / Metragem</span>
                        <p class="text-white font-medium">{{ $visita->area_tratada }}</p>
                    </div>
                @endif
                @if ($visita->proxima_visita)
                    <div>
                        <span class="form-label">Próxima Visita / Retorno</span>
                        <p class="text-white font-medium">{{ $visita->proxima_visita->format('d/m/Y H:i') }}</p>
                    </div>
                @endif
            </div>

            @if ($visita->observacoes)
                <div class="mt-5 pt-5" style="border-top: 1px solid var(--color-surface-800);">
                    <span class="form-label">Observações e Recomendações Técnicas</span>
                    <p class="text-sm whitespace-pre-line p-3 rounded-lg"
                        style="background: var(--color-surface-950); color: var(--color-surface-300); border: 1px solid var(--color-surface-800);">
                        {{ $visita->observacoes }}</p>
                </div>
            @endif
        </div>

        {{-- Financeiro Exclusivo do Gestor / Admin --}}
        @if (auth()->user()->isAdmin())
            <div class="card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Apuração Financeira & Lucratividade (Exclusivo Gestão)
                    </h3>
                    <span class="badge {{ $visita->exige_nota_fiscal ? 'badge-warning' : 'badge-neutral' }}">
                        {{ $visita->exige_nota_fiscal ? 'Exige NF (' . number_format($visita->aliquota_imposto, 1) . '%)' : 'Sem NF' }}
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
                    <div class="p-3 rounded-lg"
                        style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                        <span class="text-xs block" style="color: var(--color-surface-400);">Custo Insumos</span>
                        <span class="text-base font-bold text-white ">R$
                            {{ number_format($visita->custo_produtos, 2, ',', '.') }}</span>
                    </div>
                    <div class="p-3 rounded-lg"
                        style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                        <span class="text-xs block" style="color: var(--color-surface-400);">Diária Técnico</span>
                        <span class="text-base font-bold text-white ">R$
                            {{ number_format($visita->custo_mao_de_obra, 2, ',', '.') }}</span>
                    </div>
                    <div class="p-3 rounded-lg"
                        style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                        <span class="text-xs block" style="color: var(--color-surface-400);">Deslocamento</span>
                        <span class="text-base font-bold text-white ">R$
                            {{ number_format($visita->custo_deslocamento, 2, ',', '.') }}</span>
                    </div>
                    <div class="p-3 rounded-lg"
                        style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                        <span class="text-xs block" style="color: var(--color-surface-400);">Imposto NF</span>
                        <span class="text-base font-bold text-amber-400 ">R$
                            {{ number_format($visita->valor_imposto, 2, ',', '.') }}</span>
                    </div>
                </div>

                <div class="pt-4 grid grid-cols-1 sm:grid-cols-3 gap-4"
                    style="border-top: 1px solid var(--color-surface-800);">
                    <div class="p-3 rounded-lg"
                        style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                        <span class="text-xs block" style="color: var(--color-surface-400);">Custo Operacional
                            Total</span>
                        <span class="text-lg font-bold text-rose-400 ">R$
                            {{ number_format($visita->custoOperacionalTotal(), 2, ',', '.') }}</span>
                    </div>
                    <div class="p-3 rounded-lg"
                        style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                        <span class="text-xs block" style="color: var(--color-surface-400);">Valor Cobrado</span>
                        <span class="text-lg font-bold text-emerald-400 ">R$
                            {{ number_format($visita->valor_cobrado, 2, ',', '.') }}</span>
                    </div>
                    <div class="p-3 rounded-lg"
                        style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                        <span class="text-xs block" style="color: var(--color-surface-400);">Lucro Bruto (Margem)</span>
                        <div class="flex items-baseline gap-2">
                            <span class="text-lg font-bold text-white ">R$
                                {{ number_format($visita->lucro_bruto, 2, ',', '.') }}</span>
                            <span
                                class="text-xs font-semibold {{ $visita->margemPercentual() >= 40 ? 'text-emerald-400' : 'text-amber-400' }}">
                                ({{ number_format($visita->margemPercentual(), 1) }}%)
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Produtos / Insumos Utilizados --}}
        @if ($visita->produtos->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                        <svg class="w-4 h-4" style="color: var(--color-primary-400);" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Insumos & Defensivos Aplicados
                    </h3>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Princípio Ativo</th>
                                <th>Dose Aplicada</th>
                                <th>Baixa no Saldo</th>
                                @if (auth()->user()->isAdmin())
                                    <th class="text-right">Custo Proporcional</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($visita->produtos as $produto)
                                @php
                                    $dose = $produto->pivot->quantidade_aplicada ?? ($produto->pivot->quantidade ?? 0);
                                    $baixa = $produto->fator_conversao > 0 ? $dose / $produto->fator_conversao : $dose;
                                @endphp
                                <tr class="animate-fadeIn">
                                    <td class="font-medium text-white">{{ $produto->nome }}</td>
                                    <td class="text-xs" style="color: var(--color-surface-400);">
                                        {{ $produto->principio_ativo ?? '—' }}</td>
                                    <td class=" text-emerald-400 font-semibold">
                                        {{ number_format($dose, 2, ',', '.') }} {{ $produto->unidade_aplicacao }}
                                    </td>
                                    <td class=" text-xs" style="color: var(--color-surface-300);">
                                        -{{ number_format($baixa, 4, ',', '.') }} {{ $produto->unidade_armazenamento }}
                                    </td>
                                    @if (auth()->user()->isAdmin())
                                        <td class=" text-right font-medium" style="color: var(--color-surface-200);">
                                            R$ {{ number_format($produto->pivot->custo_proporcional ?? 0, 2, ',', '.') }}
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
