@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navegação da paginação" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
        {{-- Resumo da Contagem de Registros --}}
        <div class="text-surface-400 text-center sm:text-left select-none">
            @if ($paginator->firstItem())
                Mostrando
                <span class="font-bold text-slate-800 dark:text-white">{{ $paginator->firstItem() }}</span>
                a
                <span class="font-bold text-slate-800 dark:text-white">{{ $paginator->lastItem() }}</span>
                de
                <span class="font-bold text-slate-800 dark:text-white">{{ $paginator->total() }}</span>
                {{ $paginator->total() === 1 ? 'resultado' : 'resultados' }}
            @else
                Mostrando
                <span class="font-bold text-slate-800 dark:text-white">{{ $paginator->count() }}</span>
                {{ $paginator->count() === 1 ? 'resultado' : 'resultados' }}
            @endif
        </div>

        {{-- Controles de Paginação --}}
        <div class="flex items-center justify-center sm:justify-end gap-1 select-none">
            {{-- Botão Anterior --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Página anterior"
                    class="h-9 px-2.5 sm:px-3 rounded-lg border text-xs font-medium inline-flex items-center gap-1.5 opacity-40 cursor-not-allowed"
                    style="border-color: var(--color-border); background: var(--color-surface); color: var(--color-slate-400);">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span class="hidden sm:inline">Anterior</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Página anterior"
                    class="h-9 px-2.5 sm:px-3 rounded-lg border text-xs font-medium inline-flex items-center gap-1.5 transition-colors shadow-2xs"
                    style="border-color: var(--color-border); background: var(--color-surface); color: var(--color-ink-700);"
                    onmouseover="this.style.background='var(--color-surface-subtle)'; this.style.borderColor='var(--color-border-strong)';"
                    onmouseout="this.style.background='var(--color-surface)'; this.style.borderColor='var(--color-border)';">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span class="hidden sm:inline">Anterior</span>
                </a>
            @endif

            {{-- Elementos Numéricos --}}
            @foreach ($elements as $element)
                {{-- Separador de Reticências "..." --}}
                @if (is_string($element))
                    <span aria-disabled="true" class="w-8 h-9 flex items-center justify-center font-bold text-xs" style="color: var(--color-slate-400);">
                        &hellip;
                    </span>
                @endif

                {{-- Links de Páginas --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                class="min-w-[2.25rem] h-9 px-2.5 rounded-lg text-xs font-bold text-white flex items-center justify-center shadow-xs"
                                style="background: var(--color-primary-600); box-shadow: 0 2px 8px rgba(35, 180, 235, 0.35);">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" aria-label="Ir para a página {{ $page }}"
                                class="min-w-[2.25rem] h-9 px-2.5 rounded-lg border text-xs font-medium flex items-center justify-center transition-colors shadow-2xs"
                                style="border-color: var(--color-border); background: var(--color-surface); color: var(--color-ink-700);"
                                onmouseover="this.style.background='var(--color-surface-subtle)'; this.style.borderColor='var(--color-border-strong)';"
                                onmouseout="this.style.background='var(--color-surface)'; this.style.borderColor='var(--color-border)';">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Botão Próxima --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Próxima página"
                    class="h-9 px-2.5 sm:px-3 rounded-lg border text-xs font-medium inline-flex items-center gap-1.5 transition-colors shadow-2xs"
                    style="border-color: var(--color-border); background: var(--color-surface); color: var(--color-ink-700);"
                    onmouseover="this.style.background='var(--color-surface-subtle)'; this.style.borderColor='var(--color-border-strong)';"
                    onmouseout="this.style.background='var(--color-surface)'; this.style.borderColor='var(--color-border)';">
                    <span class="hidden sm:inline">Próxima</span>
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="Próxima página"
                    class="h-9 px-2.5 sm:px-3 rounded-lg border text-xs font-medium inline-flex items-center gap-1.5 opacity-40 cursor-not-allowed"
                    style="border-color: var(--color-border); background: var(--color-surface); color: var(--color-slate-400);">
                    <span class="hidden sm:inline">Próxima</span>
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
