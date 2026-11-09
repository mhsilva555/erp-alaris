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
                Gerencie os parâmetros operacionais de cálculo e acesse o cadastro de unidades (Matriz e Filiais).
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

        {{-- 1. Atalho para Unidades da Empresa (Matriz & Filiais) --}}
        <div class="card p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b" style="border-color: var(--color-surface-800);">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-lg bg-sky-500/10 text-sky-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                            Unidades da Empresa (Matriz & Filiais)
                        </h3>
                        <p class="text-xs" style="color: var(--color-surface-400);">
                            Dados institucionais (Razão Social, CNPJ, Endereço e Contatos) exibidos nos PDFs de Propostas, Contratos e O.S.
                        </p>
                    </div>
                </div>
                <a href="{{ route('unidades.index') }}" class="btn btn-secondary btn-sm shrink-0">
                    <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Gerenciar Matriz & Filiais
                </a>
            </div>

            @if(isset($unidades) && $unidades->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($unidades as $unidade)
                        <div class="p-3.5 rounded-xl flex items-start justify-between gap-3"
                            style="background: var(--color-surface-950); border: 1px solid {{ $unidade->padrao ? 'rgba(35, 180, 235, 0.35)' : 'var(--color-surface-800)' }};">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-bold text-white truncate">{{ $unidade->nome }}</span>
                                    <span class="badge {{ $unidade->ehMatriz() ? 'bg-sky-500/15 text-sky-400 border-sky-500/30' : 'bg-indigo-500/15 text-indigo-400 border-indigo-500/30' }} text-[10px]">
                                        {{ $unidade->tipo_label }}
                                    </span>
                                    @if($unidade->padrao)
                                        <span class="badge bg-emerald-500/15 text-emerald-400 border-emerald-500/30 text-[10px]">Padrão</span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-300 mt-1 truncate">{{ $unidade->razao_social }}</p>
                                <p class="text-[11px] mt-0.5" style="color: var(--color-surface-400);">
                                    {{ $unidade->cnpj_formatado ?: 'CNPJ não informado' }}
                                    @if($unidade->cidade)
                                        • {{ $unidade->cidade }}/{{ $unidade->estado }}
                                    @endif
                                </p>
                            </div>
                            @permissao('unidades.editar')
                                <a href="{{ route('unidades.edit', $unidade) }}" class="text-xs text-sky-400 hover:text-sky-300 font-semibold shrink-0">
                                    Editar
                                </a>
                            @endpermissao
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-4 rounded-xl flex items-center justify-between gap-3 text-xs"
                    style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800); color: var(--color-surface-400);">
                    <span>Nenhuma unidade cadastrada. Cadastre a Matriz e as Filiais para personalizar o cabeçalho e rodapé dos documentos.</span>
                    <a href="{{ route('unidades.create') }}" class="btn btn-primary btn-sm shrink-0">Nova Unidade</a>
                </div>
            @endif
        </div>

        {{-- 2. Parâmetros Operacionais & Fiscais --}}
        <form action="{{ route('configuracoes.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

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
                            Parâmetros Operacionais & Custos Padrão
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
                        Salvar Parâmetros Operacionais
                    </button>
                @endpermissao
            </div>
        </form>

    </div>
@endsection
