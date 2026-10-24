@extends('layouts.app')

@section('title', 'Gestão de Equipamentos')

@section('content')
<div class="space-y-5">
    {{-- Indicadores do Inventário --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card p-4">
            <p class="text-xs font-medium" style="color: var(--color-surface-400);">Modelos Cadastrados</p>
            <p class="text-2xl font-bold text-white mt-1">{{ $totalEquipamentos }}</p>
            <span class="text-[11px]" style="color: var(--color-surface-400);">Itens no catálogo</span>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium" style="color: var(--color-surface-400);">Estoque Total Físico</p>
            <p class="text-2xl font-bold text-white mt-1">{{ number_format($totalEstoque, 0, ',', '.') }} <span class="text-xs font-normal" style="color: var(--color-surface-400);">un</span></p>
            <span class="text-[11px]" style="color: var(--color-surface-400);">Patrimônio total</span>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium text-amber-400">Em Clientes (Comodato)</p>
            <p class="text-2xl font-bold text-amber-300 mt-1">{{ number_format($totalComodato, 0, ',', '.') }} <span class="text-xs font-normal text-amber-400/70">un</span></p>
            <span class="text-[11px]" style="color: var(--color-surface-400);">Instalados em campo</span>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium text-emerald-400">Saldo Disponível p/ Alocação</p>
            <p class="text-2xl font-bold text-emerald-300 mt-1">{{ number_format($totalDisponivel, 0, ',', '.') }} <span class="text-xs font-normal text-emerald-400/70">un</span></p>
            <span class="text-[11px]" style="color: var(--color-surface-400);">Prontos no depósito</span>
        </div>
    </div>

    {{-- Filtros e Ações --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('equipamentos.index') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex-1 flex flex-col sm:flex-row gap-3">
                <div class="flex-1 max-w-md relative">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-surface-400);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Buscar por nome, modelo ou descrição..." class="form-input !pl-10">
                </div>

                <div class="w-full sm:w-56">
                    <select name="tipo" onchange="this.form.submit()" class="form-select">
                        <option value="">Todos os tipos</option>
                        @foreach($tipos as $valor => $rotulo)
                            <option value="{{ $valor }}" {{ request('tipo') === $valor ? 'selected' : '' }}>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if(request('busca') || request('tipo'))
                    <a href="{{ route('equipamentos.index') }}" class="btn btn-secondary text-xs">Limpar</a>
                @endif
                <button type="button" onclick="abrirModalCategorias()" class="btn btn-secondary shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    Categorias
                </button>
                <a href="{{ route('equipamentos.create') }}" class="btn btn-primary shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Novo Equipamento
                </a>
            </div>
        </form>
    </div>

    {{-- Tabela de Equipamentos --}}
    <div class="card table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Equipamento</th>
                    <th>Tipo</th>
                    <th class="text-center">Total Físico</th>
                    <th class="text-center">Em Comodato</th>
                    <th class="text-center">Disponível</th>
                    <th class="text-right hidden md:table-cell">Valor Unitário</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($equipamentos as $eq)
                    <tr class="animate-fadeIn">
                        <td>
                            <div class="font-semibold text-white">{{ $eq->nome }}</div>
                            @if($eq->modelo)
                                <div class="text-xs" style="color: var(--color-surface-400);">Modelo: {{ $eq->modelo }}</div>
                            @endif
                            @if($eq->descricao)
                                <p class="text-[11px] truncate max-w-xs mt-0.5" style="color: var(--color-surface-500);">{{ $eq->descricao }}</p>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-neutral text-xs">
                                {{ $eq->tipo_formatado }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="font-medium text-white">{{ $eq->quantidade_total }}</span>
                            <span class="text-xs" style="color: var(--color-surface-400);"> un</span>
                        </td>
                        <td class="text-center">
                            <span class="font-semibold {{ $eq->quantidade_em_comodato > 0 ? 'text-amber-400' : 'text-surface-400' }}">
                                {{ $eq->quantidade_em_comodato }}
                            </span>
                            <span class="text-xs" style="color: var(--color-surface-400);"> un</span>
                        </td>
                        <td class="text-center">
                            @if($eq->quantidade_disponivel > 0)
                                <span class="badge badge-success font-bold">
                                    {{ $eq->quantidade_disponivel }} un
                                </span>
                            @else
                                <span class="badge badge-danger font-bold">
                                    Esgotado (0 un)
                                </span>
                            @endif
                        </td>
                        <td class="text-right hidden md:table-cell">
                            @if($eq->valor_unitario)
                                <span class="text-xs text-white">R$ {{ number_format($eq->valor_unitario, 2, ',', '.') }}</span>
                            @else
                                <span class="text-xs" style="color: var(--color-surface-500);">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($eq->ativo)
                                <span class="badge badge-success text-[11px]">Ativo</span>
                            @else
                                <span class="badge badge-neutral text-[11px]">Inativo</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('comodatos.create') }}?equipamento_id={{ $eq->id }}" class="btn btn-secondary btn-sm text-xs" title="Alocar este equipamento em novo comodato" {{ $eq->quantidade_disponivel <= 0 ? 'disabled style=opacity:0.5;pointer-events:none;' : '' }}>
                                    + Comodato
                                </a>
                                <a href="{{ route('equipamentos.edit', $eq) }}" class="btn btn-secondary btn-sm" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </a>
                                <form action="{{ route('equipamentos.destroy', $eq) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja remover este equipamento?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Excluir">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-8" style="color: var(--color-surface-400);">
                            Nenhum equipamento cadastrado no inventário.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($equipamentos->hasPages())
        <div class="mt-4">
            {{ $equipamentos->links() }}
        </div>
    @endif
</div>

{{-- Modal de Gestão de Categorias --}}
<div id="modalCategorias" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/60 backdrop-blur-sm" onclick="if(event.target === this) fecharModalCategorias()">
    <div class="card max-w-lg w-full p-6 space-y-5 animate-fadeIn shadow-2xl relative" style="border: 1px solid var(--color-surface-700);">
        {{-- Cabeçalho do Modal --}}
        <div class="flex items-center justify-between pb-3" style="border-bottom: 1px solid var(--color-surface-800);">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: var(--color-surface-800); color: var(--color-primary-400);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">Categorias de Equipamentos</h3>
                    <p class="text-xs" style="color: var(--color-surface-400);">Classificação e organização dos itens do inventário</p>
                </div>
            </div>
            <button type="button" onclick="fecharModalCategorias()" class="text-surface-400 hover:text-white transition-colors p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Formulário para Adicionar Nova Categoria --}}
        <div class="p-4 rounded-lg" style="background: var(--color-surface-900); border: 1px solid var(--color-surface-800);">
            <h4 class="text-xs font-semibold text-white uppercase tracking-wider mb-2">Adicionar Nova Categoria</h4>
            <form id="formNovaCategoria" onsubmit="salvarNovaCategoria(event)" class="flex gap-2">
                @csrf
                <input type="text" id="nomeNovaCategoria" name="nome" placeholder="Ex: Gaiolas de Captura, Termonebulizadores..."
                       class="form-input !py-1.5 text-xs flex-1" required>
                <button type="submit" id="btnSalvarCategoria" class="btn btn-primary !py-1.5 text-xs shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Adicionar
                </button>
            </form>
            <p id="msgFeedbackCategoria" class="text-xs mt-2 hidden"></p>
        </div>

        {{-- Lista de Categorias Cadastradas --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-white uppercase tracking-wider">Categorias Cadastradas</span>
                <span id="contadorCategorias" class="text-xs" style="color: var(--color-surface-400);"></span>
            </div>
            <div id="listaCategorias" class="max-h-60 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
                <div class="text-center py-6 text-xs" style="color: var(--color-surface-400);">Carregando categorias...</div>
            </div>
        </div>

        {{-- Rodapé do Modal --}}
        <div class="pt-3 flex justify-end" style="border-top: 1px solid var(--color-surface-800);">
            <button type="button" onclick="fecharModalCategorias()" class="btn btn-secondary text-xs">Concluído</button>
        </div>
    </div>
</div>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    function abrirModalCategorias() {
        const modal = document.getElementById('modalCategorias');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        carregarCategorias();
        setTimeout(() => {
            document.getElementById('nomeNovaCategoria').focus();
        }, 100);
    }

    function fecharModalCategorias() {
        const modal = document.getElementById('modalCategorias');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.getElementById('msgFeedbackCategoria').classList.add('hidden');
        document.getElementById('nomeNovaCategoria').value = '';
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const modal = document.getElementById('modalCategorias');
            if (modal && !modal.classList.contains('hidden')) {
                fecharModalCategorias();
            }
        }
    });

    async function carregarCategorias() {
        const container = document.getElementById('listaCategorias');
        const contador = document.getElementById('contadorCategorias');

        try {
            const res = await fetch('{{ route('equipamentos.categorias.index') }}', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) throw new Error('Erro ao buscar categorias.');
            const data = await res.json();
            const categorias = data.categorias || [];

            contador.textContent = `${categorias.length} categoria(s)`;

            if (categorias.length === 0) {
                container.innerHTML = '<p class="text-center py-4 text-xs" style="color: var(--color-surface-400);">Nenhuma categoria cadastrada.</p>';
                return;
            }

            container.innerHTML = categorias.map(cat => {
                const qtdEquipamentos = cat.equipamentos_count || 0;
                const canDelete = qtdEquipamentos === 0;

                return `
                    <div class="flex items-center justify-between p-2.5 rounded-lg transition-colors" style="background: var(--color-surface-800);">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-white">${cat.nome}</span>
                            <span class="badge ${qtdEquipamentos > 0 ? 'badge-primary' : 'badge-neutral'} text-[10px]">
                                ${qtdEquipamentos} ${qtdEquipamentos === 1 ? 'item' : 'itens'}
                            </span>
                        </div>
                        <div>
                            ${canDelete ? `
                                <button type="button" onclick="excluirCategoria(${cat.id}, '${cat.nome.replace(/'/g, "\\'")}')"
                                        class="btn btn-danger btn-sm text-xs !p-1.5" title="Remover categoria sem equipamentos">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            ` : `
                                <span class="text-xs p-1 opacity-50 cursor-not-allowed" style="color: var(--color-surface-400);" title="Esta categoria possui equipamentos vinculados e não pode ser excluída">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </span>
                            `}
                        </div>
                    </div>
                `;
            }).join('');

        } catch (err) {
            container.innerHTML = `<p class="text-center py-4 text-xs text-red-400">${err.message}</p>`;
        }
    }

    async function salvarNovaCategoria(e) {
        e.preventDefault();
        const input = document.getElementById('nomeNovaCategoria');
        const btn = document.getElementById('btnSalvarCategoria');
        const feedback = document.getElementById('msgFeedbackCategoria');
        const nome = input.value.trim();

        if (!nome) return;

        btn.disabled = true;
        btn.classList.add('opacity-50');
        feedback.classList.add('hidden');

        try {
            const res = await fetch('{{ route('equipamentos.categorias.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ nome })
            });

            const data = await res.json();

            if (!res.ok) {
                const erro = data.errors?.nome?.[0] || data.mensagem || 'Erro ao cadastrar categoria.';
                feedback.textContent = erro;
                feedback.className = 'text-xs mt-2 text-red-400';
                feedback.classList.remove('hidden');
                return;
            }

            feedback.textContent = data.mensagem || 'Categoria adicionada com sucesso!';
            feedback.className = 'text-xs mt-2 text-emerald-400';
            feedback.classList.remove('hidden');
            input.value = '';

            // Recarregar a lista dentro do modal
            await carregarCategorias();

            // Adicionar ao select de filtro na página se não existir
            const selectFiltro = document.querySelector('select[name="tipo"]');
            if (selectFiltro && data.categoria) {
                const opt = document.createElement('option');
                opt.value = data.categoria.slug;
                opt.textContent = data.categoria.nome;
                selectFiltro.appendChild(opt);
            }

        } catch (err) {
            feedback.textContent = err.message || 'Falha de conexão ao salvar categoria.';
            feedback.className = 'text-xs mt-2 text-red-400';
            feedback.classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btn.classList.remove('opacity-50');
        }
    }

    async function excluirCategoria(id, nome) {
        if (!confirm(`Tem certeza que deseja remover a categoria "${nome}"?`)) {
            return;
        }

        try {
            const res = await fetch(`{{ url('/equipamentos/categorias') }}/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await res.json();

            if (!res.ok) {
                alert(data.mensagem || 'Não foi possível excluir esta categoria.');
                return;
            }

            // Recarregar lista do modal
            await carregarCategorias();

        } catch (err) {
            alert('Erro de conexão ao excluir a categoria.');
        }
    }
</script>
@endsection

