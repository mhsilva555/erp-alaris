@extends('layouts.app')

@section('title', 'Detalhes da Visita #' . $visita->id)

@section('content')
    <div class="space-y-6">

        {{-- Header Card Padronizado com Breadcrumb e Ações Operacionais --}}
        <div class="card p-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider mb-1.5" style="color: var(--color-surface-400);">
                        <a href="{{ route('visitas.index') }}" class="hover:underline flex items-center gap-1" style="color: var(--color-primary-600);">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Voltar para Visitas
                        </a>
                        <span>/</span>
                        <span>Ordem de Serviço #{{ $visita->id }}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
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
                        @if ($visita->contrato && $visita->contrato->comodatos->count() > 1 && $visita->comodato)
                            <a href="{{ route('contratos.show', $visita->contrato) }}" class="badge badge-purple hover:underline" title="Ver comodatos do contrato">
                                Reposição Unificada ({{ $visita->contrato->comodatos->count() }} comodatos)
                            </a>
                        @elseif ($visita->comodato)
                            <a href="{{ route('comodatos.show', $visita->comodato) }}" class="badge badge-purple hover:underline" title="Ver detalhes do comodato vinculado">
                                Reposição Comodato: {{ $visita->comodato->nome_equipamento }}
                            </a>
                        @endif
                    </div>

                    <p class="text-xs mt-1.5 flex items-center gap-1.5" style="color: var(--color-surface-400);">
                        <svg class="w-4 h-4 shrink-0" style="color: var(--color-primary-500);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Data do Agendamento: <strong class="text-white font-semibold">{{ $visita->data_hora->format('d/m/Y \à\s H:i') }}</strong>
                    </p>
                </div>

                {{-- Barra de Botões Padronizados --}}
                <div class="flex flex-wrap items-center gap-2">
                    @permissao('visitas.finalizar')
                    @if ($visita->status !== 'concluida' && $visita->status !== 'cancelada')
                        <button type="button" onclick="openFinalizarModal()" class="btn btn-success btn-sm font-semibold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Finalizar Visita
                        </button>
                    @endif
                    @endpermissao

                    @permissao('visitas.gerar_os')
                    <a href="{{ route('visitas.os', $visita) }}" class="btn btn-secondary btn-sm">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Imprimir OS Técnica
                    </a>
                    @endpermissao

                    @permissao('visitas.editar')
                    @if ($visita->status !== 'cancelada')
                        <form id="cancelar-visita-{{ $visita->id }}" action="{{ route('visitas.cancelar', $visita) }}"
                            method="POST" class="inline">
                            @csrf
                        </form>
                        <button type="button"
                            onclick="if(confirm('Tem certeza que deseja cancelar esta Ordem de Serviço? Os insumos alocados voltarão automaticamente ao estoque disponível.')) document.getElementById('cancelar-visita-{{ $visita->id }}').submit();"
                            class="btn btn-warning-soft btn-sm font-semibold" title="Cancelar OS e devolver insumos ao estoque">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                            Cancelar OS
                        </button>
                    @endif
                    <a href="{{ route('visitas.edit', $visita) }}" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Editar
                    </a>
                    @endpermissao

                    @permissao('visitas.deletar')
                        <form id="delete-visita-{{ $visita->id }}" action="{{ route('visitas.destroy', $visita) }}"
                            method="POST" class="inline">
                            @csrf @method('DELETE')
                        </form>
                        <button type="button" onclick="confirmDelete('delete-visita-{{ $visita->id }}')" class="btn btn-danger-soft btn-sm"
                            title="Excluir">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Excluir
                        </button>
                    @endpermissao
                </div>
            </div>
        </div>

        {{-- Card de Dados Operacionais da Visita --}}
        <div class="card">
            <div class="card-header">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <svg class="w-4 h-4" style="color: var(--color-primary-500);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Informações Operacionais do Atendimento
                </h3>
            </div>

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 text-sm">
                    {{-- Cliente --}}
                    <div class="p-4 rounded-xl border" style="background: var(--color-surface-950); border-color: var(--color-border);">
                        <span class="stat-label mb-1">Cliente</span>
                        <a href="{{ route('clientes.show', $visita->cliente) }}"
                            class="font-bold text-sm hover:underline block" style="color: var(--color-primary-600);">
                            {{ $visita->cliente->nome }}
                        </a>
                        <p class="text-xs mt-1" style="color: var(--color-surface-400);">
                            {{ $visita->cliente->telefone_formatado ?? $visita->cliente->telefone ?? 'Sem telefone' }} • {{ $visita->cliente->cidade_estado }}
                        </p>
                    </div>

                    {{-- Técnico Responsável --}}
                    <div class="p-4 rounded-xl border" style="background: var(--color-surface-950); border-color: var(--color-border);">
                        <span class="stat-label mb-1">Técnico Responsável</span>
                        <p class="text-white font-bold text-sm">{{ $visita->tecnico?->nome ?? 'Não atribuído' }}</p>
                        <p class="text-xs mt-1" style="color: var(--color-surface-400);">
                            Especialidade: {{ $visita->tecnico?->especialidade ?? 'Geral' }}
                        </p>
                    </div>

                    {{-- Contrato de Origem --}}
                    @if ($visita->contrato)
                        <div class="p-4 rounded-xl border" style="background: var(--color-surface-950); border-color: var(--color-border);">
                            <span class="stat-label mb-1">Contrato de Origem</span>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="badge badge-neutral text-[11px]">{{ $visita->contrato->numero }}</span>
                                <a href="{{ route('contratos.show', $visita->contrato) }}"
                                    class="font-bold text-sm hover:underline truncate" style="color: var(--color-primary-600);">
                                    {{ $visita->contrato->titulo ?: 'Contrato Recorrente' }}
                                </a>
                            </div>
                            <p class="text-xs mt-1" style="color: var(--color-surface-400);">
                                Periodicidade: {{ $visita->contrato->periodicidadeLabel() }} • {{ $visita->contrato->vigenciaLabel() }}
                            </p>
                        </div>
                    @endif

                    @if ($visita->tipo_praga)
                        <div class="p-4 rounded-xl border" style="background: var(--color-surface-950); border-color: var(--color-border);">
                            <span class="stat-label mb-1">Alvo / Tipo de Praga</span>
                            <p class="text-white font-semibold text-sm">{{ $visita->tipo_praga }}</p>
                        </div>
                    @endif

                    @if ($visita->area_tratada)
                        <div class="p-4 rounded-xl border" style="background: var(--color-surface-950); border-color: var(--color-border);">
                            <span class="stat-label mb-1">Área / Metragem</span>
                            <p class="text-white font-semibold text-sm">{{ $visita->area_tratada }}</p>
                        </div>
                    @endif

                    @if ($visita->proxima_visita)
                        <div class="p-4 rounded-xl border" style="background: var(--color-surface-950); border-color: var(--color-border);">
                            <span class="stat-label mb-1">Próxima Visita / Retorno</span>
                            <p class="text-white font-semibold text-sm">{{ $visita->proxima_visita->format('d/m/Y H:i') }}</p>
                        </div>
                    @endif
                </div>

                @if ($visita->contrato && $visita->contrato->comodatos->isNotEmpty())
                    <div class="pt-4 border-t" style="border-color: var(--color-border);">
                        <span class="stat-label mb-2">Equipamentos em Comodato Vinculados a este Atendimento</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($visita->contrato->comodatos as $cmd)
                                <a href="{{ route('comodatos.show', $cmd) }}"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs transition hover:border-sky-400"
                                    style="background: var(--color-surface-950); border-color: var(--color-border);">
                                    <span class="font-semibold text-white">{{ $cmd->nome_equipamento }}</span>
                                    <span class="badge badge-info text-[10px]">{{ $cmd->quantidade }} un</span>
                                    @if ($cmd->produto)
                                        <span style="color: var(--color-surface-400);">• {{ $cmd->produto->nome }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($visita->observacoes)
                    <div class="pt-4 border-t" style="border-color: var(--color-border);">
                        <span class="stat-label mb-1.5">Observações e Recomendações Técnicas</span>
                        <p class="text-sm whitespace-pre-line p-3.5 rounded-xl border"
                            style="background: var(--color-surface-950); color: var(--color-surface-300); border-color: var(--color-border);">{{ $visita->observacoes }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Financeiro Exclusivo do Gestor / Permissão Financeira --}}
        @permissao('visitas.acessar_dados')
            <div class="card">
                <div class="card-header">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Apuração Financeira & Lucratividade (Exclusivo Gestão)
                    </h3>
                    <span class="badge {{ $visita->exige_nota_fiscal ? 'badge-warning' : 'badge-neutral' }}">
                        {{ $visita->exige_nota_fiscal ? 'Exige NF (' . number_format($visita->aliquota_imposto, 1) . '%)' : 'Sem NF' }}
                    </span>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 sm:grid-cols-3 {{ $visita->gastos_adicionais > 0 ? 'xl:grid-cols-5' : 'xl:grid-cols-4' }} gap-3 sm:gap-4">
                        <div class="p-3.5 rounded-xl border"
                            style="background: var(--color-surface-950); border-color: var(--color-border);">
                            <span class="stat-label">Custo Insumos</span>
                            <span class="text-base font-bold text-white whitespace-nowrap block mt-1">R$
                                {{ number_format($visita->custo_produtos, 2, ',', '.') }}</span>
                        </div>
                        <div class="p-3.5 rounded-xl border"
                            style="background: var(--color-surface-950); border-color: var(--color-border);">
                            <span class="stat-label">Diária Técnico</span>
                            <span class="text-base font-bold text-white whitespace-nowrap block mt-1">R$
                                {{ number_format($visita->custo_mao_de_obra, 2, ',', '.') }}</span>
                        </div>
                        <div class="p-3.5 rounded-xl border"
                            style="background: var(--color-surface-950); border-color: var(--color-border);">
                            <span class="stat-label">Deslocamento</span>
                            <span class="text-base font-bold text-white whitespace-nowrap block mt-1">R$
                                {{ number_format($visita->custo_deslocamento, 2, ',', '.') }}</span>
                        </div>
                        @if ($visita->gastos_adicionais > 0)
                            <div class="p-3.5 rounded-xl border"
                                style="background: var(--color-surface-950); border-color: var(--color-border);">
                                <span class="stat-label">Gastos Adicionais</span>
                                <span class="text-base font-bold text-amber-500 whitespace-nowrap block mt-1">R$
                                    {{ number_format($visita->gastos_adicionais, 2, ',', '.') }}</span>
                                @if ($visita->motivo_gastos_adicionais)
                                    <span class="text-[11px] block truncate mt-0.5" style="color: var(--color-surface-400);" title="{{ $visita->motivo_gastos_adicionais }}">
                                        {{ $visita->motivo_gastos_adicionais }}
                                    </span>
                                @endif
                            </div>
                        @endif
                        <div class="p-3.5 rounded-xl border"
                            style="background: var(--color-surface-950); border-color: var(--color-border);">
                            <span class="stat-label">Imposto NF</span>
                            <span class="text-base font-bold text-amber-500 whitespace-nowrap block mt-1">R$
                                {{ number_format($visita->valor_imposto, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="pt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 border-t"
                        style="border-color: var(--color-border);">
                        <div class="p-4 rounded-xl border"
                            style="background: var(--color-surface-950); border-color: var(--color-border);">
                            <span class="stat-label">Custo Operacional Total</span>
                            <span class="text-lg font-bold text-rose-500 whitespace-nowrap block mt-1">R$
                                {{ number_format($visita->custoOperacionalTotal(), 2, ',', '.') }}</span>
                            <span class="text-[11px] block mt-0.5" style="color: var(--color-surface-400);">Insumos + Diária + Deslocamento</span>
                        </div>
                        <div class="p-4 rounded-xl border"
                            style="background: var(--color-surface-950); border-color: var(--color-border);">
                            <span class="stat-label">Valor Cobrado</span>
                            @if ($visita->contrato && (float) $visita->valor_cobrado == 0)
                                <div class="flex items-center gap-1.5 mt-1.5">
                                    <span class="badge badge-info text-xs">Incluso no Contrato</span>
                                </div>
                                <span class="text-[11px] block mt-1" style="color: var(--color-surface-400);">{{ $visita->contrato->modeloCobrancaLabel() }}: R$ {{ number_format((float) $visita->contrato->valor_total, 2, ',', '.') }}</span>
                            @else
                                <span class="text-lg font-bold text-emerald-500 whitespace-nowrap block mt-1">R$
                                    {{ number_format($visita->valor_cobrado, 2, ',', '.') }}</span>
                            @endif
                        </div>
                        <div class="p-4 rounded-xl border"
                            style="background: var(--color-surface-950); border-color: var(--color-border);">
                            <span class="stat-label">Lucro Bruto (Margem)</span>
                            @if ($visita->contrato && (float) $visita->valor_cobrado == 0)
                                <span class="text-sm font-bold block mt-1.5" style="color: var(--color-primary-600);">Faturamento Contratual</span>
                                <span class="text-[11px] block mt-0.5" style="color: var(--color-surface-400);">Rentabilidade apurada na mensalidade do contrato</span>
                            @else
                                <div class="flex items-baseline gap-2 mt-1">
                                    <span class="text-lg font-bold text-white whitespace-nowrap">R$
                                        {{ number_format($visita->lucro_bruto, 2, ',', '.') }}</span>
                                    <span
                                        class="text-xs font-semibold {{ $visita->margemPercentual() >= 40 ? 'text-emerald-500' : 'text-amber-500' }}">
                                        ({{ number_format($visita->margemPercentual(), 1) }}%)
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endpermissao

        {{-- Produtos / Insumos Utilizados --}}
        @if ($visita->produtos->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <svg class="w-4 h-4" style="color: var(--color-primary-500);" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Insumos & Defensivos Aplicados
                    </h3>
                </div>
                <div class="table-container border-0 rounded-none">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Princípio Ativo</th>
                                <th>Dose Aplicada</th>
                                <th>Baixa no Saldo</th>
                                @permissao('visitas.acessar_dados')
                                    <th class="text-right">Custo Proporcional</th>
                                @endpermissao
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
                                    <td class="text-emerald-500 font-semibold">
                                        {{ number_format($dose, 2, ',', '.') }} {{ $produto->unidade_aplicacao }}
                                    </td>
                                    @if ($visita->status === \App\Models\Visita::STATUS_CANCELADA)
                                        <td class="text-xs">
                                            <span class="badge badge-warning text-[11px]">
                                                Estornado ao estoque
                                            </span>
                                        </td>
                                    @else
                                        <td class="text-xs" style="color: var(--color-surface-300);">
                                            -{{ number_format($baixa, 4, ',', '.') }} {{ $produto->unidade_armazenamento }}
                                            @if (($produto->pivot->fracao_baixada ?? 0) > 0)
                                                <span class="text-[10px] text-emerald-500 font-semibold block">(Baixado do estoque)</span>
                                            @endif
                                        </td>
                                    @endif
                                    @permissao('visitas.acessar_dados')
                                        <td class="text-right font-medium text-white">
                                            R$ {{ number_format($produto->pivot->custo_proporcional ?? 0, 2, ',', '.') }}
                                        </td>
                                    @endpermissao
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    @if ($visita->status !== 'concluida')
        {{-- Modal Finalizar Visita --}}
        <div id="modal-finalizar-visita" class="modal-backdrop hidden"
            onclick="if(event.target === this) this.classList.add('hidden')">
            <div class="modal-card max-w-lg">
                <div class="card-header">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white">Finalizar Visita / Concluir OS #{{ $visita->id }}</h3>
                            <p class="text-xs" style="color: var(--color-surface-400);">{{ $visita->cliente->nome }}</p>
                        </div>
                    </div>
                    <button type="button" onclick="document.getElementById('modal-finalizar-visita').classList.add('hidden')"
                        class="btn btn-ghost btn-sm p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form action="{{ route('visitas.finalizar', $visita) }}" method="POST" class="p-6 space-y-4">
                    @csrf

                    {{-- Resumo da Conclusão --}}
                    <div class="p-3.5 rounded-lg border text-xs space-y-1.5" style="background: rgba(16, 185, 129, 0.08); border-color: rgba(16, 185, 129, 0.2);">
                        <div class="flex items-center gap-1.5 font-semibold text-emerald-500">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Rotinas automáticas ao confirmar:
                        </div>
                        <ul class="list-disc list-inside space-y-0.5" style="color: var(--color-surface-300);">
                            <li>O status da visita será atualizado para <strong class="text-emerald-500">Concluída</strong>.</li>
                            @if ($visita->produtos->isNotEmpty())
                                <li>Será efetuada a <strong>baixa fracionada de estoque</strong> dos {{ $visita->produtos->count() }} insumo(s) cadastrados.</li>
                            @endif
                            @permissao('visitas.acessar_dados')
                                @if ($visita->valor_cobrado > 0)
                                    <li>Lançará o contas a receber de <strong class="text-white">R$ {{ number_format($visita->valor_cobrado, 2, ',', '.') }}</strong> no financeiro.</li>
                                @endif
                            @endpermissao
                        </ul>
                    </div>

                    {{-- Observação da Execução --}}
                    <div>
                        <label class="form-label" for="finalizar_observacoes">Observações Técnicas da Execução</label>
                        <textarea name="observacoes" id="finalizar_observacoes" rows="3" class="form-input"
                            placeholder="Ex: Serviço concluído com sucesso. Áreas críticas inspecionadas e tratadas conforme procedimento padrão."></textarea>
                        <span class="text-[11px] block mt-1" style="color: var(--color-surface-400);">Opcional. Será registrado nas observações técnicas da visita.</span>
                    </div>

                    {{-- Gastos Adicionais --}}
                    <div class="pt-3 border-t" style="border-color: var(--color-border);">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold text-white flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Gastos Extras / Adicionais da Visita
                            </label>
                            <span class="text-[11px]" style="color: var(--color-surface-400);">Opcional</span>
                        </div>

                        @if ($visita->gastos_adicionais > 0)
                            <div class="mb-3 p-2.5 rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-300 dark:border-amber-500/30 text-xs text-amber-900 dark:text-amber-200 flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Gastos extras já registrados na abertura da OS: <strong class="text-amber-950 dark:text-white">R$ {{ number_format($visita->gastos_adicionais, 2, ',', '.') }}</strong>@if ($visita->motivo_gastos_adicionais) ({{ $visita->motivo_gastos_adicionais }})@endif. Deixe como está para manter, ou altere se necessário.</span>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label text-xs" for="gastos_adicionais">Valor Extra / Adicional (R$)</label>
                                <input type="number" name="gastos_adicionais" id="gastos_adicionais"
                                    step="0.01" min="0" value="{{ old('gastos_adicionais', $visita->gastos_adicionais > 0 ? number_format($visita->gastos_adicionais, 2, '.', '') : '0.00') }}"
                                    class="form-input" placeholder="0,00">
                            </div>
                            <div>
                                <label class="form-label text-xs" for="motivo_gastos_adicionais">Motivo / Descrição</label>
                                <input type="text" name="motivo_gastos_adicionais" id="motivo_gastos_adicionais"
                                    value="{{ old('motivo_gastos_adicionais', $visita->motivo_gastos_adicionais) }}"
                                    class="form-input" placeholder="Ex: Pedágio extra, estacionamento, compras...">
                            </div>
                        </div>
                        <p class="text-[11px] mt-1.5" style="color: var(--color-surface-400);">
                            Este valor será somado ao custo operacional da visita, lançado no fluxo de caixa e exibido na coluna Gastos Extras do DRE.
                        </p>
                    </div>

                    {{-- Botões de Ação --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t" style="border-color: var(--color-border);">
                        <button type="button" onclick="document.getElementById('modal-finalizar-visita').classList.add('hidden')"
                            class="btn btn-secondary btn-sm">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-success btn-sm font-semibold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Confirmar e Concluir Visita
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openFinalizarModal() {
                const modal = document.getElementById('modal-finalizar-visita');
                if (modal) {
                    modal.classList.remove('hidden');
                    const obsInput = document.getElementById('finalizar_observacoes');
                    if (obsInput) obsInput.focus();
                }
            }

            // Auto-abertura se parâmetro finalizar=1 vier na URL
            document.addEventListener('DOMContentLoaded', function() {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('finalizar') === '1') {
                    openFinalizarModal();
                }
            });
        </script>
    @endif
@endsection
