@extends('layouts.app')

@section('title', 'Novo Cliente')

@section('content')
<div class="max-w-3xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('clientes.store') }}" class="space-y-6">
            @csrf

            {{-- Dados Cadastrais --}}
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-3" style="color: var(--color-primary-400);">1. Dados Cadastrais e Contato</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="form-label" for="nome">Nome / Razão Social *</label>
                        <input type="text" id="nome" name="nome" value="{{ old('nome') }}" class="form-input" required>
                    </div>

                    <div>
                        <label class="form-label" for="documento">Documento (CPF ou CNPJ)</label>
                        <input type="text" id="documento" name="documento" value="{{ old('documento') }}" class="form-input" data-mask="cpf-cnpj" placeholder="000.000.000-00 ou 00.000.000/0000-00">
                    </div>

                    <div>
                        <label class="form-label" for="telefone">Telefone / WhatsApp</label>
                        <input type="text" id="telefone" name="telefone" value="{{ old('telefone') }}" class="form-input" data-mask="telefone" placeholder="(00) 00000-0000">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="form-label" for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-input">
                    </div>
                </div>
            </div>

            {{-- Localização --}}
            <div class="pt-2 border-t" style="border-color: var(--color-surface-800);">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white" style="color: var(--color-primary-400);">2. Localização</h3>
                    <span id="cep-status" class="text-xs text-sky-400 font-medium hidden">Buscando endereço...</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4">
                    {{-- CEP --}}
                    <div class="sm:col-span-1 lg:col-span-3">
                        <label class="form-label" for="cep">CEP</label>
                        <div class="relative">
                            <input type="text" id="cep" name="cep" value="{{ old('cep') }}" class="form-input" data-mask="cep" placeholder="00000-000" autocomplete="postal-code">
                            <span id="cep-spinner" class="hidden absolute right-3 top-2.5 text-sky-400">
                                <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </span>
                        </div>
                    </div>

                    {{-- Logradouro --}}
                    <div class="sm:col-span-1 lg:col-span-6">
                        <label class="form-label" for="logradouro">Logradouro (Rua, Av.)</label>
                        <input type="text" id="logradouro" name="logradouro" value="{{ old('logradouro') }}" class="form-input" placeholder="Ex: Rua Maria Helena Coutinho">
                    </div>

                    {{-- Número --}}
                    <div class="sm:col-span-1 lg:col-span-3">
                        <label class="form-label" for="numero">Número</label>
                        <input type="text" id="numero" name="numero" value="{{ old('numero') }}" class="form-input" placeholder="Ex: 120 ou S/N">
                    </div>

                    {{-- Complemento --}}
                    <div class="sm:col-span-1 lg:col-span-3">
                        <label class="form-label" for="complemento">Complemento</label>
                        <input type="text" id="complemento" name="complemento" value="{{ old('complemento') }}" class="form-input" placeholder="Apto, Sala, Galpão...">
                    </div>

                    {{-- Bairro --}}
                    <div class="sm:col-span-1 lg:col-span-3">
                        <label class="form-label" for="bairro">Bairro</label>
                        <input type="text" id="bairro" name="bairro" value="{{ old('bairro') }}" class="form-input" placeholder="Ex: Parque Planalto">
                    </div>

                    {{-- Cidade --}}
                    <div class="sm:col-span-1 lg:col-span-4">
                        <label class="form-label" for="cidade">Cidade *</label>
                        <input type="text" id="cidade" name="cidade" value="{{ old('cidade') }}" class="form-input" placeholder="Ex: Imperatriz" required>
                    </div>

                    {{-- UF --}}
                    <div class="sm:col-span-1 lg:col-span-2">
                        <label class="form-label" for="estado">UF *</label>
                        <select id="estado" name="estado" class="form-select" required>
                            <option value="">Selecione...</option>
                            @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                                <option value="{{ $uf }}" {{ old('estado', 'MA') === $uf ? 'selected' : '' }}>{{ $uf }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Gestão Comercial e Funil --}}
            <div class="pt-2 border-t" style="border-color: var(--color-surface-800);">
                <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-3" style="color: var(--color-primary-400);">3. Gestão Comercial & Funil</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="origem">Origem / Canal de Aquisição</label>
                        <select id="origem" name="origem" class="form-select">
                            <option value="Trafego Pago" {{ old('origem') === 'Trafego Pago' ? 'selected' : '' }}>Tráfego Pago (Google/Meta)</option>
                            <option value="Indicacao" {{ old('origem') === 'Indicacao' ? 'selected' : '' }}>Indicação</option>
                            <option value="Prospeccao Ativa" {{ old('origem') === 'Prospeccao Ativa' ? 'selected' : '' }}>Prospecção Ativa (Outbound)</option>
                            <option value="Redes Sociais" {{ old('origem') === 'Redes Sociais' ? 'selected' : '' }}>Redes Sociais / Orgânico</option>
                            <option value="Parceria" {{ old('origem') === 'Parceria' ? 'selected' : '' }}>Parceria Comercial</option>
                            <option value="outros" {{ old('origem', 'outros') === 'outros' ? 'selected' : '' }}>Outros</option>
                        </select>
                    </div>


                    <div>
                        <label class="form-label" for="status_prospeccao">Status do Funil / Situação</label>
                        <select id="status_prospeccao" name="status_prospeccao" class="form-select">
                            <option value="ativo" {{ old('status_prospeccao', 'ativo') === 'ativo' ? 'selected' : '' }}>Cliente Ativo</option>
                            <option value="em_negociacao" {{ old('status_prospeccao') === 'em_negociacao' ? 'selected' : '' }}>Em Negociação</option>
                            <option value="prospeccao" {{ old('status_prospeccao') === 'prospeccao' ? 'selected' : '' }}>Prospecção Ativa</option>
                            <option value="retornar_3_meses" {{ old('status_prospeccao') === 'retornar_3_meses' ? 'selected' : '' }}>Retornar em 3 Meses</option>
                            <option value="contrato_concorrente" {{ old('status_prospeccao') === 'contrato_concorrente' ? 'selected' : '' }}>Contrato Concorrente a Vencer</option>
                            <option value="inativo" {{ old('status_prospeccao') === 'inativo' ? 'selected' : '' }}>Inativo</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="data_followup">Data de Retorno / Follow-up (Agenda)</label>
                        <input type="date" id="data_followup" name="data_followup" value="{{ old('data_followup') }}" class="form-input">
                        <p class="text-[11px] mt-1" style="color: var(--color-surface-400);">Gera lembrete automático na Agenda Unificada.</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="form-label" for="observacoes">Observações Técnicas / Restrições do Local</label>
                        <textarea id="observacoes" name="observacoes" rows="3" class="form-textarea" placeholder="Instruções de acesso, animais no local, horários permitidos...">{{ old('observacoes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Cadastrar Cliente
                </button>
                <a href="{{ route('clientes.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const cepInput = document.getElementById('cep');
        const logradouroInput = document.getElementById('logradouro');
        const numeroInput = document.getElementById('numero');
        const complementoInput = document.getElementById('complemento');
        const bairroInput = document.getElementById('bairro');
        const cidadeInput = document.getElementById('cidade');
        const estadoInput = document.getElementById('estado');
        const hintEl = document.getElementById('cep-hint');
        const spinnerEl = document.getElementById('cep-spinner');
        const statusEl = document.getElementById('cep-status');

        let ultimoCepConsultado = '';

        function buscarViaCep() {
            if (!cepInput) return;
            const cepLimpo = cepInput.value.replace(/\D/g, '');

            if (cepLimpo === ultimoCepConsultado) return;
            ultimoCepConsultado = cepLimpo;

            if (spinnerEl) spinnerEl.classList.remove('hidden');
            if (statusEl) {
                statusEl.textContent = 'Buscando CEP no ViaCEP...';
                statusEl.className = 'text-xs text-sky-400 font-medium';
                statusEl.classList.remove('hidden');
            }
            if (hintEl) {
                hintEl.textContent = 'Consultando ViaCEP...';
                hintEl.style.color = '#38bdf8';
            }

            fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`)
                .then(res => {
                    if (!res.ok) throw new Error('Falha na requisição');
                    return res.json();
                })
                .then(data => {
                    if (spinnerEl) spinnerEl.classList.add('hidden');

                    if (data.erro) {
                        if (statusEl) {
                            statusEl.textContent = 'CEP não encontrado';
                            statusEl.className = 'text-xs text-rose-400 font-medium';
                        }
                        if (hintEl) {
                            hintEl.textContent = 'CEP não localizado no ViaCEP.';
                            hintEl.style.color = '#f87171';
                        }
                        return;
                    }

                    // Preenche Logradouro
                    if (logradouroInput && data.logradouro) {
                        logradouroInput.value = data.logradouro;
                    }

                    // Preenche Bairro
                    if (bairroInput && data.bairro) {
                        bairroInput.value = data.bairro;
                    }

                    // Preenche Complemento se vier na base do ViaCEP
                    if (complementoInput && data.complemento && !complementoInput.value) {
                        complementoInput.value = data.complemento;
                    }

                    // Preenche Cidade
                    if (cidadeInput && data.localidade) {
                        cidadeInput.value = data.localidade;
                    }

                    // Preenche UF
                    if (estadoInput && data.uf) {
                        const ufUpper = data.uf.toUpperCase();
                        estadoInput.value = ufUpper;
                        estadoInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    if (statusEl) {
                        statusEl.textContent = '✓ Endereço preenchido!';
                        statusEl.className = 'text-xs text-emerald-400 font-bold';
                        setTimeout(() => statusEl.classList.add('hidden'), 3500);
                    }
                    if (hintEl) {
                        hintEl.textContent = '✓ Dados carregados do ViaCEP';
                        hintEl.style.color = '#34d399';
                    }

                    // Foco direto no campo Número para o usuário digitar
                    if (numeroInput) {
                        numeroInput.focus();
                    }
                })
                .catch(err => {
                    if (spinnerEl) spinnerEl.classList.add('hidden');
                    if (statusEl) {
                        statusEl.textContent = 'Erro ao consultar CEP';
                        statusEl.className = 'text-xs text-rose-400 font-medium';
                    }
                    if (hintEl) {
                        hintEl.textContent = 'Erro ao conectar com ViaCEP.';
                        hintEl.style.color = '#f87171';
                    }
                });
        }

        cepInput.addEventListener('input', function () {
            const limpo = this.value.replace(/\D/g, '');
            if (limpo.length === 8) {
                buscarViaCep();
            }
        });

        cepInput.addEventListener('blur', function () {
            buscarViaCep();
        });
    });
</script>
@endsection
