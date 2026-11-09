@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navegação da paginação simples" class="flex items-center justify-between text-xs select-none">
        {{-- Botão Anterior --}}
        @if ($paginator->onFirstPage())
            <span aria-disabled="true"
                class="h-9 px-3 rounded-lg border text-xs font-medium inline-flex items-center gap-1.5 opacity-40 cursor-not-allowed"
                style="border-color: var(--color-border); background: var(--color-surface); color: var(--color-slate-400);">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                <span>Anterior</span>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                class="h-9 px-3 rounded-lg border text-xs font-medium inline-flex items-center gap-1.5 transition-colors shadow-2xs"
                style="border-color: var(--color-border); background: var(--color-surface); color: var(--color-ink-700);"
                onmouseover="this.style.background='var(--color-surface-subtle)'; this.style.borderColor='var(--color-border-strong)';"
                onmouseout="this.style.background='var(--color-surface)'; this.style.borderColor='var(--color-border)';">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                <span>Anterior</span>
            </a>
        @endif

        {{-- Botão Próxima --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                class="h-9 px-3 rounded-lg border text-xs font-medium inline-flex items-center gap-1.5 transition-colors shadow-2xs"
                style="border-color: var(--color-border); background: var(--color-surface); color: var(--color-ink-700);"
                onmouseover="this.style.background='var(--color-surface-subtle)'; this.style.borderColor='var(--color-border-strong)';"
                onmouseout="this.style.background='var(--color-surface)'; this.style.borderColor='var(--color-border)';">
                <span>Próxima</span>
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        @else
            <span aria-disabled="true"
                class="h-9 px-3 rounded-lg border text-xs font-medium inline-flex items-center gap-1.5 opacity-40 cursor-not-allowed"
                style="border-color: var(--color-border); background: var(--color-surface); color: var(--color-slate-400);">
                <span>Próxima</span>
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>
        @endif
    </nav>
@endif
