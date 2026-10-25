<?php

use App\Models\Cliente;
use App\Models\EquipamentoComodato;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Visita;

test('gestor e tecnico podem acessar o modulo de agendamentos com agenda unificada', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $tecnico = Tecnico::create(['nome' => 'Técnico Agendamento', 'ativo' => true]);
    $tecnicoUser = User::factory()->create(['role' => User::ROLE_TECNICO, 'tecnico_id' => $tecnico->id]);

    $cliente = Cliente::create([
        'nome' => 'Empresa Agendada',
        'cidade' => 'São Paulo',
        'estado' => 'SP',
        'data_followup' => now()->toDateString(),
        'status_prospeccao' => Cliente::STATUS_RETORNAR_3_MESES,
    ]);

    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now(),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
    ]);

    $comodato = EquipamentoComodato::create([
        'cliente_id' => $cliente->id,
        'nome_equipamento' => 'Porta Iscas Refil',
        'quantidade' => 1,
        'data_instalacao' => now()->subDays(10),
        'periodicidade_dias' => 15,
        'data_proxima_reposicao' => now()->addDays(2),
    ]);

    // Acesso como Gestor / Admin
    $responseAdmin = $this->actingAs($admin)->get(route('agendamentos.index'));
    $responseAdmin->assertOk();
    $responseAdmin->assertSee('Agenda Geral Unificada');
    $responseAdmin->assertSee('Empresa Agendada');
    $responseAdmin->assertSee('Porta Iscas Refil');
    $responseAdmin->assertSee('Follow-up: Empresa Agendada');

    // Acesso com filtro de categoria
    $this->actingAs($admin)
        ->get(route('agendamentos.index', ['tipo' => 'visitas']))
        ->assertOk();

    // Acesso como Técnico
    $responseTecnico = $this->actingAs($tecnicoUser)->get(route('agendamentos.index'));
    $responseTecnico->assertOk();
    $responseTecnico->assertSee('Empresa Agendada');
});

test('followup cadastrado para o ultimo dia do mes aparece na agenda geral', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create([
        'nome' => 'Cliente Final do Mês',
        'cidade' => 'São Paulo',
        'estado' => 'SP',
        'data_followup' => '2026-10-31',
        'status_prospeccao' => Cliente::STATUS_RETORNAR_3_MESES,
    ]);

    $response = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-10']));
    $response->assertOk();
    $response->assertSee('Follow-up: Cliente Final do Mês');
});

test('gestor pode remover eventos de visita, comodato e followup da agenda', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $tecnico = Tecnico::create(['nome' => 'Técnico Teste', 'ativo' => true]);

    $cliente = Cliente::create([
        'nome' => 'Cliente Remoção',
        'cidade' => 'São Paulo',
        'estado' => 'SP',
        'data_followup' => now()->toDateString(),
    ]);

    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now(),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
    ]);

    $comodato = EquipamentoComodato::create([
        'cliente_id' => $cliente->id,
        'nome_equipamento' => 'Isca Remoção',
        'quantidade' => 1,
        'data_instalacao' => now()->subDays(10),
        'periodicidade_dias' => 15,
        'data_proxima_reposicao' => now()->addDays(2),
    ]);

    // 1. Remove Visita
    $resVisita = $this->actingAs($admin)->delete(route('agendamentos.destroy', ['tipo' => 'visita', 'id' => $visita->id]));
    $resVisita->assertRedirect();
    $resVisita->assertSessionHas('sucesso');
    expect(Visita::find($visita->id))->toBeNull();

    // 2. Remove Comodato (desagenda a reposição da agenda)
    $resComodato = $this->actingAs($admin)->delete(route('agendamentos.destroy', ['tipo' => 'comodato', 'id' => $comodato->id]));
    $resComodato->assertRedirect();
    $resComodato->assertSessionHas('sucesso');
    $comodato->refresh();
    expect($comodato->data_proxima_reposicao)->toBeNull();

    // 3. Remove Follow-up (desagenda o contato mas preserva o cliente)
    $resFollowup = $this->actingAs($admin)->delete(route('agendamentos.destroy', ['tipo' => 'followup', 'id' => $cliente->id]));
    $resFollowup->assertRedirect();
    $resFollowup->assertSessionHas('sucesso');
    $cliente->refresh();
    expect($cliente->data_followup)->toBeNull();
    expect(Cliente::find($cliente->id))->not->toBeNull();
});

test('tecnico de campo so pode remover sua propria visita e nao comodato ou followup', function () {
    $tecnico1 = Tecnico::create(['nome' => 'Técnico 1', 'ativo' => true]);
    $tecnico2 = Tecnico::create(['nome' => 'Técnico 2', 'ativo' => true]);
    $userTecnico1 = User::factory()->create(['role' => User::ROLE_TECNICO, 'tecnico_id' => $tecnico1->id]);

    $cliente = Cliente::create(['nome' => 'Cliente Teste', 'cidade' => 'SP', 'estado' => 'SP', 'data_followup' => now()->toDateString()]);

    $visitaTecnico2 = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico2->id,
        'data_hora' => now(),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
    ]);

    // Técnico 1 tentando remover visita do Técnico 2 -> 403
    $this->actingAs($userTecnico1)
        ->delete(route('agendamentos.destroy', ['tipo' => 'visita', 'id' => $visitaTecnico2->id]))
        ->assertForbidden();

    // Técnico 1 tentando remover follow-up -> 403
    $this->actingAs($userTecnico1)
        ->delete(route('agendamentos.destroy', ['tipo' => 'followup', 'id' => $cliente->id]))
        ->assertForbidden();
});

test('comodato com ciclo de 15 dias projeta multiplas ocorrencias no mesmo mes e nos meses seguintes', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create(['nome' => 'Restaurante Sabor', 'cidade' => 'SP', 'estado' => 'SP']);

    $comodato = EquipamentoComodato::create([
        'cliente_id' => $cliente->id,
        'nome_equipamento' => 'Porta Iscas Cozinha',
        'quantidade' => 4,
        'data_instalacao' => '2026-10-05',
        'periodicidade_dias' => 15,
        'data_proxima_reposicao' => '2026-10-05',
    ]);

    // 1. No mês de Outubro/2026, com ciclo de 15 dias a partir de 05/10:
    // Ocorrência 1: 05/10 (âncora original)
    // Ocorrência 2: 20/10 (previsto recorrente)
    $responseOutubro = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-10']));
    $responseOutubro->assertOk();
    $responseOutubro->assertSee('Reposição: Porta Iscas Cozinha');
    $responseOutubro->assertSee('Previsto (Recorrente)');

    $totaisOutubro = $responseOutubro->viewData('totais');
    expect($totaisOutubro['total_comodatos'])->toBe(2);

    // 2. No mês seguinte (Novembro/2026):
    // 05/10 + 30 dias = 04/11
    // 04/11 + 15 dias = 19/11
    // Ambas são projetadas como Previsto (Recorrente)
    $responseNovembro = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-11']));
    $responseNovembro->assertOk();
    $responseNovembro->assertSee('Reposição: Porta Iscas Cozinha');
    $responseNovembro->assertSee('Reposição de Comodato');
    $responseNovembro->assertSee('Previsto (Recorrente)');

    $totaisNovembro = $responseNovembro->viewData('totais');
    expect($totaisNovembro['total_comodatos'])->toBe(2);
});

test('cliente com contrato mensal projeta visita recorrente na agenda quando nao ha OS agendada', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $tecnico = Tecnico::create(['nome' => 'Técnico Campo', 'ativo' => true]);

    $cliente = Cliente::create([
        'nome' => 'Indústria Alfa',
        'cidade' => 'Campinas',
        'estado' => 'SP',
        'periodicidade' => 'mensal',
        'status_prospeccao' => Cliente::STATUS_ATIVO,
    ]);
    $cliente->created_at = '2026-09-10 10:00:00';
    $cliente->save();

    // No mês de Outubro/2026, sem nenhuma visita física cadastrada no banco:
    $response = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-10']));
    $response->assertOk();
    $response->assertSee('Visita Contratual: Indústria Alfa');
    $response->assertSee('Visita Recorrente (Projetada)');
    $response->assertSee('Previsto (Recorrente)');
    $response->assertSee('Agendar OS');
    $response->assertSee(route('visitas.create', ['cliente_id' => $cliente->id, 'data' => '2026-10-10']));

    $totais = $response->viewData('totais');
    expect($totais['total_visitas'])->toBe(1);

    // Quando criamos uma OS física real para o cliente no mês de Outubro:
    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => '2026-10-14 09:00:00',
        'status' => Visita::STATUS_AGENDADA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
    ]);

    // A agenda agora exibe a OS física e NÃO duplica com uma visita projetada
    $responseAposCriar = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-10']));
    $responseAposCriar->assertOk();
    $responseAposCriar->assertSee("OS #{$visita->id} — Indústria Alfa");
    $responseAposCriar->assertDontSee('Visita Contratual: Indústria Alfa');

    $totaisApos = $responseAposCriar->viewData('totais');
    expect($totaisApos['total_visitas'])->toBe(1);
});

test('cliente com contrato quinzenal projeta dois ciclos no mes e abate conforme OS sao criadas', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $tecnico = Tecnico::create(['nome' => 'Técnico Dedetizador', 'ativo' => true]);

    $cliente = Cliente::create([
        'nome' => 'Padaria Central',
        'cidade' => 'São Paulo',
        'estado' => 'SP',
        'periodicidade' => 'quinzenal',
        'status_prospeccao' => Cliente::STATUS_ATIVO,
    ]);
    $cliente->created_at = '2026-09-05 08:00:00';
    $cliente->save();

    // 1. Sem visitas cadastradas, o mês de Outubro/2026 tem 2 ciclos projetados
    $resInicial = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-10']));
    $resInicial->assertOk();
    $totaisInicial = $resInicial->viewData('totais');
    expect($totaisInicial['total_visitas'])->toBe(2);

    // 2. Criamos a visita física para a 1ª quinzena (dia 05/10)
    Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => '2026-10-05 09:00:00',
        'status' => Visita::STATUS_CONCLUIDA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
    ]);

    // O total de visitas permanece 2 (1 OS real + 1 ciclo projetado para a 2ª quinzena)
    $resUmaVisita = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-10']));
    $resUmaVisita->assertOk();
    $totaisUma = $resUmaVisita->viewData('totais');
    expect($totaisUma['total_visitas'])->toBe(2);
    $resUmaVisita->assertSee('Visita Contratual: Padaria Central');

    // 3. Criamos a visita da 2ª quinzena (dia 20/10)
    Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => '2026-10-20 09:00:00',
        'status' => Visita::STATUS_AGENDADA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
    ]);

    // Com as duas OS criadas, nenhuma visita projetada resta
    $resCompleto = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-10']));
    $resCompleto->assertOk();
    $totaisCompleto = $resCompleto->viewData('totais');
    expect($totaisCompleto['total_visitas'])->toBe(2);
    $resCompleto->assertDontSee('Visita Contratual: Padaria Central');
});

test('cliente cadastrado na segunda quinzena com contrato quinzenal nao projeta evento passado na primeira quinzena', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $tecnico = Tecnico::create(['nome' => 'Técnico Especialista', 'ativo' => true]);

    // Cliente cadastrado em 24/10 com periodicidade quinzenal
    $cliente = Cliente::create([
        'nome' => 'Supermercado Progresso',
        'cidade' => 'São Paulo',
        'estado' => 'SP',
        'periodicidade' => 'quinzenal',
        'status_prospeccao' => Cliente::STATUS_ATIVO,
    ]);
    $cliente->created_at = '2026-10-24 10:00:00';
    $cliente->save();

    // Uma OS criada para o dia de cadastro (24/10)
    Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => '2026-10-24 14:00:00',
        'status' => Visita::STATUS_CONCLUIDA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
    ]);

    // Ao acessar o mês de Outubro/2026:
    // O sistema NÃO deve projetar uma visita na 1ª quinzena (ex.: 15/10), pois ela seria anterior ao cadastro do cliente
    $res = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-10']));
    $res->assertOk();
    $res->assertDontSee('Visita Contratual: Supermercado Progresso');

    $totais = $res->viewData('totais');
    expect($totais['total_visitas'])->toBe(1); // apenas a OS real criada em 24/10
});

test('usuario pode alternar entre visualizacao de lista e calendario', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $tecnico = Tecnico::create(['nome' => 'Técnico Agenda', 'ativo' => true]);

    $cliente = Cliente::create([
        'nome' => 'Cliente Calendário Mode',
        'cidade' => 'São Paulo',
        'estado' => 'SP',
    ]);

    Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => '2026-10-24 10:00:00',
        'status' => Visita::STATUS_AGENDADA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
    ]);

    // Modo Lista (padrão)
    $resLista = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-10', 'modo' => 'lista']));
    $resLista->assertOk();
    expect($resLista->viewData('modo'))->toBe('lista');
    $resLista->assertSee('id="view-lista"', false);
    $resLista->assertSee('id="view-calendario"', false);
    $resLista->assertSee('btn-modo-lista', false);
    $resLista->assertSee('btn-modo-calendario', false);

    // Modo Calendário
    $resCalendario = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-10', 'modo' => 'calendario']));
    $resCalendario->assertOk();
    expect($resCalendario->viewData('modo'))->toBe('calendario');
    expect($resCalendario->viewData('gradeCalendario'))->toBeArray();
    expect(count($resCalendario->viewData('gradeCalendario')))->toBeGreaterThanOrEqual(28);

    // Células da grade e chips do evento devem estar presentes
    $resCalendario->assertSee('data-data-str="2026-10-24"', false);
    $resCalendario->assertSee('Cliente Calendário Mode');
    $resCalendario->assertSee('template-dia-2026-10-24', false);
    $resCalendario->assertSee('modal-dia-eventos', false);
});

test('visita com previsao de retorno preventivo exibe evento na agenda com link de agendamento em 1 clique', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $tecnico = Tecnico::create(['nome' => 'Técnico Especialista', 'ativo' => true]);

    $cliente = Cliente::create([
        'nome' => 'Restaurante Sabor Real',
        'cidade' => 'São Paulo',
        'estado' => 'SP',
    ]);

    // Cria OS principal no dia 10/10 com retorno preventivo marcado para 25/10 às 14:00
    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => '2026-10-10 09:00:00',
        'proxima_visita' => '2026-10-25 14:00:00',
        'status' => Visita::STATUS_CONCLUIDA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
        'tipo_praga' => 'Baratas germânicas',
        'area_tratada' => '120 m² Cozinha',
    ]);

    $res = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-10']));
    $res->assertOk();

    // Deve exibir o retorno preventivo na agenda
    $res->assertSee('Retorno Preventivo: Restaurante Sabor Real');
    $res->assertSee('Ref. OS #'.$visita->id);
    $res->assertSee('Retorno Previsto');
    $res->assertSee('Agendar OS');

    // Total de visitas deve incluir a visita original e o retorno preventivo projetado
    $totais = $res->viewData('totais');
    expect($totais['total_visitas'])->toBe(2);

    // O link de agendamento deve direcionar para a tela de criação com os dados pré-preenchidos
    $createUrl = route('visitas.create', [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'tipo_servico' => $visita->tipo_servico,
        'tipo_praga' => $visita->tipo_praga,
        'area_tratada' => $visita->area_tratada,
        'data' => '2026-10-25T14:00',
        'observacoes' => "Retorno preventivo / reforço referente à OS #{$visita->id}.",
    ]);
    $res->assertSee(e($createUrl), false);

    // Gestor pode remover a previsão de retorno preventivo da agenda
    $resDelete = $this->actingAs($admin)->delete(route('agendamentos.destroy', [
        'tipo' => 'retorno_preventivo',
        'id' => $visita->id,
    ]));
    $resDelete->assertRedirect();

    $visita->refresh();
    expect($visita->proxima_visita)->toBeNull();
    // A OS principal concluída permanece intacta no banco
    expect($visita->status)->toBe(Visita::STATUS_CONCLUIDA);
});

test('administrador nao visualiza ordem de servico vinculada a comodato na agenda geral mas tecnico visualiza', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $tecnico = Tecnico::create(['nome' => 'Flávio Veras', 'ativo' => true]);
    $tecnicoUser = User::factory()->create(['role' => User::ROLE_TECNICO, 'tecnico_id' => $tecnico->id]);

    $cliente = Cliente::create([
        'nome' => 'ATHOMUS LTDA',
        'cidade' => 'São Paulo',
        'estado' => 'SP',
    ]);

    $comodato = EquipamentoComodato::create([
        'cliente_id' => $cliente->id,
        'nome_equipamento' => 'Porta-Iscas Externo',
        'quantidade' => 4,
        'data_instalacao' => '2026-10-01',
        'periodicidade_dias' => 30,
        'data_proxima_reposicao' => '2026-10-25',
    ]);

    // Visita / OS gerada a partir do comodato
    $visitaComodato = Visita::create([
        'cliente_id' => $cliente->id,
        'comodato_id' => $comodato->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => '2026-10-25 09:00:00',
        'status' => Visita::STATUS_AGENDADA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
    ]);

    // Visita avulsa normal (não é de comodato)
    $visitaAvulsa = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => '2026-10-26 14:00:00',
        'status' => Visita::STATUS_AGENDADA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
    ]);

    // 1. Visão do Administrador:
    // O Administrador vê o evento de Reposição de Comodato e a visita avulsa,
    // mas NÃO vê a OS de comodato duplicada como card avulso de visita.
    $resAdmin = $this->actingAs($admin)->get(route('agendamentos.index', ['mes' => '2026-10']));
    $resAdmin->assertOk();
    $resAdmin->assertSee('Reposição: Porta-Iscas Externo');
    $resAdmin->assertSee('OS #'.$visitaAvulsa->id);
    $resAdmin->assertDontSee('OS #'.$visitaComodato->id);

    // 2. Visão do Técnico:
    // O Técnico visualiza a OS do comodato normalmente atribuída a ele para executar em campo.
    $resTecnico = $this->actingAs($tecnicoUser)->get(route('agendamentos.index', ['mes' => '2026-10']));
    $resTecnico->assertOk();
    $resTecnico->assertSee('OS #'.$visitaComodato->id);
    $resTecnico->assertSee('Reposição de Comodato');
    $resTecnico->assertSee('OS #'.$visitaAvulsa->id);
});
