@extends('layouts.app')

@section('title', 'Fluxo de Caixa')

@section('content')
    <div class="space-y-6">

        {{-- Top Controls & Month Filter --}}
        <div class="card p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Fluxo de Caixa & Contas
                    </h2>
                    <p class="text-sm mt-1" style="color: var(--color-surface-400);">
                        Controle integrado de contas a receber e a pagar por competência e vencimento.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <form method="GET" action="{{ route('financeiro.fluxo') }}">
                        <input type="month" name="mes" value="{{ $mes }}" class="form-input text-sm py-1.5"
                            onchange="this.form.submit()">
                    </form>

                    <button type="button"
                        onclick="document.getElementById('modal-novo-lancamento').classList.remove('hidden')"
                        class="btn btn-primary text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Novo Lançamento
                    </button>
                </div>
            </div>

            {{-- Financial Stat Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Saldo Realizado --}}
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="stat-label">Saldo Realizado (Caixa)</span>
                            <div class="stat-value {{ $saldoAtual >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                R$ {{ number_format($saldoAtual, 2, ',', '.') }}
                            </div>
                            <div class="stat-helper">Receitas pagas menos despesas pagas</div>
                        </div>
                        <div class="stat-icon" style="background: rgba(16,185,129,.12); color: var(--color-success);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 6l3 18h12l3-18H3z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Receitas --}}
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="stat-label text-emerald-400">Receitas Realizadas</span>
                            <div class="stat-value text-emerald-400">
                                R$ {{ number_format($receitasPagas, 2, ',', '.') }}
                            </div>
                            <div class="stat-helper">A receber: <strong class="text-white">R$
                                    {{ number_format($receitasPendentes, 2, ',', '.') }}</strong></div>
                        </div>
                        <div class="stat-icon" style="background: rgba(16,185,129,.12); color: var(--color-success);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 11l5-5m0 0l5 5m-5-5v12" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Despesas --}}
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="stat-label text-rose-400">Despesas Pagas</span>
                            <div class="stat-value text-rose-400">
                                R$ {{ number_format($despesasPagas, 2, ',', '.') }}
                            </div>
                            <div class="stat-helper">A pagar: <strong class="text-white">R$
                                    {{ number_format($despesasPendentes, 2, ',', '.') }}</strong></div>
                        </div>
                        <div class="stat-icon" style="background: rgba(239,68,68,.12); color: var(--color-danger);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Saldo Projetado --}}
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="stat-label text-blue-400">Saldo Projetado Final</span>
                            <div class="stat-value {{ $saldoProjetado >= 0 ? 'text-blue-400' : 'text-rose-400' }}">
                                R$ {{ number_format($saldoProjetado, 2, ',', '.') }}
                            </div>
                            <div class="stat-helper">Considerando todos os pendentes</div>
                        </div>
                        <div class="stat-icon" style="background: rgba(43,143,255,.12); color: var(--color-primary-400);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Transactions Table --}}
        <div class="card overflow-hidden">
            <div class="card-header">
                <h3 class="text-sm font-semibold text-white">Extrato de Movimentações —
                    {{ \Carbon\Carbon::parse($mes . '-01')->translatedFormat('F \d\e Y') }}</h3>
                <span class="badge badge-neutral">{{ $movimentacoes->total() }} lançamentos</span>
            </div>

            @if ($movimentacoes->isEmpty())
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h4 class="empty-state-title">Nenhuma movimentação registrada</h4>
                    <p class="empty-state-desc">Não há receitas ou despesas lançadas no período selecionado.</p>
                    <button type="button"
                        onclick="document.getElementById('modal-novo-lancamento').classList.remove('hidden')"
                        class="btn btn-primary btn-sm mt-3">
                        Cadastrar Lançamento
                    </button>
                </div>
            @else
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Tipo</th>
                                <th>Categoria / Descrição</th>
                                <th>Vínculo / Cliente</th>
                                <th>Vencimento</th>
                                <th class="text-right">Valor (R$)</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($movimentacoes as $mov)
                                <tr class="animate-fadeIn">
                                    <td>
                                        @if ($mov->status === 'pago')
                                            <span class="badge badge-success">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Pago
                                            </span>
                                        @elseif($mov->status === 'pendente')
                                            <span class="badge badge-warning">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Pendente
                                            </span>
                                        @else
                                            <span class="badge badge-neutral">
                                                Cancelado
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span
                                            class="text-xs font-bold uppercase {{ $mov->tipo === 'receita' ? 'text-emerald-400' : 'text-rose-400' }}">
                                            {{ $mov->tipo }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-white block">{{ $mov->descricao }}</strong>
                                        <span class="text-xs"
                                            style="color: var(--color-surface-400);">{{ $mov->categoria }}</span>
                                    </td>
                                    <td>
                                        @if ($mov->visita)
                                            <a href="{{ route('visitas.show', $mov->visita) }}"
                                                class="text-emerald-400 hover:underline text-xs block font-medium">
                                                OS #{{ $mov->visita->id }} — {{ $mov->visita->cliente->nome }}
                                            </a>
                                        @elseif($mov->cliente)
                                            <a href="{{ route('clientes.show', $mov->cliente) }}"
                                                class="text-white hover:underline text-xs block">
                                                {{ $mov->cliente->nome }}
                                            </a>
                                        @else
                                            <span class="text-xs" style="color: var(--color-surface-500);">Geral /
                                                Operacional</span>
                                        @endif
                                    </td>
                                    <td class="text-xs " style="color: var(--color-surface-300);">
                                        {{ $mov->data_vencimento ? $mov->data_vencimento->format('d/m/Y') : ($mov->data_competencia ? $mov->data_competencia->format('d/m/Y') : '—') }}
                                    </td>
                                    <td
                                        class="text-right  font-bold {{ $mov->tipo === 'receita' ? 'text-emerald-400' : 'text-rose-400' }}">
                                        {{ $mov->tipo === 'receita' ? '+' : '-' }} R$
                                        {{ number_format($mov->valor, 2, ',', '.') }}
                                    </td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if ($mov->status === 'pendente')
                                                <form action="{{ route('financeiro.status', $mov) }}" method="POST"
                                                    class="inline">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="status" value="pago">
                                                    <button type="submit" class="btn btn-success-soft btn-sm"
                                                        title="Confirmar pagamento / recebimento">
                                                        Liquidar
                                                    </button>
                                                </form>
                                            @elseif($mov->status === 'pago')
                                                <form action="{{ route('financeiro.status', $mov) }}" method="POST"
                                                    class="inline">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="status" value="pendente">
                                                    <button type="submit" class="btn btn-ghost btn-sm"
                                                        title="Reabrir lançamento">
                                                        Reabrir
                                                    </button>
                                                </form>
                                            @endif

                                            <form id="del-mov-{{ $mov->id }}"
                                                action="{{ route('financeiro.destroy', $mov) }}" method="POST"
                                                class="inline">
                                                @csrf @method('DELETE')
                                            </form>
                                            <button onclick="confirmDelete('del-mov-{{ $mov->id }}')"
                                                class="btn btn-danger-soft btn-sm" title="Excluir">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4" style="border-top: 1px solid var(--color-surface-800);">
                    {{ $movimentacoes->appends(['mes' => $mes])->links() }}
                </div>
            @endif
        </div>

    </div>

    {{-- Modal Novo Lançamento --}}
    <div id="modal-novo-lancamento" class="modal-backdrop hidden"
        onclick="if(event.target === this) this.classList.add('hidden')">
        <div class="modal-card">
            <div class="card-header">
                <h3 class="text-base font-bold text-white">Novo Lançamento Financeiro</h3>
                <button type="button" onclick="document.getElementById('modal-novo-lancamento').classList.add('hidden')"
                    class="btn btn-ghost btn-sm p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('financeiro.store') }}" method="POST" class="p-6 space-y-4">
                @csrf

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label" for="tipo">Tipo *</label>
                        <select name="tipo" id="tipo" class="form-select" required>
                            <option value="receita">Receita (Entrada)</option>
                            <option value="despesa">Despesa (Saída)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="categoria">Categoria *</label>
                        <input type="text" name="categoria" id="categoria" class="form-input"
                            placeholder="Ex: Manutenção, Insumos" required>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="descricao">Descrição *</label>
                    <input type="text" name="descricao" id="descricao" class="form-input"
                        placeholder="Ex: Compra de defensivos ou Pagamento de diária" required>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label" for="valor">Valor (R$) *</label>
                        <input type="number" step="0.01" min="0.01" name="valor" id="valor"
                            class="form-input " placeholder="0.00" required>
                    </div>
                    <div>
                        <label class="form-label" for="status">Status *</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="pago">Já Liquidado / Pago</option>
                            <option value="pendente">Pendente / A Vencer</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label" for="data_competencia">Competência *</label>
                        <input type="date" name="data_competencia" id="data_competencia"
                            value="{{ now()->format('Y-m-d') }}" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label" for="data_vencimento">Vencimento</label>
                        <input type="date" name="data_vencimento" id="data_vencimento"
                            value="{{ now()->format('Y-m-d') }}" class="form-input">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label" for="cliente_id">Cliente (Opcional)</label>
                        <select name="cliente_id" id="cliente_id" class="form-select">
                            <option value="">Nenhum cliente</option>
                            @foreach ($clientes as $cli)
                                <option value="{{ $cli->id }}">{{ $cli->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="visita_id">OS Vinculada (Opcional)</label>
                        <select name="visita_id" id="visita_id" class="form-select">
                            <option value="">Nenhuma OS</option>
                            @foreach ($visitas as $vis)
                                <option value="{{ $vis->id }}">OS #{{ $vis->id }} — {{ $vis->cliente->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t"
                    style="border-color: var(--color-surface-800);">
                    <button type="button"
                        onclick="document.getElementById('modal-novo-lancamento').classList.add('hidden')"
                        class="btn btn-secondary">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Salvar Lançamento
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
