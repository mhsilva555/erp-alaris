@extends('layouts.app')

@section('title', 'Meu Perfil & Gestão de Acessos')

@section('content')
    <div class="max-w-4xl space-y-8">

        {{-- Dados Pessoais --}}
        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold text-white">Informações da Minha Conta</h2>
                    <p class="text-xs" style="color: var(--color-surface-400);">Atualize seus dados cadastrais e senha de
                        acesso.</p>
                </div>
                <span class="badge {{ $user->isAdmin() ? 'badge-success' : 'badge-info' }} uppercase font-bold">
                    Perfil: {{ $user->roleLabel() }}
                </span>
            </div>

            <form method="POST" action="{{ route('perfil.update') }}" class="space-y-4">
                @csrf @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="name">Nome *</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
                            class="form-input" required>
                    </div>

                    <div>
                        <label class="form-label" for="email">E-mail *</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                            class="form-input" required>
                    </div>
                </div>

                <div class="pt-4 border-t" style="border-color: var(--color-surface-800);">
                    <h3 class="text-sm font-semibold text-white mb-3">Alterar Senha <span class="text-xs font-normal"
                            style="color: var(--color-surface-400);">(Deixe em branco para não alterar)</span></h3>

                    <div class="space-y-3">
                        <div>
                            <label class="form-label" for="current_password">Senha Atual</label>
                            <input type="password" id="current_password" name="current_password" class="form-input"
                                placeholder="••••••••">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label" for="password">Nova Senha</label>
                                <input type="password" id="password" name="password" class="form-input"
                                    placeholder="••••••••">
                            </div>

                            <div>
                                <label class="form-label" for="password_confirmation">Confirmar Nova Senha</label>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                    class="form-input" placeholder="••••••••">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>

        {{-- Gestão de Usuários & Perfis --}}
        @algumapermissao(['usuarios.ver', 'usuarios.criar', 'usuarios.deletar', 'perfis.ver'])
            <div class="card p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-base font-semibold text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Provisionamento de Acessos & Usuários Técnicos
                        </h2>
                        <p class="text-xs mt-0.5" style="color: var(--color-surface-400);">
                            Crie contas de login para a equipe técnica de campo com permissões restritas às suas próprias
                            OS.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        @permissao('perfis.ver')
                            <a href="{{ route('perfis.index') }}" class="btn btn-secondary btn-sm">
                                <svg class="w-4 h-4 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                Perfis & Permissões
                            </a>
                        @endpermissao
                        @permissao('usuarios.criar')
                            <button type="button" onclick="document.getElementById('form-novo-usuario').classList.toggle('hidden')"
                                class="btn btn-primary btn-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Novo Acesso
                            </button>
                        @endpermissao
                    </div>
                </div>

                @permissao('usuarios.criar')
                    {{-- Formulário de Criação de Usuário --}}
                    <div id="form-novo-usuario" class="hidden mb-6 p-4 rounded-xl"
                        style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-400 mb-3">Cadastrar Novo Usuário</h3>

                        <form method="POST" action="{{ route('perfil.usuarios.store') }}" class="space-y-4">
                            @csrf

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="form-label" for="novo_name">Nome *</label>
                                    <input type="text" id="novo_name" name="name" class="form-input" required>
                                </div>

                                <div>
                                    <label class="form-label" for="novo_email">E-mail de Login *</label>
                                    <input type="email" id="novo_email" name="email" class="form-input" required>
                                </div>

                                <div>
                                    <label class="form-label" for="novo_password">Senha Inicial *</label>
                                    <input type="password" id="novo_password" name="password" class="form-input"
                                        placeholder="Mínimo 8 dígitos" required>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="form-label" for="novo_perfil_id">Perfil de Acesso *</label>
                                    <select id="novo_perfil_id" name="perfil_id" class="form-select"
                                        onchange="toggleTecnicoSelect(this.options[this.selectedIndex].getAttribute('data-slug'))" required>
                                        @foreach ($perfis as $prf)
                                            <option value="{{ $prf->id }}" data-slug="{{ $prf->slug }}" {{ $prf->slug === 'tecnico' ? 'selected' : '' }}>
                                                {{ $prf->nome }} {{ $prf->descricao ? '— ' . $prf->descricao : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="div-tecnico-vinculo">
                                    <label class="form-label" for="novo_tecnico_id">Vincular ao Técnico Cadastrado *</label>
                                    <select id="novo_tecnico_id" name="tecnico_id" class="form-select">
                                        <option value="">Selecione o técnico correspondente</option>
                                        @foreach ($tecnicos as $tec)
                                            <option value="{{ $tec->id }}">{{ $tec->nome }}
                                                ({{ $tec->especialidade ?? 'Geral' }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button"
                                    onclick="document.getElementById('form-novo-usuario').classList.add('hidden')"
                                    class="btn btn-secondary btn-sm">
                                    Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    Criar Acesso
                                </button>
                            </div>
                        </form>
                    </div>
                @endpermissao

                {{-- Lista de Usuários do Sistema --}}
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nome / Usuário</th>
                                <th>E-mail</th>
                                <th>Perfil</th>
                                <th>Técnico Vinculado</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($usuarios as $usr)
                                <tr class="animate-fadeIn">
                                    <td>
                                        <strong class="text-white text-xs block">{{ $usr->name }}</strong>
                                        @if ($usr->id === $user->id)
                                            <span class="text-[10px] text-emerald-400 font-bold">Você (Sessão Atual)</span>
                                        @endif
                                    </td>
                                    <td class="text-xs " style="color: var(--color-surface-300);">{{ $usr->email }}
                                    </td>
                                    <td>
                                        <span
                                            class="badge {{ $usr->isAdmin() ? 'badge-success' : 'badge-info' }} uppercase font-bold">
                                            {{ $usr->roleLabel() }}
                                        </span>
                                    </td>
                                    <td class="text-xs" style="color: var(--color-surface-300);">
                                        {{ $usr->tecnico?->nome ?? ($usr->isAdmin() ? '—' : 'Não vinculado') }}
                                    </td>
                                    <td class="text-right">
                                        @if ($usr->id !== $user->id)
                                            @permissao('usuarios.deletar')
                                                <form id="del-usr-{{ $usr->id }}"
                                                    action="{{ route('perfil.usuarios.destroy', $usr) }}" method="POST"
                                                    class="inline">
                                                    @csrf @method('DELETE')
                                                </form>
                                                <button onclick="confirmDelete('del-usr-{{ $usr->id }}')"
                                                    class="btn btn-danger-soft btn-sm text-xs">
                                                    Excluir
                                                </button>
                                            @endpermissao
                                        @else
                                            <span class="badge badge-neutral text-xs">Ativo</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <script>
                function toggleTecnicoSelect(role) {
                    const div = document.getElementById('div-tecnico-vinculo');
                    const select = document.getElementById('novo_tecnico_id');
                    if (role === 'tecnico') {
                        div.style.display = 'block';
                        select.setAttribute('required', 'required');
                    } else {
                        div.style.display = 'none';
                        select.removeAttribute('required');
                        select.value = '';
                    }
                }
            </script>
        @endalgumapermissao

    </div>
@endsection
