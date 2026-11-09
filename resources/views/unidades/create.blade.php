@extends('layouts.app')

@section('title', 'Nova Unidade da Empresa')

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
            <h1 class="text-xl font-bold text-white">Cadastrar Nova Unidade (Matriz ou Filial)</h1>
            <p class="text-xs mt-1" style="color: var(--color-surface-400);">
                Os dados cadastrados serão utilizados no cabeçalho, assinaturas e rodapé de Propostas Comerciais, Contratos e Ordens de Serviço quando esta unidade for selecionada.
            </p>
        </div>

        <form method="POST" action="{{ route('unidades.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="ativo_submitted" value="1">

            <div class="card p-6 space-y-5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-white" style="color: var(--color-primary-400);">
                    1. Identificação da Unidade & Dados Fiscais
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label for="nome" class="form-label">Nome de Identificação da Unidade *</label>
                        <input type="text" id="nome" name="nome" value="{{ old('nome') }}" required
                            class="form-input font-bold" placeholder="Ex: Matriz Imperatriz ou Filial Açailândia">
                        <span class="form-hint">Nome exibido no seletor ao emitir propostas, contratos e O.S.</span>
                    </div>

                    <div>
                        <label for="tipo" class="form-label">Tipo de Unidade *</label>
                        <select id="tipo" name="tipo" class="form-select" required>
                            @foreach (\App\Models\UnidadeEmpresa::TIPOS as $key => $label)
                                <option value="{{ $key }}"
                                    {{ old('tipo', $jaExisteMatriz ? 'filial' : 'matriz') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="razao_social" class="form-label">Razão Social Oficial *</label>
                        <input type="text" id="razao_social" name="razao_social"
                            value="{{ old('razao_social', $empresaPadrao['razaoSocial']) }}" required
                            class="form-input font-bold" placeholder="Ex: ALARIS SERVICE LTDA">
                    </div>

                    <div>
                        <label for="nome_fantasia" class="form-label">Nome Fantasia / Marca *</label>
                        <input type="text" id="nome_fantasia" name="nome_fantasia"
                            value="{{ old('nome_fantasia', $empresaPadrao['nomeFantasia']) }}" required
                            class="form-input" placeholder="Ex: Alaris Service">
                    </div>

                    <div>
                        <label for="cnpj" class="form-label">CNPJ da Unidade *</label>
                        <input type="text" id="cnpj" name="cnpj" value="{{ old('cnpj') }}" required
                            class="form-input font-bold" placeholder="00.000.000/0000-00" data-mask="documento">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="licenca_sanitaria" class="form-label">Alvará Sanitário / Licença Ambiental</label>
                        <input type="text" id="licenca_sanitaria" name="licenca_sanitaria"
                            value="{{ old('licenca_sanitaria', $empresaPadrao['licenca']) }}"
                            class="form-input" placeholder="Ex: Alvará Sanitário Municipal • Licença Ambiental SEMMA">
                    </div>

                    <div>
                        <label for="telefone" class="form-label">Telefone / WhatsApp Comercial</label>
                        <input type="text" id="telefone" name="telefone"
                            value="{{ old('telefone', $empresaPadrao['telefone']) }}"
                            class="form-input" placeholder="(00) 00000-0000" data-mask="telefone">
                    </div>

                    <div>
                        <label for="email" class="form-label">E-mail Comercial</label>
                        <input type="email" id="email" name="email"
                            value="{{ old('email', $empresaPadrao['email']) }}"
                            class="form-input" placeholder="contato@alarisservice.com.br">
                    </div>

                    <div>
                        <label for="website" class="form-label">Website da Empresa</label>
                        <input type="text" id="website" name="website"
                            value="{{ old('website', $empresaPadrao['website']) }}"
                            class="form-input" placeholder="Ex: www.alarisservice.com.br">
                    </div>

                    <div class="sm:col-span-3">
                        <label for="slogan" class="form-label">Slogan / Ramo de Atuação</label>
                        <input type="text" id="slogan" name="slogan"
                            value="{{ old('slogan', $empresaPadrao['slogan']) }}"
                            class="form-input" placeholder="Ex: Soluções em Controle de Pragas & Higienização">
                    </div>
                </div>
            </div>

            <div class="card p-6 space-y-5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-white" style="color: var(--color-primary-400);">
                    2. Endereço e Localização da Unidade
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <div class="flex items-center justify-between">
                            <label for="cep" class="form-label mb-1">CEP</label>
                            <span id="unidade-cep-status" class="text-xs text-sky-400 font-medium hidden">Buscando...</span>
                        </div>
                        <input type="text" id="cep" name="cep" value="{{ old('cep') }}"
                            class="form-input" placeholder="00000-000" data-mask="cep" autocomplete="postal-code">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="endereco" class="form-label">Endereço (Logradouro, Número e Complemento)</label>
                        <input type="text" id="endereco" name="endereco" value="{{ old('endereco') }}"
                            class="form-input" placeholder="Ex: Av. Getúlio Vargas, 1500">
                    </div>

                    <div>
                        <label for="bairro" class="form-label">Bairro</label>
                        <input type="text" id="bairro" name="bairro" value="{{ old('bairro') }}"
                            class="form-input" placeholder="Ex: Centro">
                    </div>

                    <div>
                        <label for="cidade" class="form-label">Cidade</label>
                        <input type="text" id="cidade" name="cidade" value="{{ old('cidade') }}"
                            class="form-input" placeholder="Ex: Imperatriz">
                    </div>

                    <div>
                        <label for="estado" class="form-label">Estado (UF)</label>
                        <input type="text" id="estado" name="estado" maxlength="2" value="{{ old('estado', 'MA') }}"
                            class="form-input uppercase" placeholder="MA">
                    </div>
                </div>

                <div class="pt-4 border-t flex flex-col sm:flex-row sm:items-center gap-6"
                    style="border-color: var(--color-surface-800);">
                    <label class="flex items-center gap-2 text-xs text-white cursor-pointer select-none">
                        <input type="checkbox" name="padrao" value="1"
                            {{ old('padrao', ! $jaExisteMatriz ? '1' : '') ? 'checked' : '' }}
                            class="form-checkbox rounded">
                        <span>Definir como <strong>Unidade Padrão</strong> (pré-selecionada ao emitir documentos)</span>
                    </label>

                    <label class="flex items-center gap-2 text-xs text-white cursor-pointer select-none">
                        <input type="checkbox" name="ativo" value="1" {{ old('ativo', '1') ? 'checked' : '' }}
                            class="form-checkbox rounded">
                        <span>Unidade Ativa</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('unidades.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Salvar Unidade</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cepInput = document.getElementById('cep');
            const statusEl = document.getElementById('unidade-cep-status');
            if (!cepInput) return;

            cepInput.addEventListener('blur', function() {
                const cepLimpo = (this.value || '').replace(/\D/g, '');
                if (cepLimpo.length !== 8) return;

                if (statusEl) statusEl.classList.remove('hidden');
                fetch('https://viacep.com.br/ws/' + cepLimpo + '/json/')
                    .then(r => r.json())
                    .then(data => {
                        if (!data.erro) {
                            const endEl = document.getElementById('endereco');
                            const bairroEl = document.getElementById('bairro');
                            const cidEl = document.getElementById('cidade');
                            const ufEl = document.getElementById('estado');
                            if (endEl && !endEl.value && data.logradouro) endEl.value = data.logradouro;
                            if (bairroEl && data.bairro) bairroEl.value = data.bairro;
                            if (cidEl && data.localidade) cidEl.value = data.localidade;
                            if (ufEl && data.uf) ufEl.value = data.uf;
                        }
                    })
                    .catch(() => {})
                    .finally(() => {
                        if (statusEl) statusEl.classList.add('hidden');
                    });
            });
        });
    </script>
@endsection
