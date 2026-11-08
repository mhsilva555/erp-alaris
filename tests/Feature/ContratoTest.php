<?php

use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\ContratoItem;
use App\Models\MeioPagamento;
use App\Models\MovimentacaoFinanceira;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Visita;
use App\Services\ContratoAgendamentoService;
use Carbon\Carbon;
use Database\Seeders\MeioPagamentoSeeder;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->tecnico = Tecnico::create(['nome' => 'Técnico Especialista', 'ativo' => true]);
    $this->tecnicoUser = User::factory()->create(['role' => User::ROLE_TECNICO, 'tecnico_id' => $this->tecnico->id]);
});

test('1. cadastro de cliente sem periodicidade', function () {
    $response = $this->actingAs($this->admin)->post(route('clientes.store'), [
        'nome' => 'Cliente Sem Periodicidade',
        'cidade' => 'São Paulo',
        'estado' => 'SP',
        'origem' => 'Indicacao',
    ]);

    $response->assertRedirect(route('clientes.index'));

    $cliente = Cliente::where('nome', 'Cliente Sem Periodicidade')->first();
    expect($cliente)->not->toBeNull();
    // Cliente existe e não depende de periodicidade
    expect($cliente->contratos)->toBeEmpty();
});

test('2. criacao de contrato vinculado a um cliente com itens e valores', function () {
    $cliente = Cliente::create(['nome' => 'Empresa Contratante', 'cidade' => 'Campinas', 'estado' => 'SP']);

    $response = $this->actingAs($this->admin)->post(route('contratos.store'), [
        'numero' => 'CTR-2026-0001',
        'cliente_id' => $cliente->id,
        'titulo' => 'Contrato Anual de Dedetização',
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'dia_preferencial' => 10,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'condicoes_pagamento' => 'Boleto 15 dias',
        'desconto' => '50,00',
        'itens' => [
            [
                'descricao' => 'Desinsetização Mensal',
                'tipo_servico' => 'controle_pragas',
                'quantidade' => 1,
                'valor_unitario' => '400,00',
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $contrato = Contrato::where('numero', 'CTR-2026-0001')->first();
    expect($contrato)->not->toBeNull();
    expect($contrato->cliente_id)->toBe($cliente->id);
    expect((float) $contrato->valor_total)->toBe(350.00); // 400 - 50 = 350
    expect($contrato->itens)->toHaveCount(1);
    expect($contrato->estaAtivo())->toBeTrue();
    // Na Opção A: Ao criar como ativo, não cria visitas vazias estáticas sem técnico no banco antecipadamente
    expect($contrato->visitas)->toBeEmpty();

    // Agenda Geral projeta dinamicamente o evento para o mês
    $agendaResponse = $this->actingAs($this->admin)->get(route('agendamentos.index', ['mes' => now()->format('Y-m')]));
    $agendaResponse->assertOk();
    $agendaResponse->assertSee('Atendimento Contratual: Empresa Contratante');
});

test('3. multiplos contratos independentes para o mesmo cliente', function () {
    $cliente = Cliente::create(['nome' => 'Shopping Center Plaza', 'cidade' => 'Campinas', 'estado' => 'SP']);

    $c1 = Contrato::create([
        'numero' => 'CTR-2026-0010',
        'cliente_id' => $cliente->id,
        'titulo' => 'Controle de Pragas Geral',
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => '2026-01-01',
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'dia_preferencial' => 5,
        'valor_total' => 600.00,
    ]);

    $c2 = Contrato::create([
        'numero' => 'CTR-2026-0011',
        'cliente_id' => $cliente->id,
        'titulo' => 'Higienização de Reservatórios Semestral',
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_DETERMINADO,
        'data_inicio' => '2026-01-01',
        'data_fim' => '2026-12-31',
        'periodicidade' => Contrato::PERIODICIDADE_SEMESTRAL,
        'dia_preferencial' => 15,
        'valor_total' => 1200.00,
    ]);

    $cliente->refresh();
    expect($cliente->contratos)->toHaveCount(2);
    expect($cliente->contratos->pluck('numero')->all())->toEqualCanonicalizing(['CTR-2026-0010', 'CTR-2026-0011']);
});

test('4. contrato com prazo determinado respeita data de termino', function () {
    $cliente = Cliente::create(['nome' => 'Cliente Determinado', 'cidade' => 'SP', 'estado' => 'SP']);
    $service = app(ContratoAgendamentoService::class);

    $contrato = Contrato::create([
        'numero' => 'CTR-2026-0020',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_DETERMINADO,
        'data_inicio' => '2026-01-01',
        'data_fim' => '2026-03-31', // Apenas Jan, Fev e Mar
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'dia_preferencial' => 15,
        'valor_total' => 300.00,
    ]);

    $service->gerarVisitas($contrato, Carbon::parse('2026-12-31'));

    $visitas = $contrato->visitas()->orderBy('data_hora')->get();
    // Deve gerar exatamente 3 visitas: 15/01, 15/02, 15/03 (nenhuma após 31/03)
    expect($visitas)->toHaveCount(3);
    expect($visitas->last()->data_hora->format('Y-m-d'))->toBe('2026-03-15');
});

test('5. contrato com prazo indeterminado suporta horizonte progressivo', function () {
    $cliente = Cliente::create(['nome' => 'Cliente Indeterminado', 'cidade' => 'SP', 'estado' => 'SP']);
    $service = app(ContratoAgendamentoService::class);

    $contrato = Contrato::create([
        'numero' => 'CTR-2026-0030',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => '2026-01-01',
        'data_fim' => null,
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'dia_preferencial' => 10,
        'valor_total' => 250.00,
    ]);

    // Abastece primeiros 90 dias a partir de 01/01
    $service->gerarVisitas($contrato, Carbon::parse('2026-01-01')->addDays(90));
    // 10/01, 10/02, 10/03 = 3 visitas
    expect($contrato->visitas()->count())->toBe(3);

    // Posteriormente, abastece mais 90 dias (até 180 dias)
    $service->gerarVisitas($contrato, Carbon::parse('2026-01-01')->addDays(180));
    // Deve conter Jan, Fev, Mar, Abr, Mai, Jun = 6 visitas
    expect($contrato->visitas()->count())->toBe(6);
});

test('6. geracao automatica de visitas semanais, quinzenais e mensais', function () {
    $cliente = Cliente::create(['nome' => 'Rede de Restaurantes', 'cidade' => 'SP', 'estado' => 'SP']);
    $service = app(ContratoAgendamentoService::class);

    // Contrato Semanal: Toda segunda-feira (dia_semana = 1) no mês de Janeiro 2026
    $semanal = Contrato::create([
        'numero' => 'CTR-SEMANAL',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_DETERMINADO,
        'data_inicio' => '2026-01-01',
        'data_fim' => '2026-01-31',
        'periodicidade' => Contrato::PERIODICIDADE_SEMANAL,
        'dia_semana' => 1, // Segunda-feira
    ]);
    $service->gerarVisitas($semanal);
    // Em Janeiro 2026, segundas caem em: 05/01, 12/01, 19/01, 26/01 -> 4 visitas
    expect($semanal->visitas()->count())->toBe(4);

    // Contrato Quinzenal: A cada 14 dias
    $quinzenal = Contrato::create([
        'numero' => 'CTR-QUINZENAL',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_DETERMINADO,
        'data_inicio' => '2026-01-01',
        'data_fim' => '2026-01-31',
        'periodicidade' => Contrato::PERIODICIDADE_QUINZENAL,
    ]);
    $service->gerarVisitas($quinzenal);
    // 01/01, 15/01, 29/01 -> 3 visitas
    expect($quinzenal->visitas()->count())->toBe(3);
});

test('7. tratamento de meses com quantidades diferentes de dias (dia 31 em fevereiro e abril)', function () {
    $cliente = Cliente::create(['nome' => 'Hospital Central', 'cidade' => 'SP', 'estado' => 'SP']);
    $service = app(ContratoAgendamentoService::class);

    // Contrato mensal configurado para o DIA 31
    $contrato = Contrato::create([
        'numero' => 'CTR-DIA31',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_DETERMINADO,
        'data_inicio' => '2026-01-01',
        'data_fim' => '2026-04-30', // Jan, Fev, Mar, Abr
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'dia_preferencial' => 31,
    ]);

    $service->gerarVisitas($contrato);

    $visitas = $contrato->visitas()->orderBy('data_hora')->get();
    expect($visitas)->toHaveCount(4);

    // Janeiro tem 31 dias -> 31/01
    expect($visitas[0]->data_hora->format('Y-m-d'))->toBe('2026-01-31');

    // Fevereiro tem 28 dias em 2026 -> ajustado automaticamente para 28/02
    expect($visitas[1]->data_hora->format('Y-m-d'))->toBe('2026-02-28');

    // Março tem 31 dias -> 31/03
    expect($visitas[2]->data_hora->format('Y-m-d'))->toBe('2026-03-31');

    // Abril tem 30 dias -> ajustado automaticamente para 30/04
    expect($visitas[3]->data_hora->format('Y-m-d'))->toBe('2026-04-30');
});

test('8. prevencao de visitas duplicadas e garantia de idempotencia', function () {
    $cliente = Cliente::create(['nome' => 'Cliente Idempotente', 'cidade' => 'SP', 'estado' => 'SP']);
    $service = app(ContratoAgendamentoService::class);

    $contrato = Contrato::create([
        'numero' => 'CTR-IDEMPOTENTE',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_DETERMINADO,
        'data_inicio' => '2026-01-01',
        'data_fim' => '2026-03-31',
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'dia_preferencial' => 15,
    ]);

    // Execução 1: cria 3 visitas
    $criadas1 = $service->gerarVisitas($contrato);
    expect($criadas1)->toBe(3);
    expect($contrato->visitas()->count())->toBe(3);

    // Execução 2 repetida no mesmo intervalo: não duplica nenhuma visita
    $criadas2 = $service->gerarVisitas($contrato);
    expect($criadas2)->toBe(0);
    expect($contrato->visitas()->count())->toBe(3);
});

test('9. geracao progressiva de visitas via comando artisan', function () {
    $cliente = Cliente::create(['nome' => 'Cliente Artisan', 'cidade' => 'SP', 'estado' => 'SP']);

    $contrato = Contrato::create([
        'numero' => 'CTR-ARTISAN',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'dia_preferencial' => now()->day,
    ]);

    // Executa comando artisan com 60 dias
    $this->artisan('contratos:gerar-visitas', ['--dias' => 60])
        ->assertSuccessful();

    expect($contrato->visitas()->count())->toBeGreaterThanOrEqual(1);
});

test('10. suspensao e reativacao de contratos com cancelamento de futuras', function () {
    $cliente = Cliente::create(['nome' => 'Cliente Suspensão', 'cidade' => 'SP', 'estado' => 'SP']);
    $service = app(ContratoAgendamentoService::class);

    $contrato = Contrato::create([
        'numero' => 'CTR-SUSPENSAO',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'dia_preferencial' => now()->day,
    ]);

    $service->gerarVisitas($contrato, now()->addDays(90));
    $totalInicial = $contrato->visitas()->where('status', Visita::STATUS_AGENDADA)->count();
    expect($totalInicial)->toBeGreaterThan(0);

    // 1. Suspender Contrato via endpoint
    $resSusp = $this->actingAs($this->admin)->post(route('contratos.suspender', $contrato));
    $resSusp->assertRedirect();

    $contrato->refresh();
    expect($contrato->status)->toBe(Contrato::STATUS_SUSPENSO);
    // Visitas futuras foram marcadas como canceladas
    expect($contrato->visitas()->where('status', Visita::STATUS_CANCELADA)->count())->toBe($totalInicial);

    // 2. Reativar Contrato via endpoint
    $resAtiv = $this->actingAs($this->admin)->post(route('contratos.ativar', $contrato));
    $resAtiv->assertRedirect();

    $contrato->refresh();
    expect($contrato->status)->toBe(Contrato::STATUS_ATIVO);
    // Na Opção A, o contrato ativo projeta os atendimentos previstos na Agenda Geral
    $agenda = $this->actingAs($this->admin)->get(route('agendamentos.index', ['mes' => now()->format('Y-m')]));
    $agenda->assertOk();
    $agenda->assertSee('Atendimento Contratual: Cliente Suspensão');
});

test('11. alteracao de periodicidade recalcula visitas futuras preservando historico', function () {
    $cliente = Cliente::create(['nome' => 'Cliente Mudança de Frequência', 'cidade' => 'SP', 'estado' => 'SP']);
    $service = app(ContratoAgendamentoService::class);

    $contrato = Contrato::create([
        'numero' => 'CTR-MUDA-FREQ',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->subMonths(2)->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'dia_preferencial' => 10,
        'valor_total' => 200.00,
    ]);

    // Visita do passado já concluída
    $visitaPassada = Visita::create([
        'cliente_id' => $cliente->id,
        'contrato_id' => $contrato->id,
        'data_hora' => now()->subMonth(),
        'status' => Visita::STATUS_CONCLUIDA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => 'controle_pragas',
    ]);

    // Gera visitas mensais
    $service->gerarVisitas($contrato, now()->addDays(60));

    // Altera periodicidade para quinzenal
    $contrato->periodicidade = Contrato::PERIODICIDADE_QUINZENAL;
    $contrato->save();
    $service->sincronizarAgendamentosFuturos($contrato);

    // A visita passada CONCLUÍDA deve permanecer intacta
    expect(Visita::find($visitaPassada->id))->not->toBeNull();
    expect(Visita::find($visitaPassada->id)->status)->toBe(Visita::STATUS_CONCLUIDA);

    // Visitas agendadas futuras foram canceladas/limpas, não gerando agendamentos automáticos
    $visitasFuturas = $contrato->visitas()->where('data_hora', '>=', now()->startOfDay())->where('status', Visita::STATUS_AGENDADA)->get();
    expect($visitasFuturas)->toBeEmpty();
});

test('12. encerramento e cancelamento de contratos', function () {
    $cliente = Cliente::create(['nome' => 'Cliente Cancelamento', 'cidade' => 'SP', 'estado' => 'SP']);
    $service = app(ContratoAgendamentoService::class);

    $contrato = Contrato::create([
        'numero' => 'CTR-CANCELAR',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
    ]);
    $service->gerarVisitas($contrato, now()->addDays(60));

    // Cancela o contrato
    $this->actingAs($this->admin)->post(route('contratos.cancelar', $contrato))->assertRedirect();

    $contrato->refresh();
    expect($contrato->status)->toBe(Contrato::STATUS_CANCELADO);
    // Nenhuma visita agendada restante
    expect($contrato->visitas()->where('status', Visita::STATUS_AGENDADA)->count())->toBe(0);
});

test('13. preservacao de visitas concluidas e ordens de servico executadas', function () {
    $cliente = Cliente::create(['nome' => 'Cliente Histórico Preservado', 'cidade' => 'SP', 'estado' => 'SP']);

    $contrato = Contrato::create([
        'numero' => 'CTR-HISTORICO',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->subMonths(3)->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
    ]);

    $visitaExecutada = Visita::create([
        'cliente_id' => $cliente->id,
        'contrato_id' => $contrato->id,
        'tecnico_id' => $this->tecnico->id,
        'data_hora' => now()->subMonth(),
        'status' => Visita::STATUS_CONCLUIDA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 450.00,
    ]);

    // Tentativa de exclusão física deve ser bloqueada
    $response = $this->actingAs($this->admin)->delete(route('contratos.destroy', $contrato));
    $response->assertRedirect();
    $response->assertSessionHas('erro');

    expect(Contrato::find($contrato->id))->not->toBeNull();
    expect(Visita::find($visitaExecutada->id))->not->toBeNull();
    expect(Visita::find($visitaExecutada->id)->status)->toBe(Visita::STATUS_CONCLUIDA);
});

test('14. criacao e execucao de ordem de servico a partir de visita contratual', function () {
    $cliente = Cliente::create(['nome' => 'Cliente Execução OS', 'cidade' => 'SP', 'estado' => 'SP']);

    $contrato = Contrato::create([
        'numero' => 'CTR-EXEC-OS',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'valor_total' => 500.00,
    ]);

    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'contrato_id' => $contrato->id,
        'tecnico_id' => $this->tecnico->id,
        'data_hora' => now(),
        'status' => Visita::STATUS_AGENDADA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 500.00,
    ]);

    // Técnico visualiza folha de OS da visita contratual
    $responseOs = $this->actingAs($this->tecnicoUser)->get(route('visitas.os', $visita));
    $responseOs->assertOk();
    $responseOs->assertSee('CTR-EXEC-OS');

    // Técnico finaliza a visita
    $responseFin = $this->actingAs($this->tecnicoUser)->post(route('visitas.finalizar', $visita), [
        'observacoes' => 'Serviço executado com sucesso conforme contrato.',
    ]);
    $responseFin->assertRedirect();

    $visita->refresh();
    expect($visita->status)->toBe(Visita::STATUS_CONCLUIDA);
    expect($visita->contrato_id)->toBe($contrato->id);
});

test('15. autorizacao de acesso aos contratos e registros relacionados', function () {
    $cliente = Cliente::create(['nome' => 'Cliente Autorização', 'cidade' => 'SP', 'estado' => 'SP']);
    $contrato = Contrato::create([
        'numero' => 'CTR-AUTH-01',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
    ]);

    // Admin acessa listagem e detalhe de contratos
    $this->actingAs($this->admin)->get(route('contratos.index'))->assertOk();
    $this->actingAs($this->admin)->get(route('contratos.show', $contrato))->assertOk();

    // Técnico de campo não possui permissão de gestão de contratos (contratos.ver)
    $this->actingAs($this->tecnicoUser)->get(route('contratos.index'))->assertForbidden();
});

test('16. compatibilidade com atendimentos e ordens de servico avulsos existentes', function () {
    $cliente = Cliente::create(['nome' => 'Cliente Atendimento Avulso', 'cidade' => 'Santos', 'estado' => 'SP']);

    // Visita avulsa sem contrato_id
    $visitaAvulsa = Visita::create([
        'cliente_id' => $cliente->id,
        'contrato_id' => null,
        'tecnico_id' => $this->tecnico->id,
        'data_hora' => now()->addDay(),
        'status' => Visita::STATUS_AGENDADA,
        'tipo_visita' => Visita::TIPO_VISITA_COMERCIAL,
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 150.00,
    ]);

    expect($visitaAvulsa->contrato)->toBeNull();

    // Acessa detalhes e OS da visita avulsa normalmente
    $this->actingAs($this->admin)->get(route('visitas.show', $visitaAvulsa))->assertOk();
    $this->actingAs($this->admin)->get(route('visitas.os', $visitaAvulsa))->assertOk();
});

test('17. selecao e persistencia de meio de pagamento no contrato', function () {
    $this->seed(MeioPagamentoSeeder::class);

    $meioBoleto = MeioPagamento::where('codigo', 'boleto')->first();
    expect($meioBoleto)->not->toBeNull();

    $cliente = Cliente::create(['nome' => 'Cliente Meio Pagamento', 'cidade' => 'Jundiaí', 'estado' => 'SP']);

    // Tela de criação carrega os meios de pagamento
    $createView = $this->actingAs($this->admin)->get(route('contratos.create'));
    $createView->assertOk();
    $createView->assertSee('Boleto Bancário');
    $createView->assertSee('PIX');

    // Criação do contrato associando o meio de pagamento
    $response = $this->actingAs($this->admin)->post(route('contratos.store'), [
        'numero' => 'CTR-PAY-001',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'meio_pagamento_id' => $meioBoleto->id,
        'condicoes_pagamento' => 'Boleto bancário 15 dias',
        'itens' => [
            [
                'descricao' => 'Controle de Pragas',
                'quantidade' => 1,
                'valor_unitario' => '350,00',
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $contrato = Contrato::where('numero', 'CTR-PAY-001')->first();
    expect($contrato)->not->toBeNull();
    expect($contrato->meio_pagamento_id)->toBe($meioBoleto->id);
    expect($contrato->meioPagamento)->not->toBeNull();
    expect($contrato->meioPagamento->nome)->toBe('Boleto Bancário');

    // Visualização exibe o meio de pagamento
    $showView = $this->actingAs($this->admin)->get(route('contratos.show', $contrato));
    $showView->assertOk();
    $showView->assertSee('Boleto Bancário');

    // Atualização com alteração do meio de pagamento para PIX
    $meioPix = MeioPagamento::where('codigo', 'pix')->first();
    $updateResponse = $this->actingAs($this->admin)->put(route('contratos.update', $contrato), [
        'numero' => 'CTR-PAY-001',
        'cliente_id' => $cliente->id,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'meio_pagamento_id' => $meioPix->id,
        'condicoes_pagamento' => 'PIX no vencimento',
    ]);

    $updateResponse->assertSessionHasNoErrors();
    $contrato->refresh();
    expect($contrato->meio_pagamento_id)->toBe($meioPix->id);
    expect($contrato->meioPagamento->codigo)->toBe('pix');
});

test('18. fluxo opcao a: criacao de contrato ativo nao gera OS automaticamente e emissao manual de ordem de servico com tecnico', function () {
    $cliente = Cliente::create(['nome' => 'Supermercado Central', 'cidade' => 'Jundiaí', 'estado' => 'SP']);

    // Cria contrato ativo
    $contrato = Contrato::create([
        'numero' => 'CTR-OPCAO-A',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->startOfMonth()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'dia_preferencial' => 12,
        'tipo_servico' => 'controle_pragas',
        'valor_total' => 500.00,
    ]);

    // Não existem visitas reais estáticas no banco
    expect($contrato->visitas)->toBeEmpty();

    // Na Agenda Geral, o atendimento aparece projetado
    $agendaView = $this->actingAs($this->admin)->get(route('agendamentos.index', ['mes' => now()->format('Y-m')]));
    $agendaView->assertOk();
    $agendaView->assertSee('Atendimento Contratual: Supermercado Central');
    $agendaView->assertSee('Previsto (Contrato)');

    // Técnico não visualiza eventos não atribuídos
    $agendaTecnico = $this->actingAs($this->tecnicoUser)->get(route('agendamentos.index', ['mes' => now()->format('Y-m')]));
    $agendaTecnico->assertOk();
    $agendaTecnico->assertDontSee('Atendimento Contratual: Supermercado Central');

    // Gestor clica no evento e emite a Ordem de Serviço MANUALMENTE com técnico atribuído
    $dataAtendimento = now()->startOfMonth()->day(12)->setTime(14, 0);
    $responseOs = $this->actingAs($this->admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'contrato_id' => $contrato->id,
        'tecnico_id' => $this->tecnico->id,
        'data_hora' => $dataAtendimento->format('Y-m-d H:i:s'),
        'status' => Visita::STATUS_AGENDADA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => '500,00',
    ]);

    $responseOs->assertSessionHasNoErrors();
    $responseOs->assertRedirect();

    // Agora existe 1 visita real vinculada ao contrato com técnico atribuído
    $contrato->refresh();
    expect($contrato->visitas)->toHaveCount(1);
    $visitaCriada = $contrato->visitas->first();
    expect($visitaCriada->tecnico_id)->toBe($this->tecnico->id);

    // Na Agenda Geral, o evento agora é a OS real com técnico
    $agendaAtualizada = $this->actingAs($this->admin)->get(route('agendamentos.index', ['mes' => now()->format('Y-m')]));
    $agendaAtualizada->assertOk();
    $agendaAtualizada->assertSee("OS #{$visitaCriada->id} — Supermercado Central");
    $agendaAtualizada->assertSee($this->tecnico->nome);

    // E o técnico agora enxerga a OS em sua agenda de campo
    $agendaTecnicoAtualizada = $this->actingAs($this->tecnicoUser)->get(route('agendamentos.index', ['mes' => now()->format('Y-m')]));
    $agendaTecnicoAtualizada->assertOk();
    $agendaTecnicoAtualizada->assertSee("OS #{$visitaCriada->id} — Supermercado Central");
});

test('19. criacao e exibicao de ordem de servico vinculada a contrato de mensalidade fixa como inclusa no contrato', function () {
    $cliente = Cliente::create(['nome' => 'Restaurante Sabor Nobre', 'cidade' => 'Campinas', 'estado' => 'SP']);

    $contrato = Contrato::create([
        'numero' => 'CTR-MENSAL-001',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->startOfMonth()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'dia_preferencial' => 10,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'valor_total' => 600.00,
    ]);

    // Tela de criação exibe aviso de atendimento incluso no contrato, campo readonly e valor cobrado sugerido 0.00
    $createView = $this->actingAs($this->admin)->get(route('visitas.create', ['contrato_id' => $contrato->id]));
    $createView->assertOk();
    $createView->assertSee('Incluso no Contrato');
    $createView->assertSee('Atendimento coberto pela mensalidade fixa do contrato');
    $createView->assertSee('readonly');

    // Cria a OS mantendo valor cobrado 0,00 (incluso no plano)
    $response = $this->actingAs($this->admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'contrato_id' => $contrato->id,
        'tecnico_id' => $this->tecnico->id,
        'data_hora' => now()->format('Y-m-d 10:00:00'),
        'status' => Visita::STATUS_CONCLUIDA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => '0,00',
    ]);

    $response->assertSessionHasNoErrors();
    $visita = Visita::where('contrato_id', $contrato->id)->first();
    expect($visita)->not->toBeNull();
    expect((float) $visita->valor_cobrado)->toBe(0.0);

    // Não gera faturamento avulso duplicado no fluxo de caixa
    $receitasOs = $visita->movimentacoes()->where('tipo', MovimentacaoFinanceira::TIPO_RECEITA)->count();
    expect($receitasOs)->toBe(0);

    // A visualização exibe badge de 'Incluso no Contrato'
    $showView = $this->actingAs($this->admin)->get(route('visitas.show', $visita));
    $showView->assertOk();
    $showView->assertSee('Incluso no Contrato');
    $showView->assertSee('Mensalidade Fixa: R$ 600,00');

    // A listagem exibe 'Incluso no Contrato' em vez de R$ 0,00
    $listView = $this->actingAs($this->admin)->get(route('visitas.index'));
    $listView->assertOk();
    $listView->assertSee('Incluso no Contrato');
});

test('20. contrato com preco fechado centraliza tributacao e custos operacionais sem falso prejuizo na os', function () {
    $cliente = Cliente::create(['nome' => 'Indústria Metalúrgica Vale', 'cidade' => 'Jundiaí', 'estado' => 'SP']);

    $contrato = Contrato::create([
        'numero' => 'CTR-FECHADO-001',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->startOfMonth()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'dia_preferencial' => 15,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'valor_total' => 1000.00,
        'exige_nota_fiscal' => true,
        'percentual_imposto' => 14.00,
        'custo_diaria_padrao' => 60.00,
        'custo_deslocamento_padrao' => 25.00,
    ]);

    expect($contrato->valorImposto())->toBe(140.00);
    expect($contrato->valorLiquidoDisponivel())->toBe(860.00);
    expect($contrato->visitasPrevistasPorCiclo())->toBe(1);
    expect($contrato->custoOperacionalPrevisto())->toBe(85.00);
    expect($contrato->lucroLiquidoPrevisto())->toBe(775.00);
    expect($contrato->margemLiquidaPrevista())->toBe(77.5);

    // Contrato com periodicidade quinzenal multiplica os custos operacionais por 2 visitas
    $contratoQuinzenal = Contrato::create([
        'numero' => 'CTR-QUINZENAL-001',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->startOfMonth()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_QUINZENAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'valor_total' => 400.00,
        'exige_nota_fiscal' => true,
        'percentual_imposto' => 14.00,
        'custo_diaria_padrao' => 50.00,
        'custo_deslocamento_padrao' => 0.00,
    ]);

    expect($contratoQuinzenal->visitasPrevistasPorCiclo())->toBe(2);
    expect($contratoQuinzenal->valorImposto())->toBe(56.00);
    expect($contratoQuinzenal->custoOperacionalPrevisto())->toBe(100.00);
    expect($contratoQuinzenal->lucroLiquidoPrevisto())->toBe(244.00);
    expect($contratoQuinzenal->margemLiquidaPrevista())->toBe(61.0);

    // Ao abrir a tela de criação da OS para este contrato, exibe tributação centralizada e preenche custos do contrato
    $createView = $this->actingAs($this->admin)->get(route('visitas.create', ['contrato_id' => $contrato->id]));
    $createView->assertOk();
    $createView->assertSee('Tributação Centralizada no Contrato');
    $createView->assertSee('Retido no Contrato');

    // Cria a OS do contrato
    $response = $this->actingAs($this->admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'contrato_id' => $contrato->id,
        'tecnico_id' => $this->tecnico->id,
        'data_hora' => now()->format('Y-m-d 14:00:00'),
        'status' => Visita::STATUS_CONCLUIDA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => 'controle_pragas',
        'custo_mao_de_obra' => '60,00',
        'custo_deslocamento' => '25,00',
        'gastos_adicionais' => '15,00',
        'motivo_gastos_adicionais' => 'Pedágio e estacionamento',
    ]);

    $response->assertSessionHasNoErrors();
    $visita = Visita::where('contrato_id', $contrato->id)->first();
    expect($visita)->not->toBeNull();
    expect((float) $visita->valor_cobrado)->toBe(0.0);
    expect((float) $visita->valor_imposto)->toBe(0.0);
    expect((float) $visita->lucro_bruto)->toBe(0.0);
    expect($visita->custoOperacionalTotal())->toBe(100.00);

    // Diária e gastos extras geram despesas no caixa para pagar/reembolsar o técnico
    expect($visita->movimentacoes()->where('tipo', MovimentacaoFinanceira::TIPO_DESPESA)->count())->toBe(2);

    // No DRE, o faturamento do contrato é apurado e deduz custos e impostos
    $dreView = $this->actingAs($this->admin)->get(route('financeiro.relatorio', ['mes' => now()->format('Y-m')]));
    $dreView->assertOk();
});

test('16. criacao de contrato como proposta comercial sem gerar eventos na agenda', function () {
    $cliente = Cliente::create(['nome' => 'Padaria Pão Quente', 'cidade' => 'Campinas', 'estado' => 'SP']);

    $response = $this->actingAs($this->admin)->post(route('contratos.store'), [
        'numero' => 'PROP-2026-0099',
        'cliente_id' => $cliente->id,
        'titulo' => 'Proposta de Controle Trimestral de Pragas',
        'status' => Contrato::STATUS_PROPOSTA,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'dia_preferencial' => 15,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'condicoes_pagamento' => 'Boleto 30 dias',
        'desconto' => '0,00',
        'itens' => [
            [
                'descricao' => 'Desinsetização e Desratização Mensal',
                'tipo_servico' => 'controle_pragas',
                'quantidade' => 1,
                'valor_unitario' => '500,00',
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $contrato = Contrato::where('numero', 'PROP-2026-0099')->first();
    expect($contrato)->not->toBeNull();
    expect($contrato->ehProposta())->toBeTrue();
    expect($contrato->status)->toBe(Contrato::STATUS_PROPOSTA);

    // Agenda Geral NÃO projeta atendimentos para propostas não aprovadas
    $agendaResponse = $this->actingAs($this->admin)->get(route('agendamentos.index', ['mes' => now()->format('Y-m')]));
    $agendaResponse->assertOk();
    $agendaResponse->assertDontSee('Padaria Pão Quente');
});

test('17. aprovacao de proposta comercial e transformacao em contrato ativo', function () {
    $cliente = Cliente::create(['nome' => 'Clube Recreativo Alvorada', 'cidade' => 'Campinas', 'estado' => 'SP']);

    $proposta = Contrato::create([
        'numero' => 'PROP-2026-0100',
        'cliente_id' => $cliente->id,
        'titulo' => 'Proposta Anual',
        'status' => Contrato::STATUS_PROPOSTA,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'dia_preferencial' => 10,
        'valor_total' => 800.00,
    ]);

    expect($proposta->podeAtivar())->toBeTrue();

    // Ativa / Aprova a proposta
    $response = $this->actingAs($this->admin)->post(route('contratos.ativar', $proposta));
    $response->assertRedirect(route('contratos.show', $proposta));
    $response->assertSessionHas('sucesso');

    $proposta->refresh();
    expect($proposta->status)->toBe(Contrato::STATUS_ATIVO);
    expect($proposta->estaAtivo())->toBeTrue();
    expect($proposta->visitas()->count())->toBe(0); // Zero O.S. automáticas no banco

    // Agora a agenda passa a projetar os atendimentos para emissão manual da OS
    $agendaResponse = $this->actingAs($this->admin)->get(route('agendamentos.index', ['mes' => now()->format('Y-m')]));
    $agendaResponse->assertOk();
    $agendaResponse->assertSee('Atendimento Contratual: Clube Recreativo Alvorada');
});

test('18. emissao de pdf de contrato e proposta mantendo sigilo comercial', function () {
    $cliente = Cliente::create(['nome' => 'Hospital Santa Clara', 'cidade' => 'Campinas', 'estado' => 'SP']);

    $contrato = Contrato::create([
        'numero' => 'CTR-2026-0101',
        'cliente_id' => $cliente->id,
        'titulo' => 'Contrato Hospitalar',
        'status' => Contrato::STATUS_PROPOSTA,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'valor_total' => 1200.00,
        'custo_diaria_padrao' => 120.00,
        'percentual_imposto' => 14.00,
        'exige_nota_fiscal' => true,
        'descricao' => 'Cláusulas adicionais de monitoramento contínuo hospitalar.',
    ]);

    ContratoItem::create([
        'contrato_id' => $contrato->id,
        'descricao' => 'Controle de Pragas Hospitalar',
        'quantidade' => 1,
        'valor_unitario' => 1200.00,
        'valor_total' => 1200.00,
    ]);

    // Visualização da Proposta com PWA Action Bar
    $response = $this->actingAs($this->admin)->get(route('contratos.pdf', $contrato));
    $response->assertOk();
    $response->assertSee('PROPOSTA COMERCIAL');
    $response->assertSee('Hospital Santa Clara');
    $response->assertSee('R$ 1.200,00');
    $response->assertSee('Voltar');
    $response->assertSee('Compartilhar');
    $response->assertSee('Imprimir / PDF');
    $response->assertSee('5. Recomendações Preventivas');
    $response->assertSee('Manter ambientes limpos, higienizados e organizados.');
    $response->assertSee('Comunicar à equipe técnica qualquer sinal de infestação.');
    $response->assertSee('Cláusulas adicionais de monitoramento contínuo hospitalar.');

    // Regra de Sigilo Comercial Absoluto
    $response->assertDontSee('120,00'); // diária interna nunca aparece
    $response->assertDontSee('14,00'); // alíquota de imposto nunca aparece
    $response->assertDontSee('14.00');

    // Download de PDF binário caso solicitado
    $responsePdf = $this->actingAs($this->admin)->get(route('contratos.pdf', [$contrato, 'download' => 1]));
    $responsePdf->assertOk();
    expect($responsePdf->headers->get('content-type'))->toContain('application/pdf');
});

test('19. remocao de links de proposta comercial da listagem e detalhes de OS', function () {
    $cliente = Cliente::create(['nome' => 'Indústria Metalúrgica', 'cidade' => 'Campinas', 'estado' => 'SP']);

    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $this->tecnico->id,
        'data_hora' => now()->format('Y-m-d 09:00:00'),
        'status' => Visita::STATUS_AGENDADA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 450.00,
    ]);

    $indexView = $this->actingAs($this->admin)->get(route('visitas.index'));
    $indexView->assertOk();
    // O botão com rota de proposta de visita não deve mais estar presente na tabela de OSs
    $indexView->assertDontSee(route('visitas.proposta', $visita));

    $showView = $this->actingAs($this->admin)->get(route('visitas.show', $visita));
    $showView->assertOk();
    $showView->assertDontSee(route('visitas.proposta', $visita));
});
