<?php

use App\Models\Cliente;
use App\Models\Configuracao;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Visita;

test('formulario de criacao de visita exibe o valor da diaria padrao do tecnico', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $tecnico = Tecnico::create([
        'nome' => 'Marcos Engenheiro',
        'valor_diaria_padrao' => 95.00,
        'ativo' => true,
    ]);

    $response = $this->actingAs($admin)->get(route('visitas.create'));

    $response->assertOk();
    $response->assertSee('data-diaria="95.00"', false);
    $response->assertSee('Diária: R$ 95,00');
});

test('salva visita utilizando a diaria padrao do tecnico quando nao informada manualmente', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    Configuracao::set('aliquota_imposto_padrao', '14.0');

    $cliente = Cliente::create([
        'nome' => 'Hospital Regional',
        'cidade' => 'Imperatriz',
        'estado' => 'MA',
    ]);

    $tecnico = Tecnico::create([
        'nome' => 'Lucas Dedetizador',
        'valor_diaria_padrao' => 85.00,
        'ativo' => true,
    ]);

    // Submete cadastro sem preencher custo_mao_de_obra explicitamente
    $response = $this->actingAs($admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->format('Y-m-d\TH:i'),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 500.00,
        'custo_deslocamento' => 30.00,
    ]);

    $response->assertRedirect(route('visitas.index'));

    $visita = Visita::where('cliente_id', $cliente->id)->first();
    expect($visita)->not->toBeNull();
    // Deve assumir a diária padrão cadastrada no técnico (R$ 85,00)
    expect((float) $visita->custo_mao_de_obra)->toBe(85.00);
});
