<?php

use App\Models\Cliente;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Pagination\LengthAwarePaginator;

test('administrador acessa rotas financeiras e de configuracao global', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create(['nome' => 'Cliente Relatório']);
    Visita::create([
        'cliente_id' => $cliente->id,
        'data_hora' => now(),
        'status' => 'concluida',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 500,
        'gastos_adicionais' => 35,
        'motivo_gastos_adicionais' => 'Estacionamento e pedágio',
    ]);

    $this->actingAs($admin)
        ->get(route('financeiro.fluxo'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('financeiro.relatorio'))
        ->assertOk()
        ->assertSee('Gastos Adicionais:')
        ->assertSee('Gastos Extras')
        ->assertSee('Estacionamento e pedágio');

    $this->actingAs($admin)
        ->get(route('financeiro.relatorio.pdf'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    $this->actingAs($admin)
        ->get(route('configuracoes.index'))
        ->assertOk();
});

test('tecnico de campo tem acesso bloqueado a rotas financeiras e configuracoes', function () {
    $tecnico = Tecnico::create([
        'nome' => 'João Técnico',
        'ativo' => true,
    ]);

    $tecnicoUser = User::factory()->create([
        'role' => User::ROLE_TECNICO,
        'tecnico_id' => $tecnico->id,
    ]);

    $this->actingAs($tecnicoUser)
        ->get(route('financeiro.fluxo'))
        ->assertForbidden();

    $this->actingAs($tecnicoUser)
        ->get(route('financeiro.relatorio'))
        ->assertForbidden();

    $this->actingAs($tecnicoUser)
        ->get(route('financeiro.relatorio.pdf'))
        ->assertForbidden();

    $this->actingAs($tecnicoUser)
        ->get(route('configuracoes.index'))
        ->assertForbidden();
});

test('tecnico de campo visualiza apenas as suas proprias visitas', function () {
    $tecnicoProprio = Tecnico::create(['nome' => 'Técnico Meu', 'ativo' => true]);
    $tecnicoOutro = Tecnico::create(['nome' => 'Técnico Outro', 'ativo' => true]);

    $tecnicoUser = User::factory()->create([
        'role' => User::ROLE_TECNICO,
        'tecnico_id' => $tecnicoProprio->id,
    ]);

    $cliente = Cliente::create(['nome' => 'Cliente Teste']);

    $minhaVisita = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnicoProprio->id,
        'data_hora' => now(),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'tipo_praga' => 'MinhaPragaVisita',
    ]);

    $outraVisita = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnicoOutro->id,
        'data_hora' => now(),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'tipo_praga' => 'PragaOutroTecnicoNaoPodeVer',
    ]);

    $response = $this->actingAs($tecnicoUser)->get(route('visitas.index'));

    $response->assertOk();
    $response->assertSee('MinhaPragaVisita');
    $response->assertDontSee('PragaOutroTecnicoNaoPodeVer');
});

test('usuario autenticado visualiza tela de perfil com label de role correspondente', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $responseAdmin = $this->actingAs($admin)->get(route('perfil.edit'));
    $responseAdmin->assertOk();
    $responseAdmin->assertSee('Administrador');

    $tecnico = Tecnico::create(['nome' => 'Carlos Técnico', 'ativo' => true]);
    $tecnicoUser = User::factory()->create([
        'role' => User::ROLE_TECNICO,
        'tecnico_id' => $tecnico->id,
    ]);
    $responseTecnico = $this->actingAs($tecnicoUser)->get(route('perfil.edit'));
    $responseTecnico->assertOk();
    $responseTecnico->assertSee('Técnico');
});

test('informacao de lancamento de contas a receber no modal de finalizacao aparece apenas para administradores', function () {
    $tecnico = Tecnico::create(['nome' => 'Pedro Técnico', 'ativo' => true]);
    $tecnicoUser = User::factory()->create([
        'role' => User::ROLE_TECNICO,
        'tecnico_id' => $tecnico->id,
    ]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create(['nome' => 'Supermercado Central']);
    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now(),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 2500.00,
    ]);

    // Admin visualiza o aviso de contas a receber no modal
    $responseAdmin = $this->actingAs($admin)->get(route('visitas.show', $visita));
    $responseAdmin->assertOk();
    $responseAdmin->assertSee('Lançará o contas a receber de');
    $responseAdmin->assertSee('2.500,00');

    // Técnico não visualiza o lançamento financeiro no modal
    $responseTecnico = $this->actingAs($tecnicoUser)->get(route('visitas.show', $visita));
    $responseTecnico->assertOk();
    $responseTecnico->assertDontSee('Lançará o contas a receber de');
    $responseTecnico->assertDontSee('2.500,00');
});

test('ordens de servico agendada e em andamento aparecem com precedencia sobre as outras independente da data', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create(['nome' => 'Cliente Ordenação']);

    // 1. Visita concluída com data mais recente/futura (amanhã)
    $visitaConcluidaFutura = Visita::create([
        'cliente_id' => $cliente->id,
        'data_hora' => now()->addDays(2),
        'status' => 'concluida',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
    ]);

    // 2. Visita agendada com data mais antiga que a concluída (hoje)
    $visitaAgendada = Visita::create([
        'cliente_id' => $cliente->id,
        'data_hora' => now()->addHour(),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
    ]);

    // 3. Visita em andamento com data de ontem
    $visitaEmAndamento = Visita::create([
        'cliente_id' => $cliente->id,
        'data_hora' => now()->subDay(),
        'status' => 'em_andamento',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
    ]);

    // 4. Visita cancelada com data futura
    $visitaCancelada = Visita::create([
        'cliente_id' => $cliente->id,
        'data_hora' => now()->addDays(3),
        'status' => 'cancelada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
    ]);

    $response = $this->actingAs($admin)->get(route('visitas.index'));
    $response->assertOk();

    /** @var LengthAwarePaginator $visitas */
    $visitas = $response->viewData('visitas');
    $ids = $visitas->pluck('id')->toArray();

    // Em andamento (1) e Agendada (2) devem vir antes de Concluída (3) e Cancelada (4)
    expect($ids[0])->toBe($visitaEmAndamento->id);
    expect($ids[1])->toBe($visitaAgendada->id);
    // As concluídas/canceladas vêm depois, ordenadas entre si por data_hora DESC
    expect($ids[2])->toBe($visitaCancelada->id);
    expect($ids[3])->toBe($visitaConcluidaFutura->id);
});
