@props([
    'name' => 'mes',
    'value' => null,
    'formId' => null,
    'align' => 'left',
])

@php
    $valorAtual = $value ?? request($name, now()->format('Y-m'));
    try {
        $dataObj = \Carbon\Carbon::parse($valorAtual . '-01');
    } catch (\Exception $e) {
        $dataObj = now();
        $valorAtual = now()->format('Y-m');
    }

    $mesesNomesCompletos = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];

    $mesesNomesAbrev = [
        1 => 'Jan',
        2 => 'Fev',
        3 => 'Mar',
        4 => 'Abr',
        5 => 'Mai',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Ago',
        9 => 'Set',
        10 => 'Out',
        11 => 'Nov',
        12 => 'Dez',
    ];

    $anoAtual = (int) $dataObj->year;
    $mesAtual = (int) $dataObj->month;
    $mesNomeFormatado = $mesesNomesCompletos[$mesAtual] . ' de ' . $anoAtual;

    $hojeAno = (int) now()->year;
    $hojeMes = (int) now()->month;
    $ehMesCorrente = $anoAtual === $hojeAno && $mesAtual === $hojeMes;

    $mesAnterior = $dataObj->copy()->subMonth()->format('Y-m');
    $proximoMes = $dataObj->copy()->addMonth()->format('Y-m');
    $pickerId = 'month-picker-' . uniqid();
@endphp

<div id="{{ $pickerId }}" class="relative inline-block text-left" data-mes-atual="{{ $valorAtual }}"
    data-ano-visualizado="{{ $anoAtual }}" data-hoje-ano="{{ $hojeAno }}" data-hoje-mes="{{ $hojeMes }}">
    {{-- Campo oculto que envia o valor no submit do form --}}
    <input type="hidden" name="{{ $name }}" value="{{ $valorAtual }}" class="month-picker-input">

    {{-- Container do Controle de Navegação Rápida --}}
    <div class="inline-flex items-center rounded-lg shadow-xs border bg-surface-900 border-surface-700/80 p-0.5"
        style="border-color: var(--color-border); background: var(--color-surface-900);">
        {{-- Botão Recuar 1 Mês --}}
        <button type="button" onclick="MonthPicker.mudarMes('{{ $pickerId }}', '{{ $mesAnterior }}')"
            title="Mês anterior ({{ $dataObj->copy()->subMonth()->translatedFormat('F/Y') }})"
            class="p-1.5 rounded-md hover:bg-surface-800 text-surface-400 hover:text-emerald-500 transition-colors"
            style="color: var(--color-surface-400);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        {{-- Botão Central Trigger --}}
        <button type="button" onclick="MonthPicker.toggle('{{ $pickerId }}')"
            class="px-2.5 py-1 flex items-center gap-2 text-xs font-semibold rounded-md hover:bg-surface-800 text-white transition-all select-none">
            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span class="tracking-wide text-slate-900 dark:text-white">{{ $mesNomeFormatado }}</span>
            @if ($ehMesCorrente)
                <span
                    class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-500/15 text-emerald-400">
                    Atual
                </span>
            @endif
            <svg class="w-3 h-3 text-slate-400 transition-transform month-picker-chevron" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        {{-- Botão Avançar 1 Mês --}}
        <button type="button" onclick="MonthPicker.mudarMes('{{ $pickerId }}', '{{ $proximoMes }}')"
            title="Próximo mês ({{ $dataObj->copy()->addMonth()->translatedFormat('F/Y') }})"
            class="p-1.5 rounded-md hover:bg-surface-800 text-surface-400 hover:text-emerald-500 transition-colors"
            style="color: var(--color-surface-400);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>

    {{-- Popover Dropdown Customizado --}}
    <div class="month-picker-popover hidden absolute {{ $align === 'right' ? 'right-0' : 'left-0' }} mt-2 w-72 rounded-xl shadow-2xl p-3 border z-50 animate-fadeIn"
        style="background: var(--color-surface-900); border-color: var(--color-border); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.4), 0 8px 10px -6px rgba(0,0,0,0.3);">

        {{-- Cabeçalho do Popover: Navegação por Ano --}}
        <div class="flex items-center justify-between pb-3 mb-2 border-b" style="border-color: var(--color-border);">
            <button type="button" onclick="MonthPicker.mudarAno('{{ $pickerId }}', -1)" title="Ano anterior"
                class="p-1 rounded-lg hover:bg-surface-800 text-surface-400 hover:text-emerald-500 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <div class="text-sm font-bold text-white tracking-wider flex items-center gap-1.5">
                <span class="month-picker-ano-label">{{ $anoAtual }}</span>
            </div>

            <button type="button" onclick="MonthPicker.mudarAno('{{ $pickerId }}', 1)" title="Próximo ano"
                class="p-1 rounded-lg hover:bg-surface-800 text-surface-400 hover:text-emerald-500 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        {{-- Grid dos 12 Meses --}}
        <div class="grid grid-cols-3 gap-1.5 month-picker-grid">
            @for ($m = 1; $m <= 12; $m++)
                @php
                    $mesPad = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
                    $mesValorLoop = "{$anoAtual}-{$mesPad}";
                    $isSelect = $mesValorLoop === $valorAtual;
                    $isHoje = $anoAtual === $hojeAno && $m === $hojeMes;
                @endphp
                <button type="button" data-mes="{{ $m }}"
                    onclick="MonthPicker.selecionarMes('{{ $pickerId }}', {{ $m }})"
                    class="month-btn py-2 px-2 text-xs font-semibold rounded-lg transition-all text-center relative
                        @if ($isSelect) bg-emerald-500 !text-white font-bold shadow-xs
                        @elseif ($isHoje)
                            border border-emerald-500/40 text-emerald-400 hover:bg-surface-800
                        @else
                            text-surface-300 hover:text-emerald-500 hover:bg-surface-800 @endif">
                    {{ $mesesNomesAbrev[$m] }}
                    @if ($isHoje && !$isSelect)
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 absolute top-1 right-1"></span>
                    @endif
                </button>
            @endfor
        </div>

        {{-- Rodapé: Atalhos --}}
        <div class="mt-3 pt-2.5 border-t flex items-center justify-between text-xs"
            style="border-color: var(--color-border);">
            <button type="button" onclick="MonthPicker.mudarMes('{{ $pickerId }}', '{{ now()->format('Y-m') }}')"
                class="text-emerald-400 hover:text-emerald-300 font-medium transition-colors flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Este Mês</span>
            </button>

            <button type="button" onclick="MonthPicker.close('{{ $pickerId }}')"
                class="text-surface-400 hover:text-surface-200 transition-colors">
                Fechar
            </button>
        </div>
    </div>
</div>

{{-- Script Global Inicializado uma única vez --}}
@once
    <script>
        window.MonthPicker = {
            nomesAbrev: ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],

            toggle(id) {
                const container = document.getElementById(id);
                if (!container) return;
                const popover = container.querySelector('.month-picker-popover');
                const chevron = container.querySelector('.month-picker-chevron');
                const isHidden = popover.classList.contains('hidden');

                // Fecha outros pickers abertos
                document.querySelectorAll('.month-picker-popover').forEach(p => p.classList.add('hidden'));
                document.querySelectorAll('.month-picker-chevron').forEach(c => c.style.transform = '');

                if (isHidden) {
                    popover.classList.remove('hidden');
                    if (chevron) chevron.style.transform = 'rotate(180deg)';
                } else {
                    popover.classList.add('hidden');
                    if (chevron) chevron.style.transform = '';
                }
            },

            close(id) {
                const container = document.getElementById(id);
                if (!container) return;
                const popover = container.querySelector('.month-picker-popover');
                const chevron = container.querySelector('.month-picker-chevron');
                if (popover) popover.classList.add('hidden');
                if (chevron) chevron.style.transform = '';
            },

            mudarMes(id, novoMes) {
                const container = document.getElementById(id);
                if (!container) return;
                const input = container.querySelector('.month-picker-input');
                if (input) {
                    input.value = novoMes;
                    const form = container.closest('form');
                    if (form) form.submit();
                }
            },

            mudarAno(id, delta) {
                const container = document.getElementById(id);
                if (!container) return;
                let anoVisualizado = parseInt(container.getAttribute('data-ano-visualizado'), 10) + delta;
                container.setAttribute('data-ano-visualizado', anoVisualizado);

                const labelAno = container.querySelector('.month-picker-ano-label');
                if (labelAno) labelAno.textContent = anoVisualizado;

                const mesAtualVal = container.getAttribute('data-mes-atual');
                const hojeAno = parseInt(container.getAttribute('data-hoje-ano'), 10);
                const hojeMes = parseInt(container.getAttribute('data-hoje-mes'), 10);

                const buttons = container.querySelectorAll('.month-picker-grid button');
                buttons.forEach((btn, index) => {
                    const mesNum = index + 1;
                    const mesPad = String(mesNum).padStart(2, '0');
                    const valorLoop = `${anoVisualizado}-${mesPad}`;
                    const isSelect = (valorLoop === mesAtualVal);
                    const isHoje = (anoVisualizado === hojeAno && mesNum === hojeMes);

                    btn.className =
                        'month-btn py-2 px-2 text-xs font-semibold rounded-lg transition-all text-center relative ';
                    if (isSelect) {
                        btn.className += 'bg-emerald-500 text-white font-bold shadow-xs';
                    } else if (isHoje) {
                        btn.className += 'border border-emerald-500/40 text-emerald-400 hover:bg-surface-800';
                    } else {
                        btn.className += 'text-surface-300 hover:text-emerald-500 hover:bg-surface-800';
                    }

                    btn.innerHTML = this.nomesAbrev[mesNum] + (isHoje && !isSelect ?
                        '<span class="w-1.5 h-1.5 rounded-full bg-emerald-400 absolute top-1 right-1"></span>' :
                        '');
                });
            },

            selecionarMes(id, mesNum) {
                const container = document.getElementById(id);
                if (!container) return;
                const ano = container.getAttribute('data-ano-visualizado');
                const mesPad = String(mesNum).padStart(2, '0');
                const novoValor = `${ano}-${mesPad}`;

                this.mudarMes(id, novoValor);
            }
        };

        // Fechar ao clicar fora
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[id^="month-picker-"]')) {
                document.querySelectorAll('.month-picker-popover').forEach(p => p.classList.add('hidden'));
                document.querySelectorAll('.month-picker-chevron').forEach(c => c.style.transform = '');
            }
        });

        // Fechar com tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.month-picker-popover').forEach(p => p.classList.add('hidden'));
                document.querySelectorAll('.month-picker-chevron').forEach(c => c.style.transform = '');
            }
        });
    </script>
@endonce
