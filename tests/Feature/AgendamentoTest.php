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
