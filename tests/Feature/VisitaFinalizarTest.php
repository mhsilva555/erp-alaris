<?php

use App\Models\Cliente;
use App\Models\Configuracao;
use App\Models\MovimentacaoFinanceira;
use App\Models\Produto;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Visita;

test('finaliza visita diretamente com observacao e gastos adicionais', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    Configuracao::set('aliquota_imposto_padrao', '14.0');

    $cliente = Cliente::create([
        'nome' => 'Supermercado Central',
        'cidade' => 'Imperatriz',
        'estado' => 'MA',
    ]);

    $tecnico = Tecnico::create([
        'nome' => 'Flávio Veras',
        'valor_diaria_padrao' => 70.00,
        'ativo' => true,
    ]);

    $produto = Produto::create([
        'nome' => 'Gel Baraticida Concentrado',
        'natureza' => 'solido',
        'unidade' => 'Kg',
        'unidade_armazenamento' => 'Kg',
        'unidade_aplicacao' => 'g',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 5.0,
        'preco_custo_unitario' => 200.00,
        'ativo' => true,
    ]);

    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->addHour(),
        'status' => Visita::STATUS_AGENDADA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
        'tipo_praga' => 'Baratas e Roedores',
        'observacoes' => 'Observação prévia de agendamento.',
        'valor_cobrado' => 600.00,
        'custo_mao_de_obra' => 70.00,
        'custo_deslocamento' => 40.00,
        'exige_nota_fiscal' => true,
        'percentual_imposto' => 14.00,
        'valor_imposto' => 84.00,
        'lucro_bruto' => 406.00,
    ]);

    // Vincula 100g de gel: custo proporcional = (100 / 1000) * 200 = R$ 20.00
    $visita->produtos()->attach($produto->id, [
        'quantidade' => 100,
        'custo_proporcional' => 20.00,
    ]);
    $visita->update(['custo_produtos' => 20.00]);

    // Submete a finalização da visita
    $response = $this->actingAs($admin)->post(route('visitas.finalizar', $visita), [
        'observacoes' => 'Serviço executado com sucesso nos ralos e cozinha industrial.',
        'gastos_adicionais' => 25.50,
        'motivo_gastos_adicionais' => 'Estacionamento e conector extra',
    ]);

    $response->assertRedirect(route('visitas.show', $visita));
    $response->assertSessionHas('sucesso');

    $visita->refresh();
    $produto->refresh();

    // 1. Status deve ser 'concluida'
    expect($visita->status)->toBe(Visita::STATUS_CONCLUIDA);

    // 2. Observações anexadas
    expect($visita->observacoes)->toContain('Observação prévia de agendamento.');
    expect($visita->observacoes)->toContain('Serviço executado com sucesso nos ralos e cozinha industrial.');

    // 3. Gastos adicionais salvos
    expect((float) $visita->gastos_adicionais)->toBe(25.50);
    expect($visita->motivo_gastos_adicionais)->toBe('Estacionamento e conector extra');

    // 4. Baixa de estoque do produto: 5.0 - (100 / 1000) = 4.9 Kg
    expect((float) $produto->quantidade_estoque)->toBe(4.90);

    // 5. Custo operacional total: 20 (produtos) + 70 (diaria) + 40 (deslocamento) + 25.50 (gastos adicionais) = 155.50
    expect($visita->custoOperacionalTotal())->toBe(155.50);

    // 6. Lucro bruto: 600 - (155.50 + 84.00) = 360.50
    expect((float) $visita->lucro_bruto)->toBe(360.50);

    // 7. Movimentações financeiras geradas
    $receita = MovimentacaoFinanceira::where('visita_id', $visita->id)
        ->where('tipo', MovimentacaoFinanceira::TIPO_RECEITA)
        ->first();
    expect($receita)->not->toBeNull();
    expect((float) $receita->valor)->toBe(600.00);

    $despesa = MovimentacaoFinanceira::where('visita_id', $visita->id)
        ->where('tipo', MovimentacaoFinanceira::TIPO_DESPESA)
        ->first();
    expect($despesa)->not->toBeNull();
    expect((float) $despesa->valor)->toBe(25.50);
    expect($despesa->descricao)->toContain('Estacionamento e conector extra');
});

test('impede refinalizar uma visita que ja esta concluida', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create(['nome' => 'Padaria Estrela', 'cidade' => 'Imperatriz', 'estado' => 'MA']);
    $tecnico = Tecnico::create(['nome' => 'José Santos', 'ativo' => true]);

    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now(),
        'status' => Visita::STATUS_CONCLUIDA,
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
    ]);

    $response = $this->actingAs($admin)->post(route('visitas.finalizar', $visita), [
        'observacoes' => 'Tentando refinalizar',
    ]);

    $response->assertRedirect(route('visitas.show', $visita));
    $response->assertSessionHas('alerta');
});

test('preserva gastos adicionais registrados na abertura da OS quando modal e enviado com zero ou vazio', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create(['nome' => 'Farmácia Central', 'cidade' => 'Imperatriz', 'estado' => 'MA']);
    $tecnico = Tecnico::create(['nome' => 'Flávio Veras', 'valor_diaria_padrao' => 80.00, 'ativo' => true]);

    // Visita criada com gastos adicionais na abertura
    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->addHour(),
        'status' => Visita::STATUS_AGENDADA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
        'valor_cobrado' => 1500.00,
        'custo_mao_de_obra' => 80.00,
        'custo_deslocamento' => 50.00,
        'gastos_adicionais' => 120.00,
        'motivo_gastos_adicionais' => 'Compra de tela protetora e silicone',
        'lucro_bruto' => 1250.00,
    ]);

    // Submete finalização enviando gastos_adicionais como 0.00 (valor default do modal antigo)
    $response = $this->actingAs($admin)->post(route('visitas.finalizar', $visita), [
        'observacoes' => 'Serviço finalizado sem gastos extras adicionais no momento da conclusão.',
        'gastos_adicionais' => '0.00',
    ]);

    $response->assertRedirect(route('visitas.show', $visita));
    $visita->refresh();

    // Deve preservar os gastos da abertura
    expect($visita->status)->toBe(Visita::STATUS_CONCLUIDA);
    expect((float) $visita->gastos_adicionais)->toBe(120.00);
    expect($visita->motivo_gastos_adicionais)->toBe('Compra de tela protetora e silicone');

    // Movimentação financeira de gastos adicionais deve existir com 120.00
    $movGastos = MovimentacaoFinanceira::where('visita_id', $visita->id)
        ->where('tipo', MovimentacaoFinanceira::TIPO_DESPESA)
        ->where('categoria', 'gastos_adicionais')
        ->first();

    expect($movGastos)->not->toBeNull();
    expect((float) $movGastos->valor)->toBe(120.00);
    expect($movGastos->descricao)->toContain('Compra de tela protetora e silicone');
});

test('aceita gastos adicionais e campos monetarios no padrao brasileiro com virgula', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create(['nome' => 'Padaria Pão Quente', 'cidade' => 'Imperatriz', 'estado' => 'MA']);
    $tecnico = Tecnico::create(['nome' => 'Flávio Veras', 'valor_diaria_padrao' => 80.00, 'ativo' => true]);

    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->addHour(),
        'status' => Visita::STATUS_AGENDADA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
        'valor_cobrado' => 800.00,
        'custo_mao_de_obra' => 80.00,
        'custo_deslocamento' => 0.00,
        'gastos_adicionais' => 0.00,
        'lucro_bruto' => 720.00,
    ]);

    $response = $this->actingAs($admin)->post(route('visitas.finalizar', $visita), [
        'observacoes' => 'Concluído com despesa em reais formatada com vírgula',
        'gastos_adicionais' => '75,50',
        'motivo_gastos_adicionais' => 'Reembolso combustível emergencial',
    ]);

    $response->assertRedirect(route('visitas.show', $visita));
    $visita->refresh();

    expect((float) $visita->gastos_adicionais)->toBe(75.50);
    expect($visita->motivo_gastos_adicionais)->toBe('Reembolso combustível emergencial');

    $movGastos = MovimentacaoFinanceira::where('visita_id', $visita->id)
        ->where('categoria', 'gastos_adicionais')
        ->first();

    expect($movGastos)->not->toBeNull();
    expect((float) $movGastos->valor)->toBe(75.50);
});
