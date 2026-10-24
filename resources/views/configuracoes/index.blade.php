@extends('layouts.app')

@section('title', 'Configurações do Sistema')

@section('content')
    <div class="max-w-2xl space-y-6">

        <div>
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Configurações Operacionais & Financeiras
            </h2>
            <p class="text-sm mt-1" style="color: var(--color-surface-400);">
                Defina parâmetros globais para formação de preço, impostos e custos operacionais do ERP Alaris.
            </p>
        </div>

        <div class="card p-6">
            <form action="{{ route('configuracoes.update') }}" method="POST" class="space-y-6">
                @csrf

                {{-- Alíquota de Imposto --}}
                <div class="p-4 rounded-xl"
                    style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <label for="aliquota_imposto_padrao" class="text-sm font-bold text-white block">
                                Alíquota Padrão de Nota Fiscal (%)
                            </label>
                            <p class="text-xs mt-1" style="color: var(--color-surface-400);">
                                Percentual padrão aplicado no cálculo de tributos quando a opção "Exige Nota Fiscal" for
                                ativada no lançamento ou orçamento da Ordem de Serviço (ex: 12% a 14%).
                            </p>
                        </div>
                        <div class="w-32 shrink-0">
                            <div class="relative">
                                <input type="number" step="0.01" min="0" max="100"
                                    id="aliquota_imposto_padrao" name="aliquota_imposto_padrao"
                                    value="{{ old('aliquota_imposto_padrao', $aliquotaImpostoPadrao) }}"
                                    class="form-input text-right  font-bold pr-8" required>
                                <span class="absolute right-3 top-2.5 text-xs font-bold"
                                    style="color: var(--color-surface-400);">%</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Diária Técnico Padrão --}}
                <div class="p-4 rounded-xl"
                    style="background: var(--color-surface-950); border: 1px solid var(--color-surface-800);">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <label for="diaria_tecnico_padrao" class="text-sm font-bold text-white block">
                                Valor Padrão da Diária Técnica (R$)
                            </label>
                            <p class="text-xs mt-1" style="color: var(--color-surface-400);">
                                Valor sugerido por visita para apropriação do custo operacional de mão de obra do técnico de
                                campo quando não houver valor individual parametrizado no cadastro do técnico.
                            </p>
                        </div>
                        <div class="w-36 shrink-0">
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs font-bold"
                                    style="color: var(--color-surface-400);">R$</span>
                                <input type="number" step="0.01" min="0" id="diaria_tecnico_padrao"
                                    name="diaria_tecnico_padrao"
                                    value="{{ old('diaria_tecnico_padrao', $diariaTecnicoPadrao) }}"
                                    class="form-input text-right  font-bold pl-9" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t"
                    style="border-color: var(--color-surface-800);">
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Salvar Parâmetros
                    </button>
                </div>
            </form>
        </div>

    </div>
@endsection
