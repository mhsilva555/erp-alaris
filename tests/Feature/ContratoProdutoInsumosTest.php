<?php

use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\ContratoItem;
use App\Models\Produto;
use App\Models\User;
use App\Models\Visita;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->cliente = Cliente::create([
        'nome' => 'Empresa Cliente Teste Insumos',
        'cidade' => 'Campinas',
        'estado' => 'SP',
    ]);

    // Insumo 1: Líquido (1L = 1000 mL), Custo R$ 100,00 por Litro -> R$ 0,10 por mL
    $this->produtoLiquido = Produto::create([
        'nome' => 'Inseticida Deltametrina 2.5CE',
        'natureza' => 'liquido',
        'unidade' => 'L',
        'unidade_armazenamento' => 'L',
        'unidade_aplicacao' => 'mL',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 10,
        'preco_custo_unitario' => 100.00,
        'ativo' => true,
    ]);

    // Insumo 2: Sólido (1Kg = 1000 g), Custo R$ 50,00 por Kg -> R$ 0,05 por g
    $this->produtoSolido = Produto::create([
        'nome' => 'Isca Granulada Rodenticida',
        'natureza' => 'solido',
        'unidade' => 'Kg',
        'unidade_armazenamento' => 'Kg',
        'unidade_aplicacao' => 'g',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 20,
        'preco_custo_unitario' => 50.00,
        'ativo' => true,
    ]);
});

test('salva insumos planejados com calculo de custo proporcional ao criar proposta ou contrato', function () {
    $payload = [
        'numero' => 'PROP-2026-0001',
        'cliente_id' => $this->cliente->id,
        'titulo' => 'Proposta Comercial com Insumos Químicos',
        'status' => Contrato::STATUS_PROPOSTA,
        'tipo_vigencia' => Contrato::VIGENCIA_DETERMINADO,
        'data_inicio' => now()->toDateString(),
        'data_fim' => now()->addMonths(12)->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'custo_diaria_padrao' => '120,00',
        'custo_deslocamento_padrao' => '30,00',
        'itens' => [
            [
                'descricao' => 'Controle Integrado de Pragas Mensal',
                'tipo_servico' => 'controle_pragas',
                'quantidade' => 1,
                'valor_unitario' => '650,00',
            ],
        ],
        'produtos' => [
            [
                'produto_id' => $this->produtoLiquido->id,
                'quantidade' => '250', // 250 mL -> 250 * 0,10 = R$ 25,00
            ],
            [
                'produto_id' => $this->produtoSolido->id,
                'quantidade' => '100', // 100 g -> 100 * 0,05 = R$ 5,00
            ],
        ],
    ];

    $response = $this->actingAs($this->admin)->post(route('contratos.store'), $payload);
    $response->assertRedirect();

    $contrato = Contrato::where('numero', 'PROP-2026-0001')->first();
    expect($contrato)->not->toBeNull();
    expect($contrato->produtos)->toHaveCount(2);

    $pivotLiquido = $contrato->produtos->firstWhere('id', $this->produtoLiquido->id)->pivot;
    expect((float) $pivotLiquido->quantidade)->toEqual(250.00);
    expect((float) $pivotLiquido->custo_proporcional)->toEqual(25.00);

    $pivotSolido = $contrato->produtos->firstWhere('id', $this->produtoSolido->id)->pivot;
    expect((float) $pivotSolido->quantidade)->toEqual(100.00);
    expect((float) $pivotSolido->custo_proporcional)->toEqual(5.00);

    // Custo de insumos previsto por visita = 25.00 + 5.00 = 30.00
    expect($contrato->custoInsumosPrevistoPorVisita())->toEqual(30.00);

    // Custo operacional previsto total por visita = 30.00 (insumos) + 120.00 (diária) + 30.00 (deslocamento) = 180.00
    expect($contrato->custoOperacionalPrevisto())->toEqual(180.00);
});

test('atualiza insumos planejados na edicao de contrato', function () {
    $contrato = Contrato::create([
        'numero' => 'CTR-2026-EDIT',
        'cliente_id' => $this->cliente->id,
        'titulo' => 'Contrato para Edição',
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
    ]);

    $contrato->produtos()->attach($this->produtoLiquido->id, [
        'quantidade' => 100,
        'custo_proporcional' => 10.00,
    ]);

    expect($contrato->fresh()->produtos)->toHaveCount(1);

    // Atualiza alterando a dose do líquido e adicionando o sólido
    $response = $this->actingAs($this->admin)->put(route('contratos.update', $contrato), [
        'numero' => $contrato->numero,
        'cliente_id' => $this->cliente->id,
        'titulo' => 'Contrato Atualizado com Novos Insumos',
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'produtos_presente' => '1',
        'produtos' => [
            [
                'id' => $this->produtoLiquido->id,
                'quantidade' => '500', // 500 mL -> R$ 50,00
            ],
            [
                'id' => $this->produtoSolido->id,
                'quantidade' => '200', // 200 g -> R$ 10,00
            ],
        ],
    ]);

    $response->assertRedirect(route('contratos.show', $contrato));

    $contratoAtualizado = $contrato->fresh();
    expect($contratoAtualizado->produtos)->toHaveCount(2);

    $pivotLiquido = $contratoAtualizado->produtos->firstWhere('id', $this->produtoLiquido->id)->pivot;
    expect((float) $pivotLiquido->quantidade)->toEqual(500.00);
    expect((float) $pivotLiquido->custo_proporcional)->toEqual(50.00);

    $pivotSolido = $contratoAtualizado->produtos->firstWhere('id', $this->produtoSolido->id)->pivot;
    expect((float) $pivotSolido->quantidade)->toEqual(200.00);
    expect((float) $pivotSolido->custo_proporcional)->toEqual(10.00);

    // Agora envia vazio com flag produtos_presente para remover todos
    $this->actingAs($this->admin)->put(route('contratos.update', $contrato), [
        'numero' => $contrato->numero,
        'cliente_id' => $this->cliente->id,
        'titulo' => 'Contrato Sem Insumos',
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'produtos_presente' => '1',
        'produtos' => [],
    ]);

    expect($contrato->fresh()->produtos)->toBeEmpty();
    expect($contrato->fresh()->custoInsumosPrevistoPorVisita())->toEqual(0.00);
});

test('pre-carrega insumos planejados do contrato na criacao de ordem de servico (visitas.create)', function () {
    $contrato = Contrato::create([
        'numero' => 'CTR-2026-OS',
        'cliente_id' => $this->cliente->id,
        'titulo' => 'Contrato Manutenção Mensal',
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
    ]);

    $contrato->produtos()->attach($this->produtoLiquido->id, [
        'quantidade' => 350,
        'custo_proporcional' => 35.00,
    ]);

    $response = $this->actingAs($this->admin)->get(route('visitas.create', [
        'contrato_id' => $contrato->id,
        'cliente_id' => $this->cliente->id,
    ]));

    $response->assertOk();
    // Verifica se os dados do insumo planejado estão presentes na tela/JSON do script
    $response->assertSee($this->produtoLiquido->nome);
    $response->assertSee('350');
});

test('pdf de proposta ou contrato exibe insumos autorizados no escopo preservando sigilo de doses e custos', function () {
    $contrato = Contrato::create([
        'numero' => 'PROP-2026-PDF',
        'cliente_id' => $this->cliente->id,
        'titulo' => 'Proposta Comercial Especial',
        'status' => Contrato::STATUS_PROPOSTA,
        'tipo_vigencia' => Contrato::VIGENCIA_DETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'custo_diaria_padrao' => 150.00,
        'custo_deslocamento_padrao' => 45.00,
    ]);

    ContratoItem::create([
        'contrato_id' => $contrato->id,
        'descricao' => 'Controle Profissional de Pragas',
        'tipo_servico' => 'controle_pragas',
        'quantidade' => 1,
        'valor_unitario' => 800.00,
        'valor_total' => 800.00,
    ]);

    $contrato->produtos()->attach($this->produtoLiquido->id, [
        'quantidade' => 300, // 300 mL
        'custo_proporcional' => 30.00, // R$ 30,00
    ]);

    $response = $this->actingAs($this->admin)->get(route('contratos.pdf', $contrato));

    $response->assertOk();
    // DEVE exibir o nome do insumo saneante no escopo técnico
    $response->assertSee('Insumos e Saneantes Autorizados no Escopo');
    $response->assertSee('Inseticida Deltametrina 2.5CE');

    // NUNCA deve exibir a dose (300 mL), o custo proporcional (30,00) ou a diária (150,00)
    $response->assertDontSee('300 mL');
    $response->assertDontSee('30,00');
    $response->assertDontSee('150,00');
});
