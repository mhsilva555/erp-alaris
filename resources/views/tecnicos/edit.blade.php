@extends('layouts.app')

@section('title', 'Editar Técnico')

@section('content')
<div class="max-w-lg">
    <div class="card p-6">
        <form method="POST" action="{{ route('tecnicos.update', $tecnico) }}" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <label class="form-label" for="nome">Nome *</label>
                <input type="text" id="nome" name="nome" value="{{ old('nome', $tecnico->nome) }}" class="form-input" required>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label" for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="{{ old('telefone', $tecnico->telefone_formatado ?? $tecnico->telefone) }}" class="form-input" data-mask="telefone" placeholder="(00) 00000-0000">
                </div>

                <div>
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $tecnico->email) }}" class="form-input">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label" for="especialidade">Especialidade</label>
                    <input type="text" id="especialidade" name="especialidade" value="{{ old('especialidade', $tecnico->especialidade) }}" class="form-input">
                </div>

                @permissao('tecnicos.acessar_dados')
                    <div>
                        <label class="form-label" for="valor_diaria_padrao">Valor Diária Padrão (R$)</label>
                        <input type="number" step="0.01" min="0" id="valor_diaria_padrao" name="valor_diaria_padrao" value="{{ old('valor_diaria_padrao', $tecnico->valor_diaria_padrao) }}" class="form-input" placeholder="50.00">
                    </div>
                @else
                    <input type="hidden" name="valor_diaria_padrao" value="{{ old('valor_diaria_padrao', $tecnico->valor_diaria_padrao) }}">
                @endpermissao
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="ativo" name="ativo" value="1" {{ old('ativo', $tecnico->ativo) ? 'checked' : '' }}
                       class="w-4 h-4 rounded" style="accent-color: var(--color-primary-600);">
                <label for="ativo" class="text-sm" style="color: var(--color-surface-300);">Técnico ativo</label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Atualizar
                </button>
                <a href="{{ route('tecnicos.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
