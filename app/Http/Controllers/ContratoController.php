<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Configuracao;
use App\Models\Contrato;
use App\Models\ContratoItem;
use App\Models\Equipamento;
use App\Models\EquipamentoComodato;
use App\Models\MeioPagamento;
use App\Models\Produto;
use App\Models\UnidadeEmpresa;
use App\Models\Visita;
use App\Services\ContratoAgendamentoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContratoController extends Controller
{
    public function index(Request $request)
    {
        $this->autorizar('contratos.ver');

        $query = Contrato::with(['cliente', 'unidade', 'itens'])->withCount([
            'visitas',
            'visitas as visitas_concluidas_count' => fn ($q) => $q->where('status', Visita::STATUS_CONCLUIDA),
            'visitas as visitas_pendentes_count' => fn ($q) => $q->where('status', Visita::STATUS_AGENDADA),
        ]);

        if ($busca = $request->get('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('numero', 'like', "%{$busca}%")
                    ->orWhere('titulo', 'like', "%{$busca}%")
                    ->orWhereHas('cliente', fn ($c) => $c->where('nome', 'like', "%{$busca}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($tipoVigencia = $request->get('tipo_vigencia')) {
            $query->where('tipo_vigencia', $tipoVigencia);
        }

        if ($clienteId = $request->get('cliente_id')) {
            $query->where('cliente_id', $clienteId);
        }

        // Estatísticas para cards executivos
        $totais = [
            'total' => Contrato::count(),
            'ativos' => Contrato::where('status', Contrato::STATUS_ATIVO)->count(),
            'propostas' => Contrato::where('status', Contrato::STATUS_PROPOSTA)->count(),
            'rascunhos' => Contrato::where('status', Contrato::STATUS_RASCUNHO)->count(),
            'suspensos' => Contrato::where('status', Contrato::STATUS_SUSPENSO)->count(),
            'faturamento_mensal' => Contrato::where('status', Contrato::STATUS_ATIVO)->sum('valor_total'),
        ];

        $contratos = $query
            ->orderByRaw("CASE 
                WHEN status = 'proposta' THEN 1
                WHEN status = 'ativo' THEN 2 
                WHEN status = 'rascunho' THEN 3 
                WHEN status = 'suspenso' THEN 4 
                ELSE 5 END ASC")
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $clientes = Cliente::orderBy('nome')->get(['id', 'nome']);

        return view('contratos.index', compact('contratos', 'totais', 'clientes'));
    }

    public function create(Request $request)
    {
        $this->autorizar('contratos.criar');

        $clientes = Cliente::orderBy('nome')->get();
        $clientePreSelecionado = $request->filled('cliente_id') ? Cliente::find($request->cliente_id) : null;
        $proximoNumero = Contrato::gerarProximoNumero();
        $meiosPagamento = MeioPagamento::where('ativo', true)->orderBy('nome')->get();
        $equipamentos = Equipamento::orderBy('nome')->get();
        $produtos = Produto::orderBy('nome')->get();
        $unidades = UnidadeEmpresa::ativas()
            ->orderByDesc('padrao')
            ->orderByRaw("CASE WHEN tipo = 'matriz' THEN 0 ELSE 1 END ASC")
            ->orderBy('nome')
            ->get();
        $unidadePadrao = UnidadeEmpresa::padraoAtiva();
        $aliquotaPadrao = (float) Configuracao::get('aliquota_imposto_padrao', '14.0');
        $diariaPadrao = (float) Configuracao::get('diaria_tecnico_padrao', '50.00');

        return view('contratos.create', compact('clientes', 'clientePreSelecionado', 'proximoNumero', 'meiosPagamento', 'equipamentos', 'produtos', 'unidades', 'unidadePadrao', 'aliquotaPadrao', 'diariaPadrao'));
    }

    public function store(Request $request, ContratoAgendamentoService $service)
    {
        $this->autorizar('contratos.criar');

        $this->normalizarCamposMonetarios($request);

        $validated = $request->validate([
            'numero' => 'required|string|max:50|unique:contratos,numero',
            'cliente_id' => 'required|exists:clientes,id',
            'unidade_id' => 'nullable|exists:unidades_empresa,id',
            'titulo' => 'nullable|string|max:255',
            'descricao' => 'nullable|string',
            'status' => 'required|in:proposta,ativo,rascunho',
            'tipo_vigencia' => 'required|in:determinado,indeterminado',
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|date|required_if:tipo_vigencia,determinado|after_or_equal:data_inicio',
            'permite_renovacao' => 'boolean',
            'periodicidade' => 'required|string|in:atendimento_unico,semanal,quinzenal,mensal,bimestral,trimestral,semestral,anual,personalizado',
            'periodicidade_dias' => 'nullable|integer|min:1|required_if:periodicidade,personalizado',
            'dia_preferencial' => 'nullable|integer|between:1,31',
            'dia_semana' => 'nullable|integer|between:0,6',
            'tipo_servico' => 'required|string|in:controle_pragas,limpeza',
            'modelo_cobranca' => 'required|string|in:mensal,por_visita,fechamento_periodo,fixo_anual',
            'exige_nota_fiscal' => 'boolean',
            'percentual_imposto' => 'nullable|numeric|min:0|max:100',
            'custo_diaria_padrao' => 'nullable|numeric|min:0',
            'custo_deslocamento_padrao' => 'nullable|numeric|min:0',
            'meio_pagamento_id' => 'nullable|exists:meios_pagamento,id',
            'condicoes_pagamento' => 'nullable|string|max:255',
            'desconto' => 'nullable|numeric|min:0',
            'observacoes_comerciais' => 'nullable|string',
            'itens' => 'nullable|array',
            'itens.*.tipo_servico' => 'nullable|string|max:50',
            'itens.*.descricao' => 'required|string|max:255',
            'itens.*.quantidade' => 'required|numeric|min:0.01',
            'itens.*.valor_unitario' => 'required|numeric|min:0',
            'comodatos' => 'nullable|array',
            'comodatos.*.equipamento_id' => 'nullable|exists:equipamentos,id',
            'comodatos.*.nome_equipamento' => 'nullable|string|max:255',
            'comodatos.*.produto_id' => 'nullable|exists:produtos,id',
            'comodatos.*.quantidade' => 'required|integer|min:1',
            'comodatos.*.periodicidade_dias' => 'nullable|integer|min:1',
            'comodatos.*.observacoes' => 'nullable|string',
        ]);

        $contrato = DB::transaction(function () use ($validated, $request) {
            $dataContrato = collect($validated)->except(['itens', 'comodatos'])->toArray();
            if (empty($dataContrato['unidade_id'])) {
                $dataContrato['unidade_id'] = UnidadeEmpresa::padraoAtiva()?->id;
            }
            $dataContrato['permite_renovacao'] = $request->has('permite_renovacao');
            $dataContrato['exige_nota_fiscal'] = $request->has('exige_nota_fiscal');
            $dataContrato['percentual_imposto'] = $dataContrato['exige_nota_fiscal']
                ? (float) ($validated['percentual_imposto'] ?? Configuracao::get('aliquota_imposto_padrao', '14.0'))
                : 0.00;
            $dataContrato['desconto'] = (float) ($validated['desconto'] ?? 0);
            $dataContrato['valor_total'] = 0.00;

            $contrato = Contrato::create($dataContrato);

            $subtotalItens = 0.00;
            if (! empty($validated['itens'])) {
                foreach ($validated['itens'] as $itemData) {
                    $qtd = (float) $itemData['quantidade'];
                    $vlrUnit = (float) $itemData['valor_unitario'];
                    $vlrTotal = round($qtd * $vlrUnit, 2);
                    $subtotalItens += $vlrTotal;

                    ContratoItem::create([
                        'contrato_id' => $contrato->id,
                        'tipo_servico' => $itemData['tipo_servico'] ?? $contrato->tipo_servico,
                        'descricao' => $itemData['descricao'],
                        'quantidade' => $qtd,
                        'valor_unitario' => $vlrUnit,
                        'valor_total' => $vlrTotal,
                    ]);
                }
            }

            $valorFinal = max(0, $subtotalItens - $dataContrato['desconto']);
            $contrato->update(['valor_total' => $valorFinal]);

            $this->sincronizarComodatosEmbutidos(
                $contrato,
                $validated['comodatos'] ?? [],
                false
            );

            if ($contrato->estaAtivo()) {
                $contrato->sincronizarMovimentacaoMes(now());
            }

            return $contrato;
        });

        $mensagem = $contrato->ehProposta()
            ? "Proposta comercial {$contrato->numero} criada com sucesso. Você pode emitir o PDF para o cliente e aprová-la quando aceita."
            : "Contrato {$contrato->numero} cadastrado com sucesso.";

        return redirect()->route('contratos.show', $contrato)
            ->with('sucesso', $mensagem);
    }

    public function show(Contrato $contrato)
    {
        $this->autorizar('contratos.ver');

        $contrato->load([
            'cliente',
            'unidade',
            'meioPagamento',
            'itens',
            'comodatos' => function ($q) {
                $q->with(['equipamento', 'produto'])->orderBy('data_proxima_reposicao');
            },
            'visitas' => function ($q) {
                $q->orderByDesc('data_hora')->with(['tecnico', 'produtos']);
            },
        ]);

        $unidades = UnidadeEmpresa::ativas()
            ->orderByDesc('padrao')
            ->orderByRaw("CASE WHEN tipo = 'matriz' THEN 0 ELSE 1 END ASC")
            ->orderBy('nome')
            ->get();

        $metricas = [
            'total_visitas' => $contrato->visitas->count(),
            'concluidas' => $contrato->visitas->where('status', Visita::STATUS_CONCLUIDA)->count(),
            'agendadas' => $contrato->visitas->where('status', Visita::STATUS_AGENDADA)->count(),
            'canceladas' => $contrato->visitas->where('status', Visita::STATUS_CANCELADA)->count(),
            'total_comodatos' => $contrato->comodatos->count(),
            'comodatos_atrasados' => $contrato->comodatos->filter->estaAtrasado()->count(),
        ];

        return view('contratos.show', compact('contrato', 'metricas', 'unidades'));
    }

    public function edit(Contrato $contrato)
    {
        $this->autorizar('contratos.editar');

        if (! $contrato->podeEditar()) {
            return redirect()->route('contratos.show', $contrato)
                ->with('alerta', 'Contratos encerrados ou cancelados não podem ser editados.');
        }

        $clientes = Cliente::orderBy('nome')->get();
        $meiosPagamento = MeioPagamento::where('ativo', true)->orderBy('nome')->get();
        $equipamentos = Equipamento::orderBy('nome')->get();
        $produtos = Produto::orderBy('nome')->get();
        $unidades = UnidadeEmpresa::ativas()
            ->orderByDesc('padrao')
            ->orderByRaw("CASE WHEN tipo = 'matriz' THEN 0 ELSE 1 END ASC")
            ->orderBy('nome')
            ->get();
        $aliquotaPadrao = (float) Configuracao::get('aliquota_imposto_padrao', '14.0');
        $diariaPadrao = (float) Configuracao::get('diaria_tecnico_padrao', '50.00');
        $contrato->load(['itens', 'unidade', 'meioPagamento', 'comodatos.equipamento', 'comodatos.produto']);

        return view('contratos.edit', compact('contrato', 'clientes', 'meiosPagamento', 'equipamentos', 'produtos', 'unidades', 'aliquotaPadrao', 'diariaPadrao'));
    }

    public function update(Request $request, Contrato $contrato, ContratoAgendamentoService $service)
    {
        $this->autorizar('contratos.editar');

        if (! $contrato->podeEditar()) {
            return redirect()->route('contratos.show', $contrato)
                ->with('alerta', 'Contratos encerrados ou cancelados não podem ser editados.');
        }

        $this->normalizarCamposMonetarios($request);

        $validated = $request->validate([
            'numero' => 'required|string|max:50|unique:contratos,numero,'.$contrato->id,
            'cliente_id' => 'required|exists:clientes,id',
            'unidade_id' => 'nullable|exists:unidades_empresa,id',
            'titulo' => 'nullable|string|max:255',
            'descricao' => 'nullable|string',
            'tipo_vigencia' => 'required|in:determinado,indeterminado',
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|date|required_if:tipo_vigencia,determinado|after_or_equal:data_inicio',
            'permite_renovacao' => 'boolean',
            'periodicidade' => 'required|string|in:atendimento_unico,semanal,quinzenal,mensal,bimestral,trimestral,semestral,anual,personalizado',
            'periodicidade_dias' => 'nullable|integer|min:1|required_if:periodicidade,personalizado',
            'dia_preferencial' => 'nullable|integer|between:1,31',
            'dia_semana' => 'nullable|integer|between:0,6',
            'tipo_servico' => 'required|string|in:controle_pragas,limpeza',
            'status' => 'nullable|string|in:proposta,ativo,rascunho,suspenso,encerrado,cancelado',
            'modelo_cobranca' => 'required|string|in:mensal,por_visita,fechamento_periodo,fixo_anual',
            'exige_nota_fiscal' => 'boolean',
            'percentual_imposto' => 'nullable|numeric|min:0|max:100',
            'custo_diaria_padrao' => 'nullable|numeric|min:0',
            'custo_deslocamento_padrao' => 'nullable|numeric|min:0',
            'meio_pagamento_id' => 'nullable|exists:meios_pagamento,id',
            'condicoes_pagamento' => 'nullable|string|max:255',
            'desconto' => 'nullable|numeric|min:0',
            'observacoes_comerciais' => 'nullable|string',
            'itens' => 'nullable|array',
            'itens.*.tipo_servico' => 'nullable|string|max:50',
            'itens.*.descricao' => 'required|string|max:255',
            'itens.*.quantidade' => 'required|numeric|min:0.01',
            'itens.*.valor_unitario' => 'required|numeric|min:0',
            'comodatos' => 'nullable|array',
            'comodatos.*.id' => 'nullable|integer',
            'comodatos.*.equipamento_id' => 'nullable|exists:equipamentos,id',
            'comodatos.*.nome_equipamento' => 'nullable|string|max:255',
            'comodatos.*.produto_id' => 'nullable|exists:produtos,id',
            'comodatos.*.quantidade' => 'required|integer|min:1',
            'comodatos.*.periodicidade_dias' => 'nullable|integer|min:1',
            'comodatos.*.observacoes' => 'nullable|string',
        ]);

        $eraAtivoAntes = $contrato->status === Contrato::STATUS_ATIVO;
        $dataInicioMudou = $contrato->data_inicio->toDateString() !== $validated['data_inicio'];

        $periodicidadeMudou = $contrato->periodicidade !== $validated['periodicidade']
            || (string) $contrato->periodicidade_dias !== (string) ($validated['periodicidade_dias'] ?? '')
            || $dataInicioMudou
            || (string) $contrato->dia_preferencial !== (string) ($validated['dia_preferencial'] ?? '')
            || (string) $contrato->dia_semana !== (string) ($validated['dia_semana'] ?? '');

        DB::transaction(function () use ($contrato, $validated, $request, $service, $periodicidadeMudou, $eraAtivoAntes, $dataInicioMudou) {
            $dataContrato = collect($validated)->except(['itens', 'comodatos'])->toArray();
            $dataContrato['permite_renovacao'] = $request->has('permite_renovacao');
            $dataContrato['exige_nota_fiscal'] = $request->has('exige_nota_fiscal');
            $dataContrato['percentual_imposto'] = $dataContrato['exige_nota_fiscal']
                ? (float) ($validated['percentual_imposto'] ?? Configuracao::get('aliquota_imposto_padrao', '14.0'))
                : 0.00;
            $dataContrato['desconto'] = (float) ($validated['desconto'] ?? 0);

            $contrato->update($dataContrato);

            // Sincroniza itens
            $contrato->itens()->delete();
            $subtotalItens = 0.00;

            if (! empty($validated['itens'])) {
                foreach ($validated['itens'] as $itemData) {
                    $qtd = (float) $itemData['quantidade'];
                    $vlrUnit = (float) $itemData['valor_unitario'];
                    $vlrTotal = round($qtd * $vlrUnit, 2);
                    $subtotalItens += $vlrTotal;

                    ContratoItem::create([
                        'contrato_id' => $contrato->id,
                        'tipo_servico' => $itemData['tipo_servico'] ?? $contrato->tipo_servico,
                        'descricao' => $itemData['descricao'],
                        'quantidade' => $qtd,
                        'valor_unitario' => $vlrUnit,
                        'valor_total' => $vlrTotal,
                    ]);
                }
            }

            $valorFinal = max(0, $subtotalItens - $dataContrato['desconto']);
            $contrato->update(['valor_total' => $valorFinal]);

            if ($request->has('comodatos_presente')) {
                $this->sincronizarComodatosEmbutidos(
                    $contrato,
                    $validated['comodatos'] ?? [],
                    $eraAtivoAntes,
                    $dataInicioMudou || $periodicidadeMudou
                );
            } elseif (! $eraAtivoAntes && $contrato->status === Contrato::STATUS_ATIVO) {
                $this->ativarComodatosDoContrato($contrato);
            } elseif ($periodicidadeMudou) {
                $this->sincronizarPeriodicidadeComodatosDoContrato($contrato, $dataInicioMudou);
            }

            if ($contrato->status === Contrato::STATUS_ATIVO) {
                $contrato->sincronizarMovimentacaoMes(now());
            }

            // Se for ativo e houve alteração na frequência ou datas, sincroniza visitas futuras não executadas
            if ($contrato->status === Contrato::STATUS_ATIVO && $periodicidadeMudou) {
                $service->sincronizarAgendamentosFuturos($contrato);
            }
        });

        return redirect()->route('contratos.show', $contrato)
            ->with('sucesso', 'Contrato atualizado com sucesso.');
    }

    public function ativar(Contrato $contrato)
    {
        $this->autorizar('contratos.ativar');

        if (! $contrato->podeAtivar()) {
            return redirect()->route('contratos.show', $contrato)
                ->with('alerta', 'Este contrato não pode ser ativado no momento.');
        }

        $eraProposta = $contrato->ehProposta();
        $eraAtivoAntes = $contrato->status === Contrato::STATUS_ATIVO;

        DB::transaction(function () use ($contrato, $eraAtivoAntes) {
            if (! $eraAtivoAntes) {
                $this->ativarComodatosDoContrato($contrato);
            }
            $contrato->update(['status' => Contrato::STATUS_ATIVO]);
            $contrato->sincronizarMovimentacaoMes(now());
        });

        $mensagem = $eraProposta
            ? "Proposta comercial {$contrato->numero} aprovada com sucesso! O contrato agora está ativo e seus atendimentos foram integrados à Agenda."
            : "Contrato {$contrato->numero} ativado com sucesso! Os atendimentos previstos estão disponíveis na Agenda Geral.";

        return redirect()->route('contratos.show', $contrato)
            ->with('sucesso', $mensagem);
    }

    public function pdf(Contrato $contrato, Request $request)
    {
        $this->autorizar('contratos.ver');

        if ($request->filled('unidade_id')) {
            $unidadeSelecionada = UnidadeEmpresa::findOrFail($request->integer('unidade_id'));
            $contrato->update(['unidade_id' => $unidadeSelecionada->id]);
        }

        $contrato->load(['cliente', 'unidade', 'itens', 'meioPagamento', 'comodatos.equipamento', 'comodatos.produto']);

        $unidades = UnidadeEmpresa::ativas()
            ->orderByDesc('padrao')
            ->orderByRaw("CASE WHEN tipo = 'matriz' THEN 0 ELSE 1 END ASC")
            ->orderBy('nome')
            ->get();

        if ($request->get('download') === '1' || $request->header('Accept') === 'application/pdf') {
            $nomeClienteArquivo = str_replace(['/', '\\'], '-', $contrato->cliente->nome);
            $nomeArquivo = ($contrato->ehProposta() ? 'Proposta Comercial' : 'Contrato').' #'.$contrato->numero.' — '.$nomeClienteArquivo.'.pdf';
            $pdfEngine = true;

            $pdf = Pdf::loadView('contratos.pdf', compact('contrato', 'unidades', 'pdfEngine'))
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'Helvetica',
                ]);

            return $pdf->stream($nomeArquivo);
        }

        return view('contratos.pdf', compact('contrato', 'unidades'));
    }

    public function suspender(Contrato $contrato, ContratoAgendamentoService $service)
    {
        $this->autorizar('contratos.suspender');

        if (! $contrato->podeSuspender()) {
            return redirect()->route('contratos.show', $contrato)
                ->with('alerta', 'Apenas contratos ativos podem ser suspensos.');
        }

        $service->tratarSuspensaoOuCancelamento($contrato, Contrato::STATUS_SUSPENSO);

        return redirect()->route('contratos.show', $contrato)
            ->with('sucesso', 'Contrato suspenso com sucesso. Visitas futuras não executadas foram suspensas.');
    }

    public function cancelar(Contrato $contrato, ContratoAgendamentoService $service)
    {
        $this->autorizar('contratos.cancelar');

        if (! $contrato->podeCancelar()) {
            return redirect()->route('contratos.show', $contrato)
                ->with('alerta', 'Este contrato não pode ser cancelado.');
        }

        $service->tratarSuspensaoOuCancelamento($contrato, Contrato::STATUS_CANCELADO);

        return redirect()->route('contratos.show', $contrato)
            ->with('sucesso', 'Contrato cancelado com sucesso. Agendamentos futuros foram cancelados e estoques liberados.');
    }

    public function encerrar(Contrato $contrato, ContratoAgendamentoService $service)
    {
        $this->autorizar('contratos.suspender');

        if (! $contrato->podeEncerrar()) {
            return redirect()->route('contratos.show', $contrato)
                ->with('alerta', 'Este contrato não pode ser encerrado.');
        }

        $service->tratarSuspensaoOuCancelamento($contrato, Contrato::STATUS_ENCERRADO);

        return redirect()->route('contratos.show', $contrato)
            ->with('sucesso', 'Contrato finalizado/encerrado com sucesso.');
    }

    public function gerarVisitasManuais(Contrato $contrato, ContratoAgendamentoService $service)
    {
        $this->autorizar('contratos.gerar_visitas');

        return redirect()->route('visitas.create', ['cliente_id' => $contrato->cliente_id, 'contrato_id' => $contrato->id])
            ->with('info', 'As Ordens de Serviço deste contrato devem ser emitidas manualmente definindo o técnico e horário de atendimento.');
    }

    public function destroy(Contrato $contrato)
    {
        $this->autorizar('contratos.deletar');

        // Se possui visitas concluídas ou em andamento, não permite exclusão física direta
        $temVisitasExecutadas = $contrato->visitas()->whereIn('status', [Visita::STATUS_CONCLUIDA, Visita::STATUS_EM_ANDAMENTO])->exists();
        if ($temVisitasExecutadas) {
            return redirect()->route('contratos.show', $contrato)
                ->with('erro', 'Não é possível excluir um contrato que já possui ordens de serviço executadas ou em andamento. Utilize a opção de Cancelamento ou Encerramento.');
        }

        DB::transaction(function () use ($contrato) {
            // Estorna produtos de visitas agendadas pendentes
            foreach ($contrato->visitas as $v) {
                $v->estornarEstoqueProdutos();
                $v->delete();
            }

            // Se o contrato não era proposta/rascunho, devolve o saldo dos equipamentos em comodato ao inventário
            $deveRestaurarEstoqueComodato = ! in_array($contrato->status, [Contrato::STATUS_PROPOSTA, Contrato::STATUS_RASCUNHO], true);
            foreach ($contrato->comodatos as $comodato) {
                if ($deveRestaurarEstoqueComodato && $comodato->equipamento_id) {
                    $comodato->equipamento?->increment('quantidade_disponivel', $comodato->quantidade);
                }
                $comodato->delete();
            }

            $contrato->movimentacoes()->delete();
            $contrato->itens()->delete();
            $contrato->delete();
        });

        return redirect()->route('contratos.index')
            ->with('sucesso', 'Contrato removido com sucesso.');
    }

    public function recolherComodatos(Contrato $contrato)
    {
        $this->autorizar('contratos.editar');

        $comodatos = $contrato->comodatos()->with('equipamento')->get();

        if ($comodatos->isEmpty()) {
            return redirect()->route('contratos.show', $contrato)
                ->with('alerta', 'Não há equipamentos em comodato vinculados a este contrato.');
        }

        $totalRecolhidos = 0;
        DB::transaction(function () use ($comodatos, &$totalRecolhidos) {
            foreach ($comodatos as $comodato) {
                $totalRecolhidos += $comodato->quantidade;
                if ($comodato->equipamento_id) {
                    $comodato->equipamento?->increment('quantidade_disponivel', $comodato->quantidade);
                }
                $comodato->delete();
            }
        });

        return redirect()->route('contratos.show', $contrato)
            ->with('sucesso', "{$totalRecolhidos} equipamento(s) em comodato foram recolhidos e devolvidos com sucesso ao estoque disponível da empresa.");
    }

    private function autorizar(string $permissao): void
    {
        $user = auth()->user();
        if (! $user || ! $user->temPermissao($permissao)) {
            abort(403, "Acesso não autorizado. Seu perfil não possui a permissão: {$permissao}.");
        }
    }

    private function normalizarCamposMonetarios(Request $request): void
    {
        $substituicoes = [];
        foreach (['desconto', 'percentual_imposto', 'custo_diaria_padrao', 'custo_deslocamento_padrao'] as $campo) {
            if ($request->has($campo) && is_string($request->input($campo))) {
                $val = trim($request->input($campo));
                if (str_contains($val, ',')) {
                    $val = str_replace(',', '.', str_replace('.', '', $val));
                }
                $substituicoes[$campo] = $val;
            }
        }

        if ($request->has('itens') && is_array($request->input('itens'))) {
            $itens = $request->input('itens');
            foreach ($itens as $idx => $item) {
                if (isset($item['valor_unitario']) && is_string($item['valor_unitario'])) {
                    $vlr = trim($item['valor_unitario']);
                    if (str_contains($vlr, ',')) {
                        $vlr = str_replace(',', '.', str_replace('.', '', $vlr));
                    }
                    $itens[$idx]['valor_unitario'] = $vlr;
                }
                if (isset($item['quantidade']) && is_string($item['quantidade'])) {
                    $qtd = trim($item['quantidade']);
                    if (str_contains($qtd, ',')) {
                        $qtd = str_replace(',', '.', str_replace('.', '', $qtd));
                    }
                    $itens[$idx]['quantidade'] = $qtd;
                }
            }
            $substituicoes['itens'] = $itens;
        }

        if (! empty($substituicoes)) {
            $request->merge($substituicoes);
        }
    }

    /**
     * Sincroniza os equipamentos em comodato incluídos diretamente no formulário do contrato/proposta.
     * A data de instalação segue a Data de Início da Vigência do contrato ($contrato->data_inicio).
     * Se o contrato estiver em proposta/rascunho, o estoque físico (quantidade_disponivel) não é debitado até a ativação.
     */
    private function sincronizarComodatosEmbutidos(
        Contrato $contrato,
        array $comodatosInput,
        bool $eraAtivoAntes,
        bool $dataInicioMudou = false
    ): void {
        $ehAtivoAgora = $contrato->status === Contrato::STATUS_ATIVO;
        $dataInstalacao = Carbon::parse($contrato->data_inicio)->startOfDay();

        $existentes = $contrato->comodatos()->with('equipamento')->get()->keyBy('id');
        $idsMantidos = [];

        foreach ($comodatosInput as $idx => $cData) {
            $equipId = ! empty($cData['equipamento_id']) ? (int) $cData['equipamento_id'] : null;
            $nomeEquip = trim((string) ($cData['nome_equipamento'] ?? ''));

            if (! $equipId && $nomeEquip === '') {
                continue;
            }

            $equipamento = $equipId ? Equipamento::findOrFail($equipId) : null;
            if ($equipamento && $nomeEquip === '') {
                $nomeEquip = $equipamento->nome.($equipamento->modelo ? " - {$equipamento->modelo}" : '');
            }

            $qtd = max(1, (int) ($cData['quantidade'] ?? 1));
            $periodicidadeDias = $contrato->periodicidadeEmDias();
            $produtoId = ! empty($cData['produto_id']) ? (int) $cData['produto_id'] : null;
            $obs = $cData['observacoes'] ?? null;

            $idExistente = ! empty($cData['id']) ? (int) $cData['id'] : null;
            $comodatoExistente = ($idExistente && $existentes->has($idExistente)) ? $existentes->get($idExistente) : null;

            if ($comodatoExistente) {
                $idsMantidos[] = $comodatoExistente->id;

                $antigoEquipId = $comodatoExistente->equipamento_id;
                $antigaQtd = (int) $comodatoExistente->quantidade;
                $antigaPeriodicidade = (int) $comodatoExistente->periodicidade_dias;

                // Ajuste de saldo físico no inventário de equipamentos
                if (! $eraAtivoAntes && $ehAtivoAgora) {
                    if ($equipamento) {
                        if ($qtd > $equipamento->quantidade_disponivel) {
                            throw ValidationException::withMessages([
                                "comodatos.{$idx}.quantidade" => "Quantidade solicitada ({$qtd} un) para '{$equipamento->nome}' excede o saldo disponível em estoque ({$equipamento->quantidade_disponivel} un).",
                            ]);
                        }
                        $equipamento->decrement('quantidade_disponivel', $qtd);
                    }
                } elseif ($eraAtivoAntes && $ehAtivoAgora) {
                    if ($equipId === $antigoEquipId) {
                        if ($equipamento) {
                            $diff = $qtd - $antigaQtd;
                            if ($diff > 0) {
                                if ($diff > $equipamento->quantidade_disponivel) {
                                    throw ValidationException::withMessages([
                                        "comodatos.{$idx}.quantidade" => "Aumento de {$diff} un para '{$equipamento->nome}' excede o saldo disponível ({$equipamento->quantidade_disponivel} un).",
                                    ]);
                                }
                                $equipamento->decrement('quantidade_disponivel', $diff);
                            } elseif ($diff < 0) {
                                $equipamento->increment('quantidade_disponivel', abs($diff));
                            }
                        }
                    } else {
                        if ($antigoEquipId) {
                            Equipamento::find($antigoEquipId)?->increment('quantidade_disponivel', $antigaQtd);
                        }
                        if ($equipamento) {
                            if ($qtd > $equipamento->quantidade_disponivel) {
                                throw ValidationException::withMessages([
                                    "comodatos.{$idx}.quantidade" => "Quantidade solicitada ({$qtd} un) para '{$equipamento->nome}' excede o saldo disponível ({$equipamento->quantidade_disponivel} un).",
                                ]);
                            }
                            $equipamento->decrement('quantidade_disponivel', $qtd);
                        }
                    }
                } elseif ($eraAtivoAntes && ! $ehAtivoAgora) {
                    // Se voltou para proposta/rascunho, devolve o saldo reservado
                    if (in_array($contrato->status, [Contrato::STATUS_PROPOSTA, Contrato::STATUS_RASCUNHO], true) && $antigoEquipId) {
                        Equipamento::find($antigoEquipId)?->increment('quantidade_disponivel', $antigaQtd);
                    }
                }

                // Se nunca teve reposição avulsa (data_ultima_reposicao == data_instalacao antiga) ou se data_inicio / periodicidade mudou
                $nuncaRepostoEmCampo = ! $comodatoExistente->data_ultima_reposicao
                    || ($comodatoExistente->data_instalacao && $comodatoExistente->data_ultima_reposicao->isSameDay($comodatoExistente->data_instalacao));

                $novaDataInstalacao = $dataInstalacao->toDateString();
                $novaUltimaReposicao = $comodatoExistente->data_ultima_reposicao
                    ? $comodatoExistente->data_ultima_reposicao->toDateString()
                    : $novaDataInstalacao;
                $novaProximaReposicao = $comodatoExistente->data_proxima_reposicao
                    ? $comodatoExistente->data_proxima_reposicao->toDateString()
                    : $contrato->calcularProximaDataReposicao($dataInstalacao)->toDateString();

                if ($nuncaRepostoEmCampo && ($dataInicioMudou || $periodicidadeDias !== $antigaPeriodicidade || ! $eraAtivoAntes)) {
                    $novaUltimaReposicao = $novaDataInstalacao;
                    $novaProximaReposicao = $contrato->calcularProximaDataReposicao($dataInstalacao)->toDateString();
                } elseif (! $nuncaRepostoEmCampo && ($dataInicioMudou || $periodicidadeDias !== $antigaPeriodicidade)) {
                    $novaProximaReposicao = $contrato->calcularProximaDataReposicao(Carbon::parse($novaUltimaReposicao))->toDateString();
                }

                $comodatoExistente->update([
                    'cliente_id' => $contrato->cliente_id,
                    'equipamento_id' => $equipId,
                    'produto_id' => $produtoId,
                    'nome_equipamento' => $nomeEquip,
                    'quantidade' => $qtd,
                    'periodicidade_dias' => $periodicidadeDias,
                    'data_instalacao' => $novaDataInstalacao,
                    'data_ultima_reposicao' => $novaUltimaReposicao,
                    'data_proxima_reposicao' => $novaProximaReposicao,
                    'observacoes' => $obs,
                ]);
            } else {
                // Novo comodato adicionado no contrato
                if ($ehAtivoAgora && $equipamento) {
                    if ($qtd > $equipamento->quantidade_disponivel) {
                        throw ValidationException::withMessages([
                            "comodatos.{$idx}.quantidade" => "Quantidade solicitada ({$qtd} un) para '{$equipamento->nome}' excede o saldo disponível em estoque ({$equipamento->quantidade_disponivel} un).",
                        ]);
                    }
                    $equipamento->decrement('quantidade_disponivel', $qtd);
                }

                $novo = EquipamentoComodato::create([
                    'cliente_id' => $contrato->cliente_id,
                    'contrato_id' => $contrato->id,
                    'equipamento_id' => $equipId,
                    'produto_id' => $produtoId,
                    'nome_equipamento' => $nomeEquip,
                    'quantidade' => $qtd,
                    'periodicidade_dias' => $periodicidadeDias,
                    'data_instalacao' => $dataInstalacao->toDateString(),
                    'data_ultima_reposicao' => $dataInstalacao->toDateString(),
                    'data_proxima_reposicao' => $contrato->calcularProximaDataReposicao($dataInstalacao)->toDateString(),
                    'observacoes' => $obs,
                ]);

                $idsMantidos[] = $novo->id;
            }
        }

        // Remove comodatos que foram excluídos da tabela no formulário de edição
        foreach ($existentes as $id => $comodatoAntigo) {
            if (! in_array($id, $idsMantidos, true)) {
                if ($eraAtivoAntes && $comodatoAntigo->equipamento_id) {
                    $comodatoAntigo->equipamento?->increment('quantidade_disponivel', $comodatoAntigo->quantidade);
                }
                $comodatoAntigo->delete();
            }
        }
    }

    /**
     * Sincroniza a periodicidade e próxima data de reposição dos comodatos vinculados quando a frequência do contrato muda.
     */
    private function sincronizarPeriodicidadeComodatosDoContrato(Contrato $contrato, bool $dataInicioMudou = false): void
    {
        $comodatos = $contrato->comodatos()->get();
        if ($comodatos->isEmpty()) {
            return;
        }

        $dataInstalacao = Carbon::parse($contrato->data_inicio)->startOfDay();
        $periodicidadeDias = $contrato->periodicidadeEmDias();

        foreach ($comodatos as $comodato) {
            $nuncaRepostoEmCampo = ! $comodato->data_ultima_reposicao
                || ($comodato->data_instalacao && $comodato->data_ultima_reposicao->isSameDay($comodato->data_instalacao));

            $novaInstalacao = $dataInicioMudou ? $dataInstalacao->toDateString() : ($comodato->data_instalacao?->toDateString() ?? $dataInstalacao->toDateString());
            $novaUltima = $nuncaRepostoEmCampo ? $novaInstalacao : $comodato->data_ultima_reposicao->toDateString();
            $novaProxima = $contrato->calcularProximaDataReposicao(Carbon::parse($novaUltima))->toDateString();

            $comodato->update([
                'periodicidade_dias' => $periodicidadeDias,
                'data_instalacao' => $novaInstalacao,
                'data_ultima_reposicao' => $novaUltima,
                'data_proxima_reposicao' => $novaProxima,
            ]);
        }
    }

    /**
     * Ativa operacionalmente os comodatos de uma proposta/rascunho quando o contrato é aprovado/ativado:
     * debita o saldo físico do inventário e alinha a data de instalação com a data_inicio do contrato.
     */
    private function ativarComodatosDoContrato(Contrato $contrato): void
    {
        $comodatos = $contrato->comodatos()->with('equipamento')->get();
        if ($comodatos->isEmpty()) {
            return;
        }

        $dataInstalacao = Carbon::parse($contrato->data_inicio)->startOfDay();
        $periodicidadeDias = $contrato->periodicidadeEmDias();

        foreach ($comodatos as $idx => $comodato) {
            if ($comodato->equipamento) {
                if ($comodato->quantidade > $comodato->equipamento->quantidade_disponivel) {
                    throw ValidationException::withMessages([
                        "comodatos.{$idx}.quantidade" => "Não foi possível ativar o contrato: o equipamento '{$comodato->equipamento->nome}' requer {$comodato->quantidade} un, mas há apenas {$comodato->equipamento->quantidade_disponivel} un disponíveis em estoque.",
                    ]);
                }
                $comodato->equipamento->decrement('quantidade_disponivel', $comodato->quantidade);
            }

            $comodato->update([
                'periodicidade_dias' => $periodicidadeDias,
                'data_instalacao' => $dataInstalacao->toDateString(),
                'data_ultima_reposicao' => $dataInstalacao->toDateString(),
                'data_proxima_reposicao' => $contrato->calcularProximaDataReposicao($dataInstalacao)->toDateString(),
            ]);
        }
    }
}
