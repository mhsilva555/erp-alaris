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
