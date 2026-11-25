@php
    $gruposPorData = $itens->groupBy(fn ($item) => $item['data_hora']->format('Y-m-d'));
    $prefixoGrupo = $prefixoGrupo ?? 'agenda';
    $destaqueConcluida = $destaqueConcluida ?? false;
@endphp

<div class="space-y-6">
    @foreach ($gruposPorData as $dataStr => $itensDoDia)
        @php
            $dataObj = \Carbon\Carbon::parse($dataStr);
            $ehHoje = $dataObj->isToday();
            $ehPassado = $dataObj->isPast() && ! $ehHoje;
        @endphp

        <div class="card overflow-hidden {{ $destaqueConcluida ? 'opacity-90' : '' }}">
            {{-- Date Header --}}
            <div class="px-6 py-3.5 flex items-center justify-between"
                style="background: {{ $ehHoje && ! $destaqueConcluida ? 'rgba(16,185,129,0.1)' : 'var(--color-surface-900)' }}; border-bottom: 1px solid var(--color-surface-800);">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-bold {{ $ehHoje && ! $destaqueConcluida ? 'text-emerald-400' : 'text-white' }}">
                        {{ ucfirst($dataObj->translatedFormat('l, d \d\e F \d\e Y')) }}
                    </span>
                    @if ($ehHoje)
                        <span class="badge badge-success font-bold">
                            Hoje
                        </span>
                    @elseif ($ehPassado && ! $destaqueConcluida)
                        <span class="badge badge-neutral">
                            Passado
                        </span>
                    @endif
                </div>
                <span class="text-xs" style="color: var(--color-surface-400);">
                    {{ $itensDoDia->count() }} {{ $itensDoDia->count() === 1 ? 'item' : 'itens' }}
                </span>
            </div>

            {{-- Items List --}}
            <div class="divide-y" style="border-color: var(--color-border);">
                @foreach ($itensDoDia as $item)
                    @php
                        $itemConcluido = ! empty($item['eh_concluida']);
                        $statusVisita = $item['status_visita'] ?? ($item['model']->status ?? null);
                    @endphp
                    <div class="p-4 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                        style="border-color: var(--color-border);"
                        onmouseover="this.style.background='var(--color-surface-subtle)'"
                        onmouseout="this.style.background='transparent'">
                        <div class="flex items-start gap-3">
                            {{-- Type Icon --}}
                            <div
                                class="mt-0.5 w-9 h-9 rounded-lg flex items-center justify-center shrink-0
                                @if ($itemConcluido) badge-success
                                @elseif ($item['tipo_item'] === 'visita') badge-info
                                @elseif ($item['tipo_item'] === 'comodato') badge-cyan
                                @else badge-purple @endif">
                                @if ($itemConcluido)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                @elseif ($item['tipo_item'] === 'visita')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                @elseif ($item['tipo_item'] === 'comodato')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                @endif
                            </div>

                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span
                                        class="text-xs font-bold uppercase tracking-wider
                                        @if ($itemConcluido) text-emerald-400
                                        @elseif ($item['tipo_item'] === 'visita') {{ ($item['eh_retorno_preventivo'] ?? false) ? 'text-teal-400' : 'text-blue-400' }}
                                        @elseif ($item['tipo_item'] === 'comodato') text-sky-400
                                        @else text-purple-400 @endif">
                                        @if ($item['tipo_item'] === 'visita')
                                            @if ($item['eh_retorno_preventivo'] ?? false)
                                                Retorno Preventivo (Reforço)
                                            @elseif ($item['eh_contrato_projetado'] ?? false)
                                                Atendimento Contratual
                                            @elseif ($item['eh_recorrente_projetado'] ?? false)
                                                Visita Recorrente (Projetada)
                                            @else
                                                Ordem de Serviço
                                            @endif
                                        @elseif ($item['tipo_item'] === 'comodato')
                                            Reposição de Comodato
                                        @else
                                            Follow-up CRM
                                        @endif
                                    </span>
                                    <span class="text-xs" style="color: var(--color-surface-400);">
                                        {{ $item['data_hora']->format('H:i') }}
                                    </span>
                                    @if (($item['possui_comodato_vinculado'] ?? false) && $item['tipo_item'] === 'visita')
                                        <span class="badge badge-cyan text-[10px] py-0.5 px-1.5">
                                            + Comodato Incluso
                                        </span>
                                    @endif
                                </div>

                                <div class="text-sm font-semibold text-white mt-0.5">
                                    {{ $item['titulo'] }}
                                </div>

                                <div class="text-xs mt-0.5" style="color: var(--color-surface-400);">
                                    {{ $item['subtitulo'] }} • <span
                                        style="color: var(--color-surface-300);">{{ $item['detalhes'] }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-wrap sm:self-center ml-12 sm:ml-0">
                            @if ($item['eh_atrasada'] ?? false)
                                <span class="badge badge-danger text-[10px]">
                                    Em atraso
                                </span>
                            @endif

                            @if ($item['eh_retorno_preventivo'] ?? false)
                                <span class="badge badge-cyan font-medium flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Retorno Previsto
                                </span>
                            @elseif ($item['eh_recorrente_projetado'] ?? false)
                                <span class="badge badge-purple font-medium flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    {{ $item['status'] }}
                                </span>
                            @elseif ($item['status'] === 'Atrasado')
                                <span class="badge badge-danger">
                                    {{ $item['status'] }}
                                </span>
                            @elseif ($itemConcluido || $item['status'] === 'Concluída')
                                <span class="badge badge-success">
                                    {{ $item['status'] }}
                                </span>
                            @elseif ($statusVisita === \App\Models\Visita::STATUS_EM_ANDAMENTO)
                                <span class="badge badge-warning">
                                    {{ $item['status'] }}
                                </span>
                            @elseif ($statusVisita === \App\Models\Visita::STATUS_AGENDADA)
                                <span class="badge badge-info">
                                    {{ $item['status'] }}
                                </span>
                            @else
                                <span class="badge badge-neutral">
                                    {{ $item['status'] }}
                                </span>
                            @endif

                            @if (($item['eh_recorrente_projetado'] ?? false) && $item['tipo_item'] === 'visita')
                                @permissao('visitas.criar')
                                    <a href="{{ $item['url'] }}" class="btn btn-primary btn-sm text-xs">
                                        Agendar OS
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                @endpermissao
                            @elseif ($item['tipo_item'] === 'comodato')
                                @if (! empty($item['url_os']))
                                    @permissao('visitas.criar')
                                        <a href="{{ $item['url_os'] }}" class="btn btn-primary btn-sm text-xs"
                                            title="Criar Ordem de Serviço vinculada a esta reposição">
                                            Agendar OS
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    @endpermissao
                                @endif
                                <a href="{{ $item['url'] }}" class="btn btn-secondary btn-sm text-xs"
                                    title="Ver detalhes do comodato">
                                    Abrir
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            @else
                                <a href="{{ $item['url'] }}" class="btn btn-secondary btn-sm text-xs">
                                    Abrir
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            @endif

                            @if (auth()->user() &&
                                    (auth()->user()->temPermissao('agendamentos.deletar') ||
                                        ($item['tipo_item'] === 'visita' &&
                                            $item['model']->tecnico_id === auth()->user()->tecnico_id &&
                                            auth()->user()->temPermissao('visitas.deletar'))))
                                @if ($item['eh_retorno_preventivo'] ?? false)
                                    <form
                                        id="delete-agenda-{{ $prefixoGrupo }}-{{ $loop->parent->index }}-{{ $loop->index }}"
                                        action="{{ route('agendamentos.destroy', ['tipo' => 'retorno_preventivo', 'id' => $item['model']->id]) }}"
                                        method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    <button type="button"
                                        onclick="confirmDelete('delete-agenda-{{ $prefixoGrupo }}-{{ $loop->parent->index }}-{{ $loop->index }}')"
                                        class="btn btn-danger-soft btn-sm p-1.5"
                                        title="Remover retorno preventivo da agenda">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                @elseif (! ($item['eh_recorrente_projetado'] ?? false) || $item['tipo_item'] === 'comodato')
                                    <form
                                        id="delete-agenda-{{ $prefixoGrupo }}-{{ $loop->parent->index }}-{{ $loop->index }}"
                                        action="{{ route('agendamentos.destroy', ['tipo' => $item['tipo_item'], 'id' => $item['model']->id]) }}"
                                        method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    <button type="button"
                                        onclick="confirmDelete('delete-agenda-{{ $prefixoGrupo }}-{{ $loop->parent->index }}-{{ $loop->index }}')"
                                        class="btn btn-danger-soft btn-sm p-1.5"
                                        title="Remover evento da agenda">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
