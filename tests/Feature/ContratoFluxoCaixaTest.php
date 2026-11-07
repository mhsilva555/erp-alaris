<?php

use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\ContratoItem;
use App\Models\MovimentacaoFinanceira;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Visita;

test('contrato ativo sincroniza receita de mensalidade no fluxo de caixa para o mes de vigencia', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create([
        'nome' => 'Supermercado Central',
        'cidade' => 'Imperatriz',
        'estado' => 'MA',
    ]);

    $contrato = Contrato::create([
        'numero' => 'CTR-2026-0099',
        'cliente_id' => $cliente->id,
        'titulo' => 'Contrato Mensal de Pragas',
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => '2026-11-01',
        'periodicidade' => Contrato::PERIODICIDADE_QUINZENAL,
        'dia_preferencial' => 7,
        'dia_semana' => 1,
        'tipo_servico' => 'controle_pragas',
        'valor_total' => 450.00,
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
    ]);

    ContratoItem::create([
        'contrato_id' => $contrato->id,
        'tipo_servico' => 'controle_pragas',
        'descricao' => 'Controle Integrado de Pragas',
        'quantidade' => 1,
        'valor_unitario' => 450.00,
        'valor_total' => 450.00,
    ]);

    // Visita a página de fluxo de caixa para novembro/2026
    $response = $this->actingAs($admin)->get(route('financeiro.fluxo', ['mes' => '2026-11']));
    $response->assertOk();

    // Verifica que a movimentação de receita foi gerada automaticamente
    $mov = MovimentacaoFinanceira::where('contrato_id', $contrato->id)
        ->where('tipo', MovimentacaoFinanceira::TIPO_RECEITA)
        ->first();

    expect($mov)->not->toBeNull();
    expect((float) $mov->valor)->toBe(450.00);
    expect($mov->categoria)->toBe('mensalidade_contrato');
    expect($mov->status)->toBe(MovimentacaoFinanceira::STATUS_PENDENTE);
    expect($mov->data_competencia->toDateString())->toBe('2026-11-01');
    expect($mov->data_vencimento->toDateString())->toBe('2026-11-07');
    expect($mov->cliente_id)->toBe($cliente->id);

    // Verifica se na view é exibido o link com o Contrato e o cliente
    $response->assertSee('Contrato #CTR-2026-0099');
    $response->assertSee('Supermercado Central');
    $response->assertSee('450,00');
});

test('ordem de servico com preco fechado de contrato deduz despesas sem duplicar faturamento', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create([
        'nome' => 'Restaurante Sabor Nobre',
        'cidade' => 'Imperatriz',
        'estado' => 'MA',
    ]);

    $tecnico = Tecnico::create([
        'nome' => 'José Silveira',
        'valor_diaria_padrao' => 60.00,
        'ativo' => true,
    ]);

    $contrato = Contrato::create([
        'numero' => 'CTR-2026-0100',
        'cliente_id' => $cliente->id,
        'titulo' => 'Contrato Mensal de Higienização',
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => '2026-11-01',
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'dia_preferencial' => 10,
        'tipo_servico' => 'limpeza',
        'valor_total' => 500.00,
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
    ]);

    // Visita vinculada a contrato de preço fechado possui valor_cobrado = 0.00
    $visita = Visita::create([
        'contrato_id' => $contrato->id,
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => '2026-11-05 09:00:00',
        'status' => Visita::STATUS_CONCLUIDA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_LIMPEZA,
        'valor_cobrado' => 0.00,
        'custo_mao_de_obra' => 60.00,
    ]);

    // Sincroniza movimentações da visita (gera a diária a pagar do técnico)
    $this->actingAs($admin)->put(route('visitas.update', $visita), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'contrato_id' => $contrato->id,
        'data_hora' => '2026-11-05 09:00',
        'status' => Visita::STATUS_CONCLUIDA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_LIMPEZA,
        'valor_cobrado' => 0.00,
        'custo_mao_de_obra' => 60.00,
    ]);

    // Acessa o fluxo de caixa
    $response = $this->actingAs($admin)->get(route('financeiro.fluxo', ['mes' => '2026-11']));
    $response->assertOk();

    // Verifica que existe a despesa de diária de R$ 60 e a receita de contrato de R$ 500
    $movDespesa = MovimentacaoFinanceira::where('visita_id', $visita->id)->where('tipo', MovimentacaoFinanceira::TIPO_DESPESA)->first();
    $movReceitaContrato = MovimentacaoFinanceira::where('contrato_id', $contrato->id)->where('tipo', MovimentacaoFinanceira::TIPO_RECEITA)->first();

    expect($movDespesa)->not->toBeNull();
    expect((float) $movDespesa->valor)->toBe(60.00);

    expect($movReceitaContrato)->not->toBeNull();
    expect((float) $movReceitaContrato->valor)->toBe(500.00);

    // O faturamento consolidado DRE no fluxo deve ser 500.00 (não 0.00)
    $response->assertSee('500,00');
    // Saldo projetado: 500.00 - 60.00 = +440.00
    $response->assertSee('440,00');
});

test('liquidacao da receita de contrato recalcula o saldo realizado do caixa', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create([
        'nome' => 'Padaria Estrela',
        'cidade' => 'Imperatriz',
        'estado' => 'MA',
    ]);

    $contrato = Contrato::create([
        'numero' => 'CTR-2026-0101',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => '2026-11-01',
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'valor_total' => 600.00,
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
    ]);

    $contrato->sincronizarMovimentacaoMes('2026-11');

    $mov = MovimentacaoFinanceira::where('contrato_id', $contrato->id)->first();
    expect($mov->status)->toBe(MovimentacaoFinanceira::STATUS_PENDENTE);

    // Liquida a movimentação
    $response = $this->actingAs($admin)->patch(route('financeiro.status', $mov), [
        'status' => MovimentacaoFinanceira::STATUS_PAGO,
    ]);
    $response->assertRedirect();

    $mov->refresh();
    expect($mov->status)->toBe(MovimentacaoFinanceira::STATUS_PAGO);
    expect($mov->data_pagamento)->not->toBeNull();

    // Consulta fluxo de caixa: receitas realizadas agora incluem 600,00
    $responseFluxo = $this->actingAs($admin)->get(route('financeiro.fluxo', ['mes' => '2026-11']));
    $responseFluxo->assertSee('600,00');
});

test('suspensao ou cancelamento do contrato remove receitas pendentes futuras sem expurgar pagas', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create([
        'nome' => 'Hotel Plaza',
        'cidade' => 'Imperatriz',
        'estado' => 'MA',
    ]);

    $contrato = Contrato::create([
        'numero' => 'CTR-2026-0102',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->startOfMonth()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'valor_total' => 800.00,
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
    ]);

    // Cria movimentação paga do mês anterior
    $mesAnterior = now()->subMonth()->format('Y-m');
    $movPaga = MovimentacaoFinanceira::create([
        'contrato_id' => $contrato->id,
        'cliente_id' => $cliente->id,
        'tipo' => MovimentacaoFinanceira::TIPO_RECEITA,
        'categoria' => 'mensalidade_contrato',
        'descricao' => 'Mensalidade Contrato Mês Passado',
        'valor' => 800.00,
        'data_competencia' => now()->subMonth()->startOfMonth()->toDateString(),
        'data_vencimento' => now()->subMonth()->startOfMonth()->day(10)->toDateString(),
        'data_pagamento' => now()->subMonth()->startOfMonth()->day(10)->toDateString(),
        'status' => MovimentacaoFinanceira::STATUS_PAGO,
    ]);

    // Cria movimentação pendente do mês atual
    $contrato->sincronizarMovimentacaoMes(now());
    $movPendente = MovimentacaoFinanceira::where('contrato_id', $contrato->id)
        ->where('status', MovimentacaoFinanceira::STATUS_PENDENTE)
        ->first();
    expect($movPendente)->not->toBeNull();

    // Cancela o contrato
    $this->actingAs($admin)->post(route('contratos.cancelar', $contrato));

    // A movimentação paga permanece intacta
    expect(MovimentacaoFinanceira::find($movPaga->id))->not->toBeNull();

    // A movimentação pendente foi removida
    expect(MovimentacaoFinanceira::find($movPendente->id))->toBeNull();
});
