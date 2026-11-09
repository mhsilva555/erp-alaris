@extends('layouts.app')

@section('title', "Editar Unidade: {$unidade->nome}")

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">
        <div>
            <a href="{{ route('unidades.index') }}"
                class="text-xs font-semibold text-primary-400 hover:underline flex items-center gap-1 mb-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Voltar para Unidades da Empresa
            </a>
            <h1 class="text-xl font-bold text-white">Editar Unidade: {{ $unidade->nome }}</h1>
            <p class="text-xs mt-1" style="color: var(--color-surface-400);">
                Atualize as informações empresariais, CNPJ e endereço desta unidade.
            </p>
        </div>

        <form method="POST" action="{{ route('unidades.update', $unidade) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="ativo_submitted" value="1">

            <div class="card p-6 space-y-5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-white" style="color: var(--color-primary-400);">
                    1. Identificação da Unidade & Dados Fiscais
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label for="nome" class="form-label">Nome de Identificação da Unidade *</label>
                        <input type="text" id="nome" name="nome" value="{{ old('nome', $unidade->nome) }}" required
                            class="form-input font-bold" placeholder="Ex: Matriz Imperatriz ou Filial Açailândia">
                    </div>

                    <div>
                        <label for="tipo" class="form-label">Tipo de Unidade *</label>
                        <select id="tipo" name="tipo" class="form-select" required>
                            @foreach (\App\Models\UnidadeEmpresa::TIPOS as $key => $label)
                                <option value="{{ $key }}"
                                    {{ old('tipo', $unidade->tipo) === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="razao_social" class="form-label">Razão Social Oficial *</label>
                        <input type="text" id="razao_social" name="razao_social"
                            value="{{ old('razao_social', $unidade->razao_social) }}" required
                            class="form-input font-bold">
                    </div>

                    <div>
                        <label for="nome_fantasia" class="form-label">Nome Fantasia / Marca *</label>
                        <input type="text" id="nome_fantasia" name="nome_fantasia"
                            value="{{ old('nome_fantasia', $unidade->nome_fantasia) }}" required
                            class="form-input">
                    </div>

                    <div>
                        <label for="cnpj" class="form-label">CNPJ da Unidade *</label>
                        <input type="text" id="cnpj" name="cnpj"
                            value="{{ old('cnpj', $unidade->cnpj_formatado) }}" required
                            class="form-input font-bold" placeholder="00.000.000/0000-00" data-mask="documento">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="licenca_sanitaria" class="form-label">Alvará Sanitário / Licença Ambiental</label>
                        <input type="text" id="licenca_sanitaria" name="licenca_sanitaria"
                            value="{{ old('licenca_sanitaria', $unidade->licenca_sanitaria) }}"
                            class="form-input">
                    </div>

                    <div>
                        <label for="telefone" class="form-label">Telefone / WhatsApp Comercial</label>
                        <input type="text" id="telefone" name="telefone"
                            value="{{ old('telefone', $unidade->telefone_formatado) }}"
                            class="form-input" placeholder="(00) 00000-0000" data-mask="telefone">
                    </div>

                    <div>
                        <label for="email" class="form-label">E-mail Comercial</label>
                        <input type="email" id="email" name="email"
                            value="{{ old('email', $unidade->email) }}"
                            class="form-input">
                    </div>

                    <div>
                        <label for="website" class="form-label">Website da Empresa</label>
                        <input type="text" id="website" name="website"
                            value="{{ old('website', $unidade->website) }}"
                            class="form-input">
                    </div>

                    <div class="sm:col-span-3">
                        <label for="slogan" class="form-label">Slogan / Ramo de Atuação</label>
                        <input type="text" id="slogan" name="slogan"
                            value="{{ old('slogan', $unidade->slogan) }}"
                            class="form-input">
                    </div>
                </div>
            </div>

            <div class="card p-6 space-y-5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-white" style="color: var(--color-primary-400);">
                    2. Endereço e Localização da Unidade
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label for="cep" class="form-label">CEP</label>
                        <input type="text" id="cep" name="cep"
                            value="{{ old('cep', $unidade->cep_formatado) }}"
                            class="form-input" placeholder="00000-000" data-mask="cep">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="endereco" class="form-label">Endereço (Logradouro, Número e Complemento)</label>
                        <input type="text" id="endereco" name="endereco"
                            value="{{ old('endereco', $unidade->endereco) }}"
                            class="form-input">
                    </div>

                    <div>
                        <label for="bairro" class="form-label">Bairro</label>
                        <input type="text" id="bairro" name="bairro"
                            value="{{ old('bairro', $unidade->bairro) }}"
                            class="form-input">
                    </div>

                    <div>
                        <label for="cidade" class="form-label">Cidade</label>
                        <input type="text" id="cidade" name="cidade"
                            value="{{ old('cidade', $unidade->cidade) }}"
                            class="form-input">
                    </div>

                    <div>
                        <label for="estado" class="form-label">Estado (UF)</label>
                        <input type="text" id="estado" name="estado" maxlength="2"
                            value="{{ old('estado', $unidade->estado) }}"
                            class="form-input uppercase">
                    </div>
                </div>

                <div class="pt-4 border-t flex flex-col sm:flex-row sm:items-center gap-6"
                    style="border-color: var(--color-surface-800);">
                    <label class="flex items-center gap-2 text-xs text-white cursor-pointer select-none">
                        <input type="checkbox" name="padrao" value="1"
                            {{ old('padrao', $unidade->padrao) ? 'checked' : '' }}
                            class="form-checkbox rounded">
                        <span>Definir como <strong>Unidade Padrão</strong> (pré-selecionada ao emitir documentos)</span>
                    </label>

                    <label class="flex items-center gap-2 text-xs text-white cursor-pointer select-none">
                        <input type="checkbox" name="ativo" value="1"
                            {{ old('ativo', $unidade->ativo) ? 'checked' : '' }}
                            class="form-checkbox rounded">
                        <span>Unidade Ativa</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('unidades.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
@endsection
