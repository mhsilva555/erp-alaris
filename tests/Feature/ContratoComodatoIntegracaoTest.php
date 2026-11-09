<?php

use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\ContratoItem;
use App\Models\Equipamento;
use App\Models\EquipamentoComodato;
use App\Models\Produto;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Visita;

test('pode alocar equipamento em comodato vinculado a um contrato e visualiza-lo no detalhe do contrato', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create([
        'nome' => 'Frigorífico Boi Gordo',
        'cidade' => 'Imperatriz',
        'estado' => 'MA',
    ]);

    $contrato = Contrato::create([
        'numero' => 'CTR-2026-0200',
        'cliente_id' => $cliente->id,
        'titulo' => 'Contrato Anual com Monitoramento',
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => '2026-11-01',
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'valor_total' => 900.00,
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
    ]);

    $equipamento = Equipamento::create([
        'nome' => 'Porta-Iscas Túnel',
        'modelo' => 'Termoplástico Reforçado',
        'tipo' => 'porta_isca',
        'quantidade_total' => 50,
        'quantidade_disponivel' => 50,
        'custo_aquisicao' => 15.00,
    ]);

    // Cria comodato vinculado ao contrato
    $response = $this->actingAs($admin)->post(route('comodatos.store'), [
        'cliente_id' => $cliente->id,
        'contrato_id' => $contrato->id,
        'equipamento_id' => $equipamento->id,
        'quantidade' => 12,
        'periodicidade_dias' => 15,
        'data_instalacao' => '2026-11-05',
        'observacoes' => 'Pontos 01 a 12 perimetrais externos',
        'retorno_contrato' => 1,
    ]);

    $response->assertRedirect(route('contratos.show', $contrato));

    $comodato = EquipamentoComodato::where('contrato_id', $contrato->id)->first();
    expect($comodato)->not->toBeNull();
    expect($comodato->quantidade)->toBe(12);
    expect($comodato->periodicidade_dias)->toBe(30); // Acompanha periodicidade mensal do contrato
    expect($comodato->cliente_id)->toBe($cliente->id);
    expect($comodato->contrato->id)->toBe($contrato->id);

    // Saldo disponível do equipamento foi decrementado
    $equipamento->refresh();
    expect($equipamento->quantidade_disponivel)->toBe(38);

    // Visualiza tela do contrato e verifica se o comodato consta no card
    $responseShow = $this->actingAs($admin)->get(route('contratos.show', $contrato));
    $responseShow->assertOk();
    $responseShow->assertSee('Equipamentos em Comodato (1)');
    $responseShow->assertSee('Porta-Iscas Túnel');
    $responseShow->assertSee('12 un');
    $responseShow->assertSee('30 dias');
    $responseShow->assertSee('Pontos 01 a 12 perimetrais externos');

    // Visualiza listagem de comodatos e verifica se o link do contrato aparece
    $responseIndex = $this->actingAs($admin)->get(route('comodatos.index'));
    $responseIndex->assertOk();
    $responseIndex->assertSee('Contrato #CTR-2026-0200');
});

test('pode editar comodato alterando ou desvinculando o contrato', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create(['nome' => 'Panificadora Central']);

    $contrato1 = Contrato::create([
        'numero' => 'CTR-2026-0201',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => '2026-11-01',
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'valor_total' => 400.00,
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
    ]);

    $contrato2 = Contrato::create([
        'numero' => 'CTR-2026-0202',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => '2026-11-01',
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'valor_total' => 600.00,
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
    ]);

    $comodato = EquipamentoComodato::create([
        'cliente_id' => $cliente->id,
        'contrato_id' => $contrato1->id,
        'nome_equipamento' => 'Armadilha Luminosa',
        'quantidade' => 3,
        'periodicidade_dias' => 30,
        'data_instalacao' => '2026-11-01',
        'data_ultima_reposicao' => '2026-11-01',
        'data_proxima_reposicao' => '2026-12-01',
    ]);

    expect($comodato->contrato_id)->toBe($contrato1->id);

    // Atualiza comodato vinculando ao contrato 2
    $response = $this->actingAs($admin)->put(route('comodatos.update', $comodato), [
        'cliente_id' => $cliente->id,
        'contrato_id' => $contrato2->id,
        'nome_equipamento' => 'Armadilha Luminosa',
        'quantidade' => 3,
        'periodicidade_dias' => 30,
        'data_instalacao' => '2026-11-01',
    ]);

    $response->assertRedirect(route('comodatos.index'));

    $comodato->refresh();
    expect($comodato->contrato_id)->toBe($contrato2->id);

    // Contrato 2 agora possui esse comodato e Contrato 1 não mais
    expect($contrato2->comodatos()->count())->toBe(1);
    expect($contrato1->comodatos()->count())->toBe(0);
});

test('exibe equipamentos em comodato no pdf da proposta e contrato mantendo integridade visual', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create(['nome' => 'Armazém Geral da Silva', 'cidade' => 'Imperatriz', 'estado' => 'MA']);

    $contrato = Contrato::create([
        'numero' => 'CTR-2026-0203',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_PROPOSTA,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => '2026-11-01',
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'valor_total' => 750.00,
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
    ]);

    ContratoItem::create([
        'contrato_id' => $contrato->id,
        'descricao' => 'Controle Integrado de Roedores',
        'quantidade' => 1,
        'valor_unitario' => 750.00,
        'valor_total' => 750.00,
    ]);

    $produto = Produto::create([
        'nome' => 'Bloco Parafinado Raticida',
        'unidade' => 'Kg',
        'unidade_armazenamento' => 'Kg',
        'unidade_aplicacao' => 'g',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 10,
        'preco_custo' => 50.00,
    ]);

    EquipamentoComodato::create([
        'cliente_id' => $cliente->id,
        'contrato_id' => $contrato->id,
        'produto_id' => $produto->id,
        'nome_equipamento' => 'Porta-Iscas Túnel Segurança',
        'quantidade' => 8,
        'periodicidade_dias' => 15,
        'data_instalacao' => '2026-11-01',
    ]);

    $response = $this->actingAs($admin)->get(route('contratos.pdf', $contrato));
    $response->assertOk();
    $response->assertSee('Equipamentos Cedidos em Regime de Comodato:');
    $response->assertSee('Porta-Iscas Túnel Segurança');
    $response->assertSee('8 un');
    $response->assertSee('A cada 15 dias');
    $response->assertSee('Bloco Parafinado Raticida');
    $response->assertSee('Equipamentos de propriedade da Contratada, cedidos sob comodato');

    // Download de PDF em stream DomPDF
    $responsePdf = $this->actingAs($admin)->get(route('contratos.pdf', [$contrato, 'download' => 1]));
    $responsePdf->assertOk();
    expect($responsePdf->headers->get('content-type'))->toContain('application/pdf');
});

test('conclusao de ordem de servico vinculada ao contrato sincroniza automaticamente a reposicao de todos os comodatos alocados', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $tecnico = Tecnico::create(['nome' => 'Marcos Operacional', 'telefone' => '9999999999', 'ativo' => true]);

    $cliente = Cliente::create(['nome' => 'Supermercado Progresso']);

    $contrato = Contrato::create([
        'numero' => 'CTR-2026-0204',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => '2026-11-01',
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'valor_total' => 1200.00,
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
    ]);

    $comodato1 = EquipamentoComodato::create([
        'cliente_id' => $cliente->id,
        'contrato_id' => $contrato->id,
        'nome_equipamento' => 'Porta-Iscas Externo',
        'quantidade' => 10,
        'periodicidade_dias' => 15,
        'data_instalacao' => '2026-11-01',
        'data_ultima_reposicao' => '2026-11-01',
        'data_proxima_reposicao' => '2026-11-16',
    ]);

    $comodato2 = EquipamentoComodato::create([
        'cliente_id' => $cliente->id,
        'contrato_id' => $contrato->id,
        'nome_equipamento' => 'Armadilha Adesiva',
        'quantidade' => 5,
        'periodicidade_dias' => 30,
        'data_instalacao' => '2026-11-01',
        'data_ultima_reposicao' => '2026-11-01',
        'data_proxima_reposicao' => '2026-12-01',
    ]);

    // Cria visita vinculada ao contrato agendada para 2026-11-10
    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'contrato_id' => $contrato->id,
        'tecnico_id' => $tecnico->id,
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'status' => Visita::STATUS_AGENDADA,
        'data_hora' => '2026-11-10 10:00:00',
        'valor_cobrado' => 0.00,
    ]);

    // Finaliza a visita em 2026-11-10
    $response = $this->actingAs($admin)->post(route('visitas.finalizar', $visita), [
        'observacoes' => 'Atendimento mensal executado com revisão perimetral e troca de iscas.',
    ]);

    $response->assertRedirect(route('visitas.show', $visita));

    $comodato1->refresh();
    $comodato2->refresh();

    // Ambas as reposições foram sincronizadas com a data da visita e seguem a próxima data do contrato mensal (2026-12-01)
    expect($comodato1->data_ultima_reposicao->toDateString())->toBe('2026-11-10');
    expect($comodato1->periodicidade_dias)->toBe(30);
    expect($comodato1->data_proxima_reposicao->toDateString())->toBe('2026-12-01');

    expect($comodato2->data_ultima_reposicao->toDateString())->toBe('2026-11-10');
    expect($comodato2->periodicidade_dias)->toBe(30);
    expect($comodato2->data_proxima_reposicao->toDateString())->toBe('2026-12-01');
});

test('recolhimento de comodatos em contrato encerrado ou cancelado restaura saldo do estoque disponivel', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create(['nome' => 'Restaurante Sabor da Terra']);

    $contrato = Contrato::create([
        'numero' => 'CTR-2026-0205',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_CANCELADO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => '2026-10-01',
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'valor_total' => 500.00,
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
    ]);

    $equipamento = Equipamento::create([
        'nome' => 'Porta-Iscas Chapa',
        'tipo' => 'porta_isca',
        'quantidade_total' => 20,
        'quantidade_disponivel' => 14, // 6 alocados
    ]);

    $comodato = EquipamentoComodato::create([
        'cliente_id' => $cliente->id,
        'contrato_id' => $contrato->id,
        'equipamento_id' => $equipamento->id,
        'nome_equipamento' => 'Porta-Iscas Chapa',
        'quantidade' => 6,
        'periodicidade_dias' => 30,
        'data_instalacao' => '2026-10-01',
    ]);

    // Exibe tela do contrato cancelado com alerta de recolhimento
    $responseShow = $this->actingAs($admin)->get(route('contratos.show', $contrato));
    $responseShow->assertOk();
    $responseShow->assertSee('Equipamentos Pendentes de Recolhimento');
    $responseShow->assertSee('Devolver ao Estoque');
    $responseShow->assertSee('Gerar O.S. de Recolhimento');

    // Executa ação de devolução / recolhimento
    $responseRecolher = $this->actingAs($admin)->post(route('contratos.recolher-comodatos', $contrato));
    $responseRecolher->assertRedirect(route('contratos.show', $contrato));
    $responseRecolher->assertSessionHas('sucesso');

    // Comodato foi encerrado/deletado e estoque disponível restaurado para o total (14 + 6 = 20)
    expect(EquipamentoComodato::find($comodato->id))->toBeNull();
    $equipamento->refresh();
    expect($equipamento->quantidade_disponivel)->toBe(20);
});

test('agenda geral nao projeta reposicoes preventivas de comodatos vinculados a contratos cancelados ou encerrados', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create(['nome' => 'Depósito São Bento']);

    $contratoCancelado = Contrato::create([
        'numero' => 'CTR-2026-0206',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_CANCELADO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => '2026-10-01',
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'valor_total' => 600.00,
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
    ]);

    $comodatoCancelado = EquipamentoComodato::create([
        'cliente_id' => $cliente->id,
        'contrato_id' => $contratoCancelado->id,
        'nome_equipamento' => 'Isca Externa Desativada',
        'quantidade' => 4,
        'periodicidade_dias' => 15,
        'data_instalacao' => '2026-10-01',
        'data_proxima_reposicao' => '2026-11-15',
    ]);

    // Acessa agenda geral para o mês de novembro/2026
    $response = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-11']));
    $response->assertOk();

    // Não deve conter a reposição do comodato do contrato cancelado
    $response->assertDontSee('Isca Externa Desativada');
});

test('listagem de contratos no formulario de comodato filtra apenas contratos ativos do cliente selecionado e valida vinculo', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $clienteA = Cliente::create(['nome' => 'Empresa Alfa']);
    $clienteB = Cliente::create(['nome' => 'Empresa Beta']);

    $contratoAtivoA = Contrato::create([
        'numero' => 'CTR-2026-0301',
        'cliente_id' => $clienteA->id,
        'titulo' => 'Contrato Ativo Alfa',
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => '2026-11-01',
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'valor_total' => 500.00,
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
    ]);

    $contratoPropostaA = Contrato::create([
        'numero' => 'CTR-2026-0302',
        'cliente_id' => $clienteA->id,
        'titulo' => 'Proposta Comercial Alfa',
        'status' => Contrato::STATUS_PROPOSTA,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => '2026-11-01',
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'valor_total' => 500.00,
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
    ]);

    $contratoAtivoB = Contrato::create([
        'numero' => 'CTR-2026-0303',
        'cliente_id' => $clienteB->id,
        'titulo' => 'Contrato Ativo Beta',
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => '2026-11-01',
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'valor_total' => 800.00,
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
    ]);

    // 1. Acessando create com clienteA pré-selecionado: só deve renderizar option para contratoAtivoA, e nunca contratoPropostaA
    $resCreateA = $this->actingAs($admin)->get(route('comodatos.create', ['cliente_id' => $clienteA->id]));
    $resCreateA->assertOk();
    $resCreateA->assertSee('Contrato #CTR-2026-0301');
    $resCreateA->assertDontSee('CTR-2026-0302');

    // 2. Tentativa de salvar comodato para clienteA vinculando proposta (não ativo) deve falhar na validação
    $equipamento = Equipamento::create([
        'nome' => 'Porta-Isca Teste',
        'tipo' => 'porta_isca',
        'quantidade_total' => 10,
        'quantidade_disponivel' => 10,
    ]);

    $resStoreInvalidoStatus = $this->actingAs($admin)->post(route('comodatos.store'), [
        'cliente_id' => $clienteA->id,
        'contrato_id' => $contratoPropostaA->id,
        'equipamento_id' => $equipamento->id,
        'quantidade' => 2,
        'periodicidade_dias' => 15,
        'data_instalacao' => '2026-11-05',
    ]);
    $resStoreInvalidoStatus->assertSessionHasErrors('contrato_id');

    // 3. Tentativa de salvar comodato para clienteA vinculando contrato ativo do clienteB deve falhar na validação
    $resStoreInvalidoCliente = $this->actingAs($admin)->post(route('comodatos.store'), [
        'cliente_id' => $clienteA->id,
        'contrato_id' => $contratoAtivoB->id,
        'equipamento_id' => $equipamento->id,
        'quantidade' => 2,
        'periodicidade_dias' => 15,
        'data_instalacao' => '2026-11-05',
    ]);
    $resStoreInvalidoCliente->assertSessionHasErrors('contrato_id');
});

test('proposta comercial criada com comodatos inclusos usa data de inicio da vigencia sem baixar estoque nem gerar eventos na agenda ate aprovacao', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create(['nome' => 'Indústria Metalúrgica Sul', 'cidade' => 'Imperatriz', 'estado' => 'MA']);

    $equipamento = Equipamento::create([
        'nome' => 'Porta-Iscas Blindado',
        'modelo' => 'Inox',
        'tipo' => 'porta_isca',
        'quantidade_total' => 30,
        'quantidade_disponivel' => 30,
    ]);

    $produto = Produto::create([
        'nome' => 'Raticida Bloco Extrusado',
        'unidade' => 'Kg',
        'unidade_armazenamento' => 'Kg',
        'unidade_aplicacao' => 'g',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 20,
        'preco_custo' => 45.00,
    ]);

    // 1. Cria Proposta Comercial já com Comodato incluso e Data de Início 2026-11-10
    $resStore = $this->actingAs($admin)->post(route('contratos.store'), [
        'numero' => 'CTR-2026-0400',
        'cliente_id' => $cliente->id,
        'titulo' => 'Proposta Anual com Comodato',
        'status' => Contrato::STATUS_PROPOSTA,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => '2026-11-10',
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'dia_preferencial' => 10,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'itens' => [
            [
                'descricao' => 'Controle Integrado de Pragas e Roedores',
                'tipo_servico' => 'controle_pragas',
                'quantidade' => 1,
                'valor_unitario' => '850,00',
            ],
        ],
        'comodatos' => [
            [
                'equipamento_id' => $equipamento->id,
                'produto_id' => $produto->id,
                'quantidade' => 10,
                'periodicidade_dias' => 15,
                'observacoes' => 'Barreira externa galpão A',
            ],
        ],
    ]);

    $contrato = Contrato::where('numero', 'CTR-2026-0400')->first();
    expect($contrato)->not->toBeNull();
    $resStore->assertRedirect(route('contratos.show', $contrato));

    // Verifica que o comodato foi criado com data_instalacao = data_inicio do contrato (2026-11-10) e periodicidade do contrato (mensal = 30 dias)
    $comodato = $contrato->comodatos()->first();
    expect($comodato)->not->toBeNull();
    expect($comodato->quantidade)->toBe(10);
    expect($comodato->periodicidade_dias)->toBe(30);
    expect($comodato->data_instalacao->toDateString())->toBe('2026-11-10');
    expect($comodato->data_proxima_reposicao->toDateString())->toBe('2026-12-10');
    expect($comodato->estaOperacionalmenteAtivo())->toBeFalse();

    // Estoque físico NÃO foi debitado enquanto está em proposta (permanece 30)
    $equipamento->refresh();
    expect($equipamento->quantidade_disponivel)->toBe(30);

    // PDF da Proposta Comercial exibe o equipamento em comodato normalmente
    $resPdf = $this->actingAs($admin)->get(route('contratos.pdf', $contrato));
    $resPdf->assertOk();
    $resPdf->assertSee('Porta-Iscas Blindado');
    $resPdf->assertSee('10 un');

    // Agenda Geral NÃO exibe eventos deste comodato enquanto for proposta
    $resAgendaProposta = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-11']));
    $resAgendaProposta->assertOk();
    $resAgendaProposta->assertDontSee('Porta-Iscas Blindado');

    // 2. Aprova a Proposta Comercial (Ativa o Contrato)
    $resAtivar = $this->actingAs($admin)->post(route('contratos.ativar', $contrato));
    $resAtivar->assertRedirect(route('contratos.show', $contrato));

    $contrato->refresh();
    $comodato->refresh();
    $equipamento->refresh();

    expect($contrato->status)->toBe(Contrato::STATUS_ATIVO);
    expect($comodato->estaOperacionalmenteAtivo())->toBeTrue();
    expect($comodato->data_instalacao->toDateString())->toBe('2026-11-10');
    expect($comodato->data_proxima_reposicao->toDateString())->toBe('2026-12-10');

    // Agora o estoque físico do equipamento foi debitado (30 - 10 = 20)
    expect($equipamento->quantidade_disponivel)->toBe(20);

    // E a Agenda Geral passa a projetar o Atendimento Contratual com o comodato unificado no mês
    $resAgendaAtivo = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-11']));
    $resAgendaAtivo->assertOk();
    $resAgendaAtivo->assertSee('Porta-Iscas Blindado');
});

test('contrato criado ja como ativo com comodatos inclusos baixa estoque imediatamente e usa data de inicio da vigencia', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create(['nome' => 'Clínica Vida Saudável']);

    $equipamento = Equipamento::create([
        'nome' => 'Armadilha Luminosa UV',
        'tipo' => 'armadilha_luminosa',
        'quantidade_total' => 15,
        'quantidade_disponivel' => 15,
    ]);

    $this->actingAs($admin)->post(route('contratos.store'), [
        'numero' => 'CTR-2026-0401',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => '2026-11-05',
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'itens' => [
            [
                'descricao' => 'Monitoramento de Vetores Voadores',
                'tipo_servico' => 'controle_pragas',
                'quantidade' => 1,
                'valor_unitario' => '600,00',
            ],
        ],
        'comodatos' => [
            [
                'equipamento_id' => $equipamento->id,
                'quantidade' => 4,
                'observacoes' => 'Recepção e corredor cirúrgico',
            ],
        ],
    ]);

    $contrato = Contrato::where('numero', 'CTR-2026-0401')->first();
    $comodato = $contrato->comodatos()->first();

    expect($comodato->periodicidade_dias)->toBe(30);
    expect($comodato->data_instalacao->toDateString())->toBe('2026-11-05');
    expect($comodato->data_proxima_reposicao->toDateString())->toBe('2026-12-05'); // Próximo ciclo mensal após 05/11

    $equipamento->refresh();
    expect($equipamento->quantidade_disponivel)->toBe(11); // 15 - 4
});

test('comodatos vinculados a contrato sao unificados diretamente no atendimento contratual na agenda gerando um unico evento e uma unica OS', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $tecnico = Tecnico::create([
        'nome' => 'Carlos Campo',
        'telefone' => '9999999999',
        'valor_diaria_padrao' => 80.00,
        'ativo' => true,
    ]);

    $cliente = Cliente::create(['nome' => 'Shopping Center Norte', 'cidade' => 'Imperatriz', 'estado' => 'MA']);

    $equip1 = Equipamento::create([
        'nome' => 'Porta-Iscas Externo',
        'tipo' => 'porta_isca',
        'quantidade_total' => 50,
        'quantidade_disponivel' => 50,
    ]);

    $equip2 = Equipamento::create([
        'nome' => 'Armadilha Luminosa Adesiva',
        'tipo' => 'armadilha_luminosa',
        'quantidade_total' => 20,
        'quantidade_disponivel' => 20,
    ]);

    $insumoRaticida = Produto::create([
        'nome' => 'Bloco Parafinado Raticida 20g',
        'unidade_medida' => 'Un',
        'unidade_aplicacao' => 'Un',
        'fator_conversao' => 1,
        'quantidade_estoque' => 100,
        'estoque_minimo' => 10,
        'preco_custo' => 2.50,
    ]);

    // Cria contrato quinzenal ativo iniciando em 09/11/2026 com 2 equipamentos em comodato vinculados
    $this->actingAs($admin)->post(route('contratos.store'), [
        'numero' => 'CTR-2026-0500',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => '2026-11-09',
        'periodicidade' => Contrato::PERIODICIDADE_QUINZENAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'custo_diaria_padrao' => '80,00',
        'itens' => [
            [
                'descricao' => 'Controle Integrado de Pragas',
                'tipo_servico' => 'controle_pragas',
                'quantidade' => 1,
                'valor_unitario' => '1500,00',
            ],
        ],
        'comodatos' => [
            [
                'equipamento_id' => $equip1->id,
                'produto_id' => $insumoRaticida->id,
                'quantidade' => 15,
                'observacoes' => 'Estacionamento externo',
            ],
            [
                'equipamento_id' => $equip2->id,
                'quantidade' => 6,
                'observacoes' => 'Praça de alimentação',
            ],
        ],
    ]);

    $contrato = Contrato::where('numero', 'CTR-2026-0500')->first();
    expect($contrato->comodatos()->count())->toBe(2);

    // Ao abrir a tela de emissão da O.S. vinculada ao contrato, o insumo do comodato deve ser pré-listado na seção 2
    $resCreateOs = $this->actingAs($admin)->get(route('visitas.create', [
        'cliente_id' => $cliente->id,
        'contrato_id' => $contrato->id,
        'tipo_visita' => 'execucao',
    ]));
    $resCreateOs->assertOk();
    $resCreateOs->assertSee('Insumo vinculado ao comodato:');
    $resCreateOs->assertSee('Porta-Iscas Externo (15 un)');
    $resCreateOs->assertSee('Bloco Parafinado Raticida 20g');

    // Ambos os comodatos acompanham o ciclo do contrato (Quinzenal -> próxima data do contrato após 09/11 é 23/11)
    foreach ($contrato->comodatos as $c) {
        expect($c->periodicidade_dias)->toBe(15);
        expect($c->data_proxima_reposicao->toDateString())->toBe('2026-11-23');
    }

    // Na Agenda Geral de Novembro/2026:
    // NÃO deve existir evento separado de "Reposição de Comodato", mas os 2 atendimentos contratuais
    // com comodatos vinculados devem ser contabilizados na aba "Comodatos (2)" sem inflar o Total Geral (2)!
    $resAgenda = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-11']));
    $resAgenda->assertOk();

    $totais = $resAgenda->viewData('totais');
    expect($totais['total_comodatos'])->toBe(2);
    expect($totais['total_visitas'])->toBe(2); // 09/11 e 23/11
    expect($totais['total_geral'])->toBe(2);

    $resAgenda->assertSee('Comodatos (2)');
    $resAgenda->assertSee('Atendimento Contratual: Shopping Center Norte');
    $resAgenda->assertSee('Comodato incluso: Porta-Iscas Externo (15 un), Armadilha Luminosa Adesiva (6 un)');
    $resAgenda->assertSee('2 comodatos vinculados');
    $resAgenda->assertSee('+ Comodato Incluso');
    $resAgenda->assertDontSee('Reposição: Porta-Iscas Externo');

    // E ao filtrar pela aba "Comodatos" (tipo=comodatos), os 2 atendimentos contratuais com comodato aparecem listados
    $resAbaComodatos = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-11', 'tipo' => 'comodatos']));
    $resAbaComodatos->assertOk();
    expect($resAbaComodatos->viewData('agendaOrdenada')->count())->toBe(2);

    // E ao emitir e concluir 1 única O.S. para o atendimento do dia 09/11:
    $primeiroComodato = $contrato->comodatos->first();
    $resOs = $this->actingAs($admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'contrato_id' => $contrato->id,
        'comodato_id' => $primeiroComodato->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => '2026-11-09 09:00:00',
        'status' => Visita::STATUS_CONCLUIDA,
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'custo_mao_de_obra' => '80,00',
        'observacoes' => 'Atendimento contratual + reposição de comodatos executados.',
    ]);
    $resOs->assertRedirect(route('visitas.index'));

    $visitaCriada = Visita::where('contrato_id', $contrato->id)->first();
    expect($visitaCriada)->not->toBeNull();
    expect((float) $visitaCriada->custo_mao_de_obra)->toBe(80.00);

    // Na Agenda Geral, a O.S. emitida para 09/11 substitui a projeção de 09/11 sem duplicar eventos (continuam sendo 2 itens no mês: OS em 09/11 e projeção em 23/11)
    $resAgendaAposOs = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-11']));
    $totaisAposOs = $resAgendaAposOs->viewData('totais');
    expect($totaisAposOs['total_geral'])->toBe(2);

    // Apenas 1 lançamento de diária técnica foi gerado no financeiro (sem duplicidade)
    $despesasDiaria = $visitaCriada->movimentacoes()->where('categoria', 'mao_de_obra')->get();
    expect($despesasDiaria->count())->toBe(1);
    expect((float) $despesasDiaria->first()->valor)->toBe(80.00);

    // E AMBOS os comodatos tiveram sua data de próxima reposição sincronizada para a próxima data do contrato (23/11)
    foreach ($contrato->comodatos()->get() as $cmd) {
        expect($cmd->data_ultima_reposicao->toDateString())->toBe('2026-11-09');
        expect($cmd->data_proxima_reposicao->toDateString())->toBe('2026-11-23');
    }

    // E o PDF/Impressão da O.S. Técnica (visitas.os) lista os nomes, quantidades, observações e status dos equipamentos
    $resPdfOs = $this->actingAs($admin)->get(route('visitas.os', $visitaCriada));
    $resPdfOs->assertOk();
    $resPdfOs->assertSee('Porta-Iscas Externo');
    $resPdfOs->assertSee('(15 un)');
    $resPdfOs->assertSee('Estacionamento externo');
    $resPdfOs->assertSee('Armadilha Luminosa Adesiva');
    $resPdfOs->assertSee('(6 un)');
    $resPdfOs->assertSee('Praça de alimentação');
});
