<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Configuracao;
use App\Models\EquipamentoComodato;
use App\Models\MovimentacaoFinanceira;
use App\Models\Produto;
use App\Models\Tecnico;
use App\Models\Visita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitaController extends Controller
{
    public function index(Request $request)
    {
        $query = Visita::with(['cliente', 'tecnico']);

        // Se for técnico, filtra estritamente as suas próprias visitas
        if ($request->user() && $request->user()->isTecnico()) {
            $query->where('tecnico_id', $request->user()->tecnico_id);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($tipoVisita = $request->get('tipo_visita')) {
            $query->where('tipo_visita', $tipoVisita);
        }

        if ($tipoServico = $request->get('tipo_servico')) {
            $query->where('tipo_servico', $tipoServico);
        }

        if ($data = $request->get('data')) {
            $query->whereDate('data_hora', $data);
        }

        if ($busca = $request->get('busca')) {
            $query->whereHas('cliente', function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%");
            });
        }

        $visitas = $query->orderByDesc('data_hora')->paginate(15)->withQueryString();

        return view('visitas.index', compact('visitas'));
    }

    public function create(Request $request)
    {
        $clientes = Cliente::orderBy('nome')->get();
        $produtos = Produto::orderBy('nome')->get();
        $tecnicos = Tecnico::where('ativo', true)->orderBy('nome')->get();
        $aliquotaPadrao = (float) Configuracao::get('aliquota_imposto_padrao', '14.0');
        $diariaPadrao = (float) Configuracao::get('diaria_tecnico_padrao', '50.00');
        $comodato = $request->filled('comodato_id') ? EquipamentoComodato::with(['cliente', 'produto', 'equipamento'])->find($request->comodato_id) : null;

        return view('visitas.create', compact('clientes', 'produtos', 'tecnicos', 'aliquotaPadrao', 'diariaPadrao', 'comodato'));
    }

    public function store(Request $request)
    {
        $this->normalizarCamposMonetarios($request);

        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'comodato_id' => 'nullable|exists:equipamentos_comodato,id',
            'data_hora' => 'required|date',
            'status' => 'required|in:agendada,em_andamento,concluida,cancelada',
            'tecnico_id' => 'required|exists:tecnicos,id',
            'tipo_visita' => 'required|string|in:comercial,tecnica,demonstracao,execucao',
            'tipo_servico' => 'required|string|in:controle_pragas,limpeza',
            'tipo_praga' => 'nullable|string|max:255',
            'area_tratada' => 'nullable|string|max:255',
            'observacoes' => 'nullable|string',
            'proxima_visita' => 'nullable|date|after:data_hora',
            'valor_cobrado' => 'nullable|numeric|min:0',
            'custo_mao_de_obra' => 'nullable|numeric|min:0',
            'custo_deslocamento' => 'nullable|numeric|min:0',
            'gastos_adicionais' => 'nullable|numeric|min:0',
            'motivo_gastos_adicionais' => 'nullable|string|max:255',
            'exige_nota_fiscal' => 'boolean',
            'produtos' => 'nullable|array',
            'produtos.*.id' => 'nullable|exists:produtos,id',
            'produtos.*.produto_id' => 'nullable|exists:produtos,id',
            'produtos.*.quantidade' => 'required_with:produtos|numeric|min:0.0001',
        ]);

        $exigeNf = $request->has('exige_nota_fiscal');
        $valorCobrado = (float) ($validated['valor_cobrado'] ?? 0);

        $tecnico = Tecnico::find($validated['tecnico_id']);
        $diariaPadraoTecnico = ($tecnico && (float) $tecnico->valor_diaria_padrao > 0)
            ? (float) $tecnico->valor_diaria_padrao
            : (float) Configuracao::get('diaria_tecnico_padrao', '50.00');

        $custoMaoDeObra = (isset($validated['custo_mao_de_obra']) && $validated['custo_mao_de_obra'] !== '' && $validated['custo_mao_de_obra'] !== null)
            ? (float) $validated['custo_mao_de_obra']
            : $diariaPadraoTecnico;

        $custoDeslocamento = (float) ($validated['custo_deslocamento'] ?? 0);
        $gastosAdicionais = (float) ($validated['gastos_adicionais'] ?? 0);

        $aliquotaImposto = $exigeNf ? (float) Configuracao::get('aliquota_imposto_padrao', '14.0') : 0.00;
        $valorImposto = round($valorCobrado * ($aliquotaImposto / 100), 2);

        DB::transaction(function () use ($validated, $valorCobrado, $custoMaoDeObra, $custoDeslocamento, $gastosAdicionais, $exigeNf, $aliquotaImposto, $valorImposto) {
            $custoProdutos = 0.0;
            $sync = [];

            if (! empty($validated['produtos'])) {
                foreach ($validated['produtos'] as $item) {
                    $prodId = $item['id'] ?? $item['produto_id'] ?? null;
                    if (! $prodId) {
                        continue;
                    }
                    $prod = Produto::findOrFail($prodId);
                    $dose = (float) $item['quantidade'];
                    $custoItem = $prod->calcularCustoDose($dose);
                    $custoProdutos += $custoItem;

                    $sync[$prod->id] = [
                        'quantidade' => $dose,
                        'custo_proporcional' => $custoItem,
                    ];
                }
            }

            $custoOperacional = $custoProdutos + $custoMaoDeObra + $custoDeslocamento + $gastosAdicionais;
            $lucroBruto = $valorCobrado - ($custoOperacional + $valorImposto);

            $visitaData = collect($validated)->except('produtos')->toArray();
            $visitaData['exige_nota_fiscal'] = $exigeNf;
            $visitaData['valor_cobrado'] = $valorCobrado;
            $visitaData['custo_produtos'] = $custoProdutos;
            $visitaData['custo_mao_de_obra'] = $custoMaoDeObra;
            $visitaData['custo_deslocamento'] = $custoDeslocamento;
            $visitaData['gastos_adicionais'] = $gastosAdicionais;
            $visitaData['motivo_gastos_adicionais'] = $validated['motivo_gastos_adicionais'] ?? null;
            $visitaData['percentual_imposto'] = $aliquotaImposto;
            $visitaData['valor_imposto'] = $valorImposto;
            $visitaData['lucro_bruto'] = $lucroBruto;

            $visita = Visita::create($visitaData);

            if (! empty($sync)) {
                $visita->produtos()->sync($sync);
            }

            // Se criada já como concluída, debitar frações do estoque, atualizar comodato e sincronizar financeiro
            if ($visita->status === Visita::STATUS_CONCLUIDA) {
                if ($visita->comodato_id) {
                    $visita->comodato?->registrarReposicao($visita->data_hora ?? now());
                }

                $visita->load('produtos');
                foreach ($visita->produtos as $produto) {
                    $dose = (float) $produto->pivot->quantidade;
                    $fracao = $produto->calcularFracaoBaixa($dose);
                    $produto->decrement('quantidade_estoque', $fracao);
                }

                $this->sincronizarMovimentacoesFinanceiras($visita);
            }
        });

        return redirect()->route('visitas.index')
            ->with('sucesso', 'Visita registrada com sucesso.');
    }

    public function show(Visita $visita)
    {
        $this->autorizarAcessoVisita($visita);

        $visita->load(['cliente', 'tecnico', 'produtos', 'comodato']);

        return view('visitas.show', compact('visita'));
    }

    public function edit(Visita $visita)
    {
        $this->autorizarAcessoVisita($visita);

        $visita->load(['produtos', 'comodato']);
        $clientes = Cliente::orderBy('nome')->get();
        $produtos = Produto::orderBy('nome')->get();
        $tecnicos = Tecnico::where('ativo', true)->orderBy('nome')->get();
        $aliquotaPadrao = (float) Configuracao::get('aliquota_imposto_padrao', '14.0');
        $diariaPadrao = (float) Configuracao::get('diaria_tecnico_padrao', '50.00');

        return view('visitas.edit', compact('visita', 'clientes', 'produtos', 'tecnicos', 'aliquotaPadrao', 'diariaPadrao'));
    }

    public function update(Request $request, Visita $visita)
    {
        $this->autorizarAcessoVisita($visita);
        $this->normalizarCamposMonetarios($request);

        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'comodato_id' => 'nullable|exists:equipamentos_comodato,id',
            'data_hora' => 'required|date',
            'status' => 'required|in:agendada,em_andamento,concluida,cancelada',
            'tecnico_id' => 'required|exists:tecnicos,id',
            'tipo_visita' => 'required|string|in:comercial,tecnica,demonstracao,execucao',
            'tipo_servico' => 'required|string|in:controle_pragas,limpeza',
            'tipo_praga' => 'nullable|string|max:255',
            'area_tratada' => 'nullable|string|max:255',
            'observacoes' => 'nullable|string',
            'proxima_visita' => 'nullable|date|after:data_hora',
            'valor_cobrado' => 'nullable|numeric|min:0',
            'custo_mao_de_obra' => 'nullable|numeric|min:0',
            'custo_deslocamento' => 'nullable|numeric|min:0',
            'gastos_adicionais' => 'nullable|numeric|min:0',
            'motivo_gastos_adicionais' => 'nullable|string|max:255',
            'exige_nota_fiscal' => 'boolean',
            'produtos' => 'nullable|array',
            'produtos.*.id' => 'nullable|exists:produtos,id',
            'produtos.*.produto_id' => 'nullable|exists:produtos,id',
            'produtos.*.quantidade' => 'required_with:produtos|numeric|min:0.0001',
        ]);

        $statusAnterior = $visita->status;
        $exigeNf = $request->has('exige_nota_fiscal');
        $valorCobrado = (float) ($validated['valor_cobrado'] ?? 0);

        $tecnico = Tecnico::find($validated['tecnico_id']);
        $diariaPadraoTecnico = ($tecnico && (float) $tecnico->valor_diaria_padrao > 0)
            ? (float) $tecnico->valor_diaria_padrao
            : (float) Configuracao::get('diaria_tecnico_padrao', '50.00');

        $custoMaoDeObra = (isset($validated['custo_mao_de_obra']) && $validated['custo_mao_de_obra'] !== '' && $validated['custo_mao_de_obra'] !== null)
            ? (float) $validated['custo_mao_de_obra']
            : $diariaPadraoTecnico;

        $custoDeslocamento = (float) ($validated['custo_deslocamento'] ?? 0);
        $gastosAdicionais = (float) ($validated['gastos_adicionais'] ?? 0);

        $aliquotaImposto = $exigeNf ? (float) Configuracao::get('aliquota_imposto_padrao', '14.0') : 0.00;
        $valorImposto = round($valorCobrado * ($aliquotaImposto / 100), 2);

        DB::transaction(function () use ($validated, $visita, $statusAnterior, $valorCobrado, $custoMaoDeObra, $custoDeslocamento, $gastosAdicionais, $exigeNf, $aliquotaImposto, $valorImposto) {
            $custoProdutos = 0.0;
            $sync = [];

            if (! empty($validated['produtos'])) {
                foreach ($validated['produtos'] as $item) {
                    $prodId = $item['id'] ?? $item['produto_id'] ?? null;
                    if (! $prodId) {
                        continue;
                    }
                    $prod = Produto::findOrFail($prodId);
                    $dose = (float) $item['quantidade'];
                    $custoItem = $prod->calcularCustoDose($dose);
                    $custoProdutos += $custoItem;

                    $sync[$prod->id] = [
                        'quantidade' => $dose,
                        'custo_proporcional' => $custoItem,
                    ];
                }
            }

            $custoOperacional = $custoProdutos + $custoMaoDeObra + $custoDeslocamento + $gastosAdicionais;
            $lucroBruto = $valorCobrado - ($custoOperacional + $valorImposto);

            $visitaData = collect($validated)->except('produtos')->toArray();
            $visitaData['exige_nota_fiscal'] = $exigeNf;
            $visitaData['valor_cobrado'] = $valorCobrado;
            $visitaData['custo_produtos'] = $custoProdutos;
            $visitaData['custo_mao_de_obra'] = $custoMaoDeObra;
            $visitaData['custo_deslocamento'] = $custoDeslocamento;
            $visitaData['gastos_adicionais'] = $gastosAdicionais;
            $visitaData['motivo_gastos_adicionais'] = $validated['motivo_gastos_adicionais'] ?? null;
            $visitaData['percentual_imposto'] = $aliquotaImposto;
            $visitaData['valor_imposto'] = $valorImposto;
            $visitaData['lucro_bruto'] = $lucroBruto;

            $visita->update($visitaData);
            $visita->produtos()->sync($sync);

            // Se transicionou para concluída, descontar frações do estoque e atualizar comodato
            if ($statusAnterior !== Visita::STATUS_CONCLUIDA && $visita->status === Visita::STATUS_CONCLUIDA) {
                if ($visita->comodato_id) {
                    $visita->comodato?->registrarReposicao($visita->data_hora ?? now());
                }

                $visita->load('produtos');
                foreach ($visita->produtos as $produto) {
                    $dose = (float) $produto->pivot->quantidade;
                    $fracao = $produto->calcularFracaoBaixa($dose);
                    $produto->decrement('quantidade_estoque', $fracao);
                }
            }

            // Sincroniza lançamentos financeiros (faturamento, gastos adicionais e diária técnica)
            if ($visita->status === Visita::STATUS_CONCLUIDA || $statusAnterior === Visita::STATUS_CONCLUIDA || $visita->status === Visita::STATUS_CANCELADA) {
                $this->sincronizarMovimentacoesFinanceiras($visita);
            }
        });

        return redirect()->route('visitas.show', $visita)
            ->with('sucesso', 'Visita atualizada com sucesso.');
    }

    /**
     * Finaliza diretamente uma visita técnica/OS, permitindo registrar
     * observações de conclusão e gastos adicionais imprevistos.
     */
    public function finalizar(Request $request, Visita $visita)
    {
        $this->autorizarAcessoVisita($visita);

        if ($visita->status === Visita::STATUS_CONCLUIDA) {
            return redirect()->route('visitas.show', $visita)
                ->with('alerta', 'Esta visita já está marcada como concluída.');
        }

        $this->normalizarCamposMonetarios($request);

        $validated = $request->validate([
            'observacoes' => 'nullable|string',
            'gastos_adicionais' => 'nullable|numeric|min:0',
            'motivo_gastos_adicionais' => 'nullable|string|max:255',
        ]);

        $novasObservacoes = trim($validated['observacoes'] ?? '');

        // Preservação inteligente de gastos adicionais da abertura da OS
        $valorModalInformado = isset($validated['gastos_adicionais']) && $validated['gastos_adicionais'] !== '' && $validated['gastos_adicionais'] !== null
            ? (float) $validated['gastos_adicionais']
            : null;

        if ($valorModalInformado !== null && $valorModalInformado > 0) {
            $gastosAdicionais = $valorModalInformado;
            $motivoGastos = ! empty($validated['motivo_gastos_adicionais'])
                ? $validated['motivo_gastos_adicionais']
                : $visita->motivo_gastos_adicionais;
        } elseif ((float) $visita->gastos_adicionais > 0 && ($valorModalInformado === null || $valorModalInformado === 0.0)) {
            // Preserva o valor e motivo já registrados na abertura da OS caso o modal venha zerado/vazio
            $gastosAdicionais = (float) $visita->gastos_adicionais;
            $motivoGastos = ! empty($validated['motivo_gastos_adicionais'])
                ? $validated['motivo_gastos_adicionais']
                : $visita->motivo_gastos_adicionais;
        } else {
            $gastosAdicionais = $valorModalInformado ?? 0.0;
            $motivoGastos = $validated['motivo_gastos_adicionais'] ?? null;
        }

        DB::transaction(function () use ($visita, $novasObservacoes, $gastosAdicionais, $motivoGastos) {
            // Atualiza ou anexa observações
            if (! empty($novasObservacoes)) {
                if (! empty($visita->observacoes)) {
                    $visita->observacoes = trim($visita->observacoes)."\n\n[Conclusão em ".now()->format('d/m/Y H:i')."]:\n".$novasObservacoes;
                } else {
                    $visita->observacoes = $novasObservacoes;
                }
            }

            $visita->gastos_adicionais = $gastosAdicionais;
            $visita->motivo_gastos_adicionais = $motivoGastos;
            $visita->status = Visita::STATUS_CONCLUIDA;

            // Recalcula o custo operacional total e lucro bruto
            $custoOperacional = (float) $visita->custo_produtos
                + (float) $visita->custo_mao_de_obra
                + (float) $visita->custo_deslocamento
                + $gastosAdicionais;

            $visita->lucro_bruto = (float) $visita->valor_cobrado - ($custoOperacional + (float) $visita->valor_imposto);
            $visita->save();

            // Atualiza reposição do comodato automaticamente se vinculada
            if ($visita->comodato_id) {
                $visita->comodato?->registrarReposicao($visita->data_hora ?? now());
            }

            // Baixa atômica de frações do estoque dos insumos aplicados
            $visita->load('produtos');
            foreach ($visita->produtos as $produto) {
                $dose = (float) $produto->pivot->quantidade;
                $fracao = $produto->calcularFracaoBaixa($dose);
                $produto->decrement('quantidade_estoque', $fracao);
            }

            // Sincroniza lançamentos financeiros (faturamento, gastos adicionais e diária do técnico)
            $this->sincronizarMovimentacoesFinanceiras($visita);
        });

        return redirect()->route('visitas.show', $visita)
            ->with('sucesso', 'Visita finalizada com sucesso! Baixa de insumos realizada e financeiro atualizado.');
    }

    public function destroy(Visita $visita)
    {
        $this->autorizarAcessoVisita($visita);

        DB::transaction(function () use ($visita) {
            $visita->movimentacoes()->delete();
            $visita->produtos()->detach();
            $visita->delete();
        });

        return redirect()->route('visitas.index')
            ->with('sucesso', 'Visita removida com sucesso.');
    }

    /**
     * Gera Ordem de Serviço (OS) detalhada para o técnico em campo.
     */
    public function ordemServico(Visita $visita)
    {
        $this->autorizarAcessoVisita($visita);

        $visita->load(['cliente.comodatos.produto', 'tecnico', 'produtos']);

        return view('visitas.os', compact('visita'));
    }

    /**
     * Gera Proposta Comercial para o cliente (com estrito sigilo de custos e mL).
     */
    public function propostaCliente(Visita $visita)
    {
        $user = auth()->user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Acesso restrito a administradores. Técnicos não possuem acesso a propostas comerciais.');
        }

        $visita->load(['cliente', 'tecnico']);

        return view('visitas.proposta', compact('visita'));
    }

    private function autorizarAcessoVisita(Visita $visita): void
    {
        $user = auth()->user();
        if ($user && $user->isTecnico() && $visita->tecnico_id !== $user->tecnico_id) {
            abort(403, 'Acesso restrito. Você só pode visualizar as suas próprias visitas.');
        }
    }

    /**
     * Sincroniza de forma atômica e consistente os lançamentos no Fluxo de Caixa (MovimentacaoFinanceira)
     * vinculados à Ordem de Serviço, garantindo integridade e conciliação com o DRE.
     */
    private function sincronizarMovimentacoesFinanceiras(Visita $visita): void
    {
        $visita->loadMissing(['cliente', 'tecnico']);

        if ($visita->status === Visita::STATUS_CANCELADA) {
            // Se a OS foi cancelada, remove lançamentos pendentes para não poluir previsões do fluxo
            $visita->movimentacoes()->where('status', MovimentacaoFinanceira::STATUS_PENDENTE)->delete();

            return;
        }

        if ($visita->status !== Visita::STATUS_CONCLUIDA) {
            return;
        }

        $dataCompetencia = $visita->data_hora ? $visita->data_hora->toDateString() : now()->toDateString();
        $valorCobrado = (float) $visita->valor_cobrado;
        $custoMaoDeObra = (float) $visita->custo_mao_de_obra;
        $gastosAdicionais = (float) $visita->gastos_adicionais;

        // 1. Receita: Faturamento da OS
        if ($valorCobrado > 0) {
            $movReceita = MovimentacaoFinanceira::where('visita_id', $visita->id)
                ->where('tipo', MovimentacaoFinanceira::TIPO_RECEITA)
                ->first();

            $descricaoReceita = "Faturamento OS #{$visita->id} - {$visita->cliente->nome}";

            if ($movReceita) {
                $movReceita->valor = $valorCobrado;
                $movReceita->descricao = $descricaoReceita;
                $movReceita->data_competencia = $dataCompetencia;
                if ($movReceita->status === MovimentacaoFinanceira::STATUS_CANCELADO) {
                    $movReceita->status = MovimentacaoFinanceira::STATUS_PENDENTE;
                }
                $movReceita->save();
            } else {
                MovimentacaoFinanceira::create([
                    'visita_id' => $visita->id,
                    'tipo' => MovimentacaoFinanceira::TIPO_RECEITA,
                    'categoria' => 'faturamento_servico',
                    'descricao' => $descricaoReceita,
                    'valor' => $valorCobrado,
                    'data_competencia' => $dataCompetencia,
                    'status' => MovimentacaoFinanceira::STATUS_PENDENTE,
                ]);
            }
        } else {
            MovimentacaoFinanceira::where('visita_id', $visita->id)
                ->where('tipo', MovimentacaoFinanceira::TIPO_RECEITA)
                ->where('status', MovimentacaoFinanceira::STATUS_PENDENTE)
                ->delete();
        }

        // 2. Despesa: Gastos Adicionais da OS
        if ($gastosAdicionais > 0) {
            $movGastos = MovimentacaoFinanceira::where('visita_id', $visita->id)
                ->where('tipo', MovimentacaoFinanceira::TIPO_DESPESA)
                ->where(function ($q) {
                    $q->where('categoria', 'gastos_adicionais')
                        ->orWhere('categoria', 'outros')
                        ->where('descricao', 'like', 'Gastos adicionais OS #%');
                })
                ->first();

            $descricaoGastos = "Gastos adicionais OS #{$visita->id}".($visita->motivo_gastos_adicionais ? " - {$visita->motivo_gastos_adicionais}" : " - {$visita->cliente->nome}");

            if ($movGastos) {
                $movGastos->categoria = 'gastos_adicionais';
                $movGastos->valor = $gastosAdicionais;
                $movGastos->descricao = $descricaoGastos;
                $movGastos->data_competencia = $dataCompetencia;
                $movGastos->save();
            } else {
                MovimentacaoFinanceira::create([
                    'visita_id' => $visita->id,
                    'tipo' => MovimentacaoFinanceira::TIPO_DESPESA,
                    'categoria' => 'gastos_adicionais',
                    'descricao' => $descricaoGastos,
                    'valor' => $gastosAdicionais,
                    'data_competencia' => $dataCompetencia,
                    'data_pagamento' => $dataCompetencia,
                    'status' => MovimentacaoFinanceira::STATUS_PAGO,
                ]);
            }
        } else {
            MovimentacaoFinanceira::where('visita_id', $visita->id)
                ->where('tipo', MovimentacaoFinanceira::TIPO_DESPESA)
                ->where(function ($q) {
                    $q->where('categoria', 'gastos_adicionais')
                        ->orWhere('descricao', 'like', 'Gastos adicionais OS #%');
                })
                ->delete();
        }

        // 3. Despesa: Diária / Mão de Obra do Técnico
        if ($custoMaoDeObra > 0) {
            $movDiaria = MovimentacaoFinanceira::where('visita_id', $visita->id)
                ->where('tipo', MovimentacaoFinanceira::TIPO_DESPESA)
                ->whereIn('categoria', ['mao_de_obra', 'diaria_tecnico'])
                ->first();

            $descricaoDiaria = "Diária Técnico OS #{$visita->id} - ".($visita->tecnico?->nome ?? 'Técnico Responsável');

            if ($movDiaria) {
                $movDiaria->valor = $custoMaoDeObra;
                $movDiaria->descricao = $descricaoDiaria;
                $movDiaria->data_competencia = $dataCompetencia;
                $movDiaria->save();
            } else {
                MovimentacaoFinanceira::create([
                    'visita_id' => $visita->id,
                    'tipo' => MovimentacaoFinanceira::TIPO_DESPESA,
                    'categoria' => 'mao_de_obra',
                    'descricao' => $descricaoDiaria,
                    'valor' => $custoMaoDeObra,
                    'data_competencia' => $dataCompetencia,
                    'status' => MovimentacaoFinanceira::STATUS_PENDENTE,
                ]);
            }
        } else {
            MovimentacaoFinanceira::where('visita_id', $visita->id)
                ->where('tipo', MovimentacaoFinanceira::TIPO_DESPESA)
                ->whereIn('categoria', ['mao_de_obra', 'diaria_tecnico'])
                ->where('status', MovimentacaoFinanceira::STATUS_PENDENTE)
                ->delete();
        }
    }

    /**
     * Sanitiza campos numéricos/monetários que venham formatados com vírgula (padrão pt-BR)
     * convertendo-os para o formato decimal com ponto antes da validação.
     */
    private function normalizarCamposMonetarios(Request $request): void
    {
        $campos = ['valor_cobrado', 'custo_mao_de_obra', 'custo_deslocamento', 'gastos_adicionais'];
        $substituicoes = [];
        foreach ($campos as $campo) {
            if ($request->has($campo) && is_string($request->$campo)) {
                $substituicoes[$campo] = str_replace(',', '.', trim($request->$campo));
            }
        }
        if (! empty($substituicoes)) {
            $request->merge($substituicoes);
        }
    }
}
