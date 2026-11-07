@extends('layouts.app')

@section('title', 'Configurações do Sistema')

@section('content')
    <div class="max-w-4xl space-y-6">

        <div>
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Configurações da Plataforma
            </h2>
            <p class="text-sm mt-1" style="color: var(--color-surface-400);">
                Gerencie os dados institucionais da Alaris exibidos nas propostas/contratos e os parâmetros operacionais de cálculo.
            </p>
        </div>

        @if (session('sucesso'))
            <div class="p-4 rounded-xl flex items-center gap-3 text-sm text-emerald-300"
                style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3);">
                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('sucesso') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-red-950/40 border border-red-800 text-red-200 text-sm">
                <p class="font-bold mb-1">Por favor, corrija os erros abaixo:</p>
                <ul class="list-disc pl-5 space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('configuracoes.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- 1. Informações Empresariais da Alaris Service --}}
            <div class="card p-6 space-y-5">
                <div class="flex items-center gap-2 pb-3 border-b" style="border-color: var(--color-surface-800);">
                    <div class="p-2 rounded-lg bg-sky-500/10 text-sky-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                            1. Informações Empresariais da Alaris Service
                        </h3>
                        <p class="text-xs" style="color: var(--color-surface-400);">
                            Dados da Contratada impressos no cabeçalho, escopo e assinaturas das Propostas Comerciais e Contratos.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {{-- Razão Social --}}
                    <div class="sm:col-span-2">
                        <label for="empresa_razao_social" class="form-label">Razão Social Oficial *</label>
                        <input type="text" id="empresa_razao_social" name="empresa_razao_social"
                            value="{{ old('empresa_razao_social', $empresa['razaoSocial']) }}"
                            class="form-input font-bold" placeholder="Ex: ALARIS SERVICE LTDA">
                        <span class="form-hint">Razão Social cadastrada no CNPJ</span>
                    </div>

                    {{-- Nome Fantasia --}}
                    <div>
                        <label for="empresa_nome_fantasia" class="form-label">Nome Fantasia / Marca *</label>
                        <input type="text" id="empresa_nome_fantasia" name="empresa_nome_fantasia"
                            value="{{ old('empresa_nome_fantasia', $empresa['nomeFantasia']) }}"
                            class="form-input" placeholder="Ex: Alaris Service">
                    </div>

                    {{-- CNPJ --}}
                    <div>
                        <label for="empresa_cnpj" class="form-label">CNPJ da Empresa *</label>
                        <input type="text" id="empresa_cnpj" name="empresa_cnpj"
                            value="{{ old('empresa_cnpj', $empresa['cnpj']) }}"
                            class="form-input font-bold" placeholder="00.000.000/0000-00" data-mask="documento">
                    </div>

                    {{-- Licença Sanitária / Alvará --}}
                    <div class="sm:col-span-2">
                        <label for="empresa_licenca_sanitaria" class="form-label">Alvará Sanitário / Licença Ambiental</label>
                        <input type="text" id="empresa_licenca_sanitaria" name="empresa_licenca_sanitaria"
                            value="{{ old('empresa_licenca_sanitaria', $empresa['licenca']) }}"
                            class="form-input" placeholder="Ex: Alvará Sanitário Municipal • Licença Ambiental SEMMA">
                    </div>

                    {{-- Telefone / WhatsApp --}}
                    <div>
                        <label for="empresa_telefone" class="form-label">Telefone / WhatsApp Comercial</label>
                        <input type="text" id="empresa_telefone" name="empresa_telefone"
                            value="{{ old('empresa_telefone', $empresa['telefone']) }}"
                            class="form-input" placeholder="(00) 00000-0000" data-mask="telefone">
                    </div>

                    {{-- E-mail Comercial --}}
                    <div class="sm:col-span-2">
                        <label for="empresa_email" class="form-label">E-mail Comercial / Atendimento</label>
                        <input type="email" id="empresa_email" name="empresa_email"
                            value="{{ old('empresa_email', $empresa['email']) }}"
                            class="form-input" placeholder="contato@alarisservice.com.br">
                    </div>

                    {{-- Slogan / Segmento --}}
                    <div class="sm:col-span-3">
                        <label for="empresa_slogan" class="form-label">Slogan / Ramo de Atuação</label>
                        <input type="text" id="empresa_slogan" name="empresa_slogan"
                            value="{{ old('empresa_slogan', $empresa['slogan']) }}"
                            class="form-input" placeholder="Ex: Soluções em Controle Integrado de Pragas & Higienização">
                    </div>

                    {{-- Endereço Completo --}}
                    <div>
                        <div class="flex items-center justify-between">
                            <label for="empresa_cep" class="form-label mb-1">CEP da Sede</label>
                            <span id="empresa-cep-status" class="text-xs text-sky-400 font-medium hidden">Buscando...</span>
                        </div>
                        <div class="relative">
                            <input type="text" id="empresa_cep" name="empresa_cep"
                                value="{{ old('empresa_cep', $empresa['cep']) }}"
                                class="form-input" placeholder="00000-000" data-mask="cep" autocomplete="postal-code">
                            <span id="empresa-cep-spinner" class="hidden absolute right-3 top-2.5 text-sky-400">
                                <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </span>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="empresa_endereco" class="form-label">Endereço da Sede (Logradouro e Número)</label>
                        <input type="text" id="empresa_endereco" name="empresa_endereco"
                            value="{{ old('empresa_endereco', $empresa['endereco']) }}"
                            class="form-input" placeholder="Ex: R. Frei Epifânio da Abadia, 120">
                    </div>

                    <div>
                        <label for="empresa_bairro" class="form-label">Bairro</label>
                        <input type="text" id="empresa_bairro" name="empresa_bairro"
                            value="{{ old('empresa_bairro', $empresa['bairro']) }}"
                            class="form-input" placeholder="Ex: Centro">
                    </div>

                    <div>
                        <label for="empresa_cidade" class="form-label">Cidade</label>
                        <input type="text" id="empresa_cidade" name="empresa_cidade"
                            value="{{ old('empresa_cidade', $empresa['cidade']) }}"
                            class="form-input" placeholder="Ex: Imperatriz">
                    </div>

                    <div>
                        <label for="empresa_estado" class="form-label">Estado (UF)</label>
                        <input type="text" id="empresa_estado" name="empresa_estado" maxlength="2"
                            value="{{ old('empresa_estado', $empresa['estado']) }}"
                            class="form-input uppercase" placeholder="MA">
                    </div>
                </div>
            </div>

            {{-- 2. Parâmetros Operacionais & Fiscais --}}
            <div class="card p-6 space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b" style="border-color: var(--color-surface-800);">
                    <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                            2. Parâmetros Operacionais & Custos Padrão
                        </h3>
                        <p class="text-xs" style="color: var(--color-surface-400);">
                            Valores base para formação de custos de O.S., contratos recorrentes e DRE.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Alíquota de Imposto --}}
                    <div class="p-4 rounded-xl"
                        style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                        <label for="aliquota_imposto_padrao" class="text-sm font-bold text-white block">
                            Alíquota Padrão de Nota Fiscal (%) *
                        </label>
                        <p class="text-xs mt-1 mb-3" style="color: var(--color-surface-400);">
                            Percentual padrão aplicado sobre o faturamento quando a NFS-e for exigida.
                        </p>
                        <div class="relative w-36">
                            <input type="number" step="0.01" min="0" max="100"
                                id="aliquota_imposto_padrao" name="aliquota_imposto_padrao"
                                value="{{ old('aliquota_imposto_padrao', $aliquotaImpostoPadrao) }}"
                                class="form-input text-right font-bold pr-8" required>
                            <span class="absolute right-3 top-2.5 text-xs font-bold"
                                style="color: var(--color-surface-400);">%</span>
                        </div>
                    </div>

                    {{-- Diária Técnico Padrão --}}
                    <div class="p-4 rounded-xl"
                        style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                        <label for="diaria_tecnico_padrao" class="text-sm font-bold text-white block">
                            Diária Técnica Padrão por Atendimento (R$) *
                        </label>
                        <p class="text-xs mt-1 mb-3" style="color: var(--color-surface-400);">
                            Valor padrão de mão de obra pré-carregado na O.S. e na provisão de contratos.
                        </p>
                        <div class="relative w-40">
                            <span class="absolute left-3 top-2.5 text-xs font-bold"
                                style="color: var(--color-surface-400);">R$</span>
                            <input type="number" step="0.01" min="0" id="diaria_tecnico_padrao"
                                name="diaria_tecnico_padrao"
                                value="{{ old('diaria_tecnico_padrao', $diariaTecnicoPadrao) }}"
                                class="form-input text-right font-bold pl-9" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                @permissao('configuracoes.editar')
                    <button type="submit" class="btn btn-primary px-6 py-2.5 text-sm font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Salvar Todas as Configurações
                    </button>
                @endpermissao
            </div>
        </form>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cepInput = document.getElementById('empresa_cep');
            const enderecoInput = document.getElementById('empresa_endereco');
            const bairroInput = document.getElementById('empresa_bairro');
            const cidadeInput = document.getElementById('empresa_cidade');
            const estadoInput = document.getElementById('empresa_estado');
            const hintEl = document.getElementById('empresa-cep-hint');
            const spinnerEl = document.getElementById('empresa-cep-spinner');
            const statusEl = document.getElementById('empresa-cep-status');

            if (!cepInput) return;

            let ultimoCepConsultado = '';

            async function consultarViaCep(cep) {
                const cepLimpo = cep.replace(/\D/g, '');
                if (cepLimpo.length !== 8) return;
                if (cepLimpo === ultimoCepConsultado) return;

                ultimoCepConsultado = cepLimpo;

                if (spinnerEl) spinnerEl.classList.remove('hidden');
                if (statusEl) {
                    statusEl.textContent = 'Buscando endereço...';
                    statusEl.classList.remove('hidden');
                }
                if (hintEl) hintEl.textContent = 'Consultando ViaCEP...';

                try {
                    const response = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);
                    if (!response.ok) throw new Error('Falha na consulta');

                    const data = await response.json();

                    if (data.erro) {
                        if (hintEl) {
                            hintEl.textContent = 'CEP não encontrado.';
                            hintEl.classList.add('text-amber-400');
                        }
                        if (statusEl) statusEl.textContent = 'Não encontrado';
                        return;
                    }

                    if (enderecoInput && data.logradouro) {
                        enderecoInput.value = data.logradouro;
                    }
                    if (bairroInput && data.bairro) {
                        bairroInput.value = data.bairro;
                    }
                    if (cidadeInput && data.localidade) {
                        cidadeInput.value = data.localidade;
                    }
                    if (estadoInput && data.uf) {
                        estadoInput.value = data.uf.toUpperCase();
                    }

                    if (hintEl) {
                        hintEl.textContent = 'Endereço carregado via CEP!';
                        hintEl.classList.remove('text-amber-400');
                        hintEl.classList.add('text-emerald-400');
                    }

                    if (enderecoInput) {
                        enderecoInput.focus();
                    }
                } catch (e) {
                    if (hintEl) hintEl.textContent = 'Erro ao consultar ViaCEP.';
                } finally {
                    if (spinnerEl) spinnerEl.classList.add('hidden');
                    if (statusEl) statusEl.classList.add('hidden');
                }
            }

            cepInput.addEventListener('input', function () {
                const cepLimpo = this.value.replace(/\D/g, '');
                if (cepLimpo.length === 8) {
                    consultarViaCep(this.value);
                }
            });

            cepInput.addEventListener('blur', function () {
                const cepLimpo = this.value.replace(/\D/g, '');
                if (cepLimpo.length === 8) {
                    consultarViaCep(this.value);
                }
            });
        });
    </script>
@endsection
