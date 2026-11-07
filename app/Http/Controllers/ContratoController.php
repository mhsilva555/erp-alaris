<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Configuracao;
use App\Models\Contrato;
use App\Models\ContratoItem;
use App\Models\MeioPagamento;
use App\Models\Visita;
use App\Services\ContratoAgendamentoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContratoController extends Controller
{
    public function index(Request $request)
    {
        $this->autorizar('contratos.ver');

        $query = Contrato::with(['cliente', 'itens'])->withCount([
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
        $aliquotaPadrao = (float) Configuracao::get('aliquota_imposto_padrao', '14.0');
        $diariaPadrao = (float) Configuracao::get('diaria_tecnico_padrao', '50.00');

        return view('contratos.create', compact('clientes', 'clientePreSelecionado', 'proximoNumero', 'meiosPagamento', 'aliquotaPadrao', 'diariaPadrao'));
    }

    public function store(Request $request, ContratoAgendamentoService $service)
    {
        $this->autorizar('contratos.criar');

        $this->normalizarCamposMonetarios($request);

        $validated = $request->validate([
            'numero' => 'required|string|max:50|unique:contratos,numero',
            'cliente_id' => 'required|exists:clientes,id',
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
        ]);

        $contrato = DB::transaction(function () use ($validated, $request) {
            $dataContrato = collect($validated)->except('itens')->toArray();
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
            'meioPagamento',
            'itens',
            'visitas' => function ($q) {
                $q->orderByDesc('data_hora')->with(['tecnico', 'produtos']);
            },
        ]);

        $metricas = [
            'total_visitas' => $contrato->visitas->count(),
            'concluidas' => $contrato->visitas->where('status', Visita::STATUS_CONCLUIDA)->count(),
            'agendadas' => $contrato->visitas->where('status', Visita::STATUS_AGENDADA)->count(),
            'canceladas' => $contrato->visitas->where('status', Visita::STATUS_CANCELADA)->count(),
        ];

        return view('contratos.show', compact('contrato', 'metricas'));
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
        $aliquotaPadrao = (float) Configuracao::get('aliquota_imposto_padrao', '14.0');
        $diariaPadrao = (float) Configuracao::get('diaria_tecnico_padrao', '50.00');
        $contrato->load(['itens', 'meioPagamento']);

        return view('contratos.edit', compact('contrato', 'clientes', 'meiosPagamento', 'aliquotaPadrao', 'diariaPadrao'));
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
        ]);

        $periodicidadeMudou = $contrato->periodicidade !== $validated['periodicidade']
            || $contrato->data_inicio->toDateString() !== $validated['data_inicio']
            || (string) $contrato->dia_preferencial !== (string) ($validated['dia_preferencial'] ?? '')
            || (string) $contrato->dia_semana !== (string) ($validated['dia_semana'] ?? '');

        DB::transaction(function () use ($contrato, $validated, $request, $service, $periodicidadeMudou) {
            $dataContrato = collect($validated)->except('itens')->toArray();
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

        DB::transaction(function () use ($contrato) {
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

        $contrato->load(['cliente', 'itens', 'meioPagamento']);

        if ($request->get('download') === '1' || $request->header('Accept') === 'application/pdf') {
            $nomeArquivo = ($contrato->ehProposta() ? 'Proposta_Comercial_' : 'Contrato_').$contrato->numero.'.pdf';
            $pdfEngine = true;

            $pdf = Pdf::loadView('contratos.pdf', compact('contrato', 'pdfEngine'))
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'Helvetica',
                ]);

            return $pdf->stream($nomeArquivo);
        }

        return view('contratos.pdf', compact('contrato'));
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

        if ($contrato->status !== Contrato::STATUS_ATIVO) {
            return redirect()->route('contratos.show', $contrato)
                ->with('alerta', 'Apenas contratos ativos podem gerar novas visitas na agenda.');
        }

        $criadas = $service->gerarVisitas($contrato);

        return redirect()->route('contratos.show', $contrato)
            ->with('sucesso', $criadas > 0
                ? "Agenda abastecida com sucesso! +{$criadas} novas visitas foram registradas."
                : 'A agenda já está totalmente atualizada até o horizonte estabelecido.');
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
            $contrato->movimentacoes()->delete();
            $contrato->itens()->delete();
            $contrato->delete();
        });

        return redirect()->route('contratos.index')
            ->with('sucesso', 'Contrato removido com sucesso.');
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
}
