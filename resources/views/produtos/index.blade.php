@extends('layouts.app')

@section('title', 'Produtos & Insumos Químicos')

@section('content')
    <div class="space-y-4">
        {{-- Header & Filtros --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('produtos.index') }}"
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex-1 max-w-md relative">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-surface-400);"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="busca" value="{{ request('busca') }}"
                        placeholder="Buscar insumos químicos, princípio ativo..." class="form-input !pl-10">
                </div>
                <div class="flex items-center gap-2">
                    <select name="natureza" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas as naturezas...</option>
                        <option value="liquido" {{ request('natureza') === 'liquido' ? 'selected' : '' }}>Líquidos (L / mL)
                        </option>
                        <option value="solido" {{ request('natureza') === 'solido' ? 'selected' : '' }}>Sólidos / Pós (Kg /
                            g)</option>
                        <option value="unidade" {{ request('natureza') === 'unidade' ? 'selected' : '' }}>Unidades / Peças
                        </option>
                    </select>
                    <a href="{{ route('produtos.create') }}" class="btn btn-primary shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Novo Insumo
                    </a>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Insumo / Princípio Ativo</th>
                        <th>Natureza & Conversão</th>
                        <th>Estoque Físico</th>
                        @if (auth()->user() && auth()->user()->isAdmin())
                            <th class="hidden md:table-cell">Custo Base</th>
                        @endif
                        <th class="hidden sm:table-cell">Mínimo</th>
                        <th>Nível</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($produtos as $produto)
                        <tr class="animate-fadeIn">
                            <td>
                                <span class="font-medium text-white">{{ $produto->nome }}</span>
                                @if ($produto->descricao)
                                    <p class="text-xs mt-0.5 truncate max-w-xs" style="color: var(--color-surface-400);">
                                        {{ $produto->descricao }}</p>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center gap-1.5">
                                    @php
                                        $natBadge = match ($produto->natureza) {
                                            'liquido' => 'badge-info',
                                            'solido' => 'badge-warning',
                                            default => 'badge-success',
                                        };
                                    @endphp
                                    <span class="badge {{ $natBadge }}">
                                        {{ ucfirst($produto->natureza ?? 'líquido') }}
                                    </span>
                                    <span class="text-[11px]" style="color: var(--color-surface-400);">
                                        1 {{ $produto->unidade_armazenamento ?? $produto->unidade }} =
                                        {{ number_format($produto->fator_conversao ?? 1000, 0, ',', '.') }}
                                        {{ $produto->unidade_aplicacao ?? 'mL' }}
                                    </span>
                                </div>
                            </td>
                            <td class=" text-sm">
                                <span
                                    class="font-bold text-white">{{ number_format($produto->quantidade_estoque, 2, ',', '.') }}</span>
                                <span class="text-xs"
                                    style="color: var(--color-surface-400);">{{ $produto->unidade_armazenamento ?? $produto->unidade }}</span>
                            </td>
                            @if (auth()->user() && auth()->user()->isAdmin())
                                <td class="hidden md:table-cell text-xs ">
                                    <span class="text-white font-medium">R$
                                        {{ number_format($produto->preco_custo_unitario, 2, ',', '.') }}</span>
                                    <span class="text-[10px] block" style="color: var(--color-surface-400);">
                                        por {{ $produto->unidade_armazenamento ?? $produto->unidade }}
                                    </span>
                                </td>
                            @endif
                            <td class="hidden sm:table-cell  text-xs" style="color: var(--color-surface-400);">
                                {{ number_format($produto->estoque_minimo, 2, ',', '.') }}
                                {{ $produto->unidade_armazenamento ?? $produto->unidade }}
                            </td>
                            <td>
                                @if ($produto->estoqueBaixo())
                                    <span class="badge badge-danger">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Crítico
                                    </span>
                                @else
                                    <span class="badge badge-success">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Normal
                                    </span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('produtos.edit', $produto) }}" class="btn btn-secondary btn-sm"
                                        title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    @if (auth()->user() && auth()->user()->isAdmin())
                                        <form id="delete-produto-{{ $produto->id }}"
                                            action="{{ route('produtos.destroy', $produto) }}" method="POST"
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                onclick="confirmDelete('delete-produto-{{ $produto->id }}')"
                                                class="btn btn-danger-soft btn-sm" title="Excluir">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user() && auth()->user()->isAdmin() ? 7 : 6 }}"
                                class="text-center py-12">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                    <h4 class="empty-state-title">Nenhum insumo encontrado</h4>
                                    <p class="empty-state-desc">Cadastre produtos e defensivos para controlar estoque e
                                        doses aplicadas em OS.</p>
                                    <a href="{{ route('produtos.create') }}"
                                        class="btn btn-primary btn-sm mt-3">Cadastrar Insumo</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($produtos->hasPages())
            <div class="pagination mt-4">
                {{ $produtos->links() }}
            </div>
        @endif
    </div>
@endsection
