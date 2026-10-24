@extends('layouts.app')

@section('title', 'Editar Cliente')

@section('content')
<div class="max-w-3xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('clientes.update', $cliente) }}" class="space-y-6">
            @csrf @method('PUT')

            {{-- Dados Cadastrais --}}
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-3" style="color: var(--color-primary-400);">1. Dados Cadastrais e Contato</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="form-label" for="nome">Nome / Razão Social *</label>
                        <input type="text" id="nome" name="nome" value="{{ old('nome', $cliente->nome) }}" class="form-input" required>
                    </div>

                    <div>
                        <label class="form-label" for="documento">Documento (CPF ou CNPJ)</label>
                        <input type="text" id="documento" name="documento" value="{{ old('documento', $cliente->documento) }}" class="form-input">
                    </div>

                    <div>
                        <label class="form-label" for="telefone">Telefone / WhatsApp</label>
                        <input type="text" id="telefone" name="telefone" value="{{ old('telefone', $cliente->telefone) }}" class="form-input">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="form-label" for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $cliente->email) }}" class="form-input">
                    </div>
                </div>
            </div>

            {{-- Localização --}}
            <div class="pt-2 border-t" style="border-color: var(--color-surface-800);">
                <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-3" style="color: var(--color-primary-400);">2. Localização</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="form-label" for="endereco">Logradouro e Número</label>
                        <input type="text" id="endereco" name="endereco" value="{{ old('endereco', $cliente->endereco) }}" class="form-input">
                    </div>

                    <div>
                        <label class="form-label" for="cep">CEP</label>
                        <input type="text" id="cep" name="cep" value="{{ old('cep', $cliente->cep) }}" class="form-input">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="form-label" for="cidade">Cidade *</label>
                        <input type="text" id="cidade" name="cidade" value="{{ old('cidade', $cliente->cidade) }}" class="form-input">
                    </div>

                    <div>
                        <label class="form-label" for="estado">UF *</label>
                        <select id="estado" name="estado" class="form-select">
                            <option value="">Selecione...</option>
                            @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                                <option value="{{ $uf }}" {{ old('estado', $cliente->estado) === $uf ? 'selected' : '' }}>{{ $uf }}</option>
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
                            <option value="Trafego Pago" {{ old('origem', $cliente->origem) === 'Trafego Pago' ? 'selected' : '' }}>Tráfego Pago (Google/Meta)</option>
                            <option value="Indicacao" {{ old('origem', $cliente->origem) === 'Indicacao' ? 'selected' : '' }}>Indicação</option>
                            <option value="Prospeccao Ativa" {{ old('origem', $cliente->origem) === 'Prospeccao Ativa' ? 'selected' : '' }}>Prospecção Ativa (Outbound)</option>
                            <option value="Redes Sociais" {{ old('origem', $cliente->origem) === 'Redes Sociais' ? 'selected' : '' }}>Redes Sociais / Orgânico</option>
                            <option value="Parceria" {{ old('origem', $cliente->origem) === 'Parceria' ? 'selected' : '' }}>Parceria Comercial</option>
                            <option value="outros" {{ old('origem', $cliente->origem) === 'outros' ? 'selected' : '' }}>Outros</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="periodicidade">Periodicidade de Atendimento</label>
                        <select id="periodicidade" name="periodicidade" class="form-select">
                            <option value="avulso" {{ old('periodicidade', $cliente->periodicidade) === 'avulso' ? 'selected' : '' }}>Avulso / Pontual</option>
                            <option value="quinzenal" {{ old('periodicidade', $cliente->periodicidade) === 'quinzenal' ? 'selected' : '' }}>Quinzenal</option>
                            <option value="mensal" {{ old('periodicidade', $cliente->periodicidade) === 'mensal' ? 'selected' : '' }}>Mensal</option>
                            <option value="bimestral" {{ old('periodicidade', $cliente->periodicidade) === 'bimestral' ? 'selected' : '' }}>Bimestral</option>
                            <option value="trimestral" {{ old('periodicidade', $cliente->periodicidade) === 'trimestral' ? 'selected' : '' }}>Trimestral</option>
                            <option value="semestral" {{ old('periodicidade', $cliente->periodicidade) === 'semestral' ? 'selected' : '' }}>Semestral</option>
                            <option value="anual" {{ old('periodicidade', $cliente->periodicidade) === 'anual' ? 'selected' : '' }}>Anual</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="status_prospeccao">Status do Funil / Situação</label>
                        <select id="status_prospeccao" name="status_prospeccao" class="form-select">
                            <option value="ativo" {{ old('status_prospeccao', $cliente->status_prospeccao) === 'ativo' ? 'selected' : '' }}>Cliente Ativo</option>
                            <option value="em_negociacao" {{ old('status_prospeccao', $cliente->status_prospeccao) === 'em_negociacao' ? 'selected' : '' }}>Em Negociação</option>
                            <option value="prospeccao" {{ old('status_prospeccao', $cliente->status_prospeccao) === 'prospeccao' ? 'selected' : '' }}>Prospecção Ativa</option>
                            <option value="retornar_3_meses" {{ old('status_prospeccao', $cliente->status_prospeccao) === 'retornar_3_meses' ? 'selected' : '' }}>Retornar em 3 Meses</option>
                            <option value="contrato_concorrente" {{ old('status_prospeccao', $cliente->status_prospeccao) === 'contrato_concorrente' ? 'selected' : '' }}>Contrato Concorrente a Vencer</option>
                            <option value="inativo" {{ old('status_prospeccao', $cliente->status_prospeccao) === 'inativo' ? 'selected' : '' }}>Inativo</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="data_followup">Data de Retorno / Follow-up (Agenda)</label>
                        <input type="date" id="data_followup" name="data_followup" value="{{ old('data_followup', $cliente->data_followup ? $cliente->data_followup->format('Y-m-d') : '') }}" class="form-input">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="form-label" for="observacoes">Observações Técnicas / Restrições do Local</label>
                        <textarea id="observacoes" name="observacoes" rows="3" class="form-textarea">{{ old('observacoes', $cliente->observacoes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Salvar Alterações
                </button>
                <a href="{{ route('clientes.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
