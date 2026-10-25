<?php

use App\Models\MovimentacaoFinanceira;
use App\Models\User;

test('administrador pode liquidar multiplas movimentacoes financeiras em massa', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $mov1 = MovimentacaoFinanceira::create([
        'tipo' => MovimentacaoFinanceira::TIPO_RECEITA,
        'categoria' => 'faturamento_servico',
        'descricao' => 'Serviço A',
        'valor' => 150.00,
        'data_competencia' => now()->toDateString(),
        'status' => MovimentacaoFinanceira::STATUS_PENDENTE,
    ]);

    $mov2 = MovimentacaoFinanceira::create([
        'tipo' => MovimentacaoFinanceira::TIPO_DESPESA,
        'categoria' => 'combustivel',
        'descricao' => 'Abastecimento',
        'valor' => 80.00,
        'data_competencia' => now()->toDateString(),
        'status' => MovimentacaoFinanceira::STATUS_PENDENTE,
    ]);

    $response = $this->actingAs($admin)->post(route('financeiro.acoes-massa'), [
        'acao' => 'liquidar',
        'ids' => [$mov1->id, $mov2->id],
    ]);

    $response->assertSessionHas('sucesso');
    expect($mov1->fresh()->status)->toBe(MovimentacaoFinanceira::STATUS_PAGO);
    expect($mov1->fresh()->data_pagamento)->not->toBeNull();
    expect($mov2->fresh()->status)->toBe(MovimentacaoFinanceira::STATUS_PAGO);
    expect($mov2->fresh()->data_pagamento)->not->toBeNull();
});

test('administrador pode reabrir multiplas movimentacoes financeiras em massa', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $mov1 = MovimentacaoFinanceira::create([
        'tipo' => MovimentacaoFinanceira::TIPO_RECEITA,
        'categoria' => 'faturamento_servico',
        'descricao' => 'Serviço Pago',
        'valor' => 300.00,
        'data_competencia' => now()->toDateString(),
        'data_pagamento' => now()->toDateString(),
        'status' => MovimentacaoFinanceira::STATUS_PAGO,
    ]);

    $response = $this->actingAs($admin)->post(route('financeiro.acoes-massa'), [
        'acao' => 'reabrir',
        'ids' => [$mov1->id],
    ]);

    $response->assertSessionHas('sucesso');
    expect($mov1->fresh()->status)->toBe(MovimentacaoFinanceira::STATUS_PENDENTE);
});

test('administrador pode excluir multiplas movimentacoes financeiras em massa', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $mov1 = MovimentacaoFinanceira::create([
        'tipo' => MovimentacaoFinanceira::TIPO_DESPESA,
        'categoria' => 'material_escritorio',
        'descricao' => 'Papelaria',
        'valor' => 45.00,
        'data_competencia' => now()->toDateString(),
        'status' => MovimentacaoFinanceira::STATUS_PENDENTE,
    ]);

    $mov2 = MovimentacaoFinanceira::create([
        'tipo' => MovimentacaoFinanceira::TIPO_DESPESA,
        'categoria' => 'lanche',
        'descricao' => 'Alimentação',
        'valor' => 25.00,
        'data_competencia' => now()->toDateString(),
        'status' => MovimentacaoFinanceira::STATUS_PENDENTE,
    ]);

    $response = $this->actingAs($admin)->post(route('financeiro.acoes-massa'), [
        'acao' => 'excluir',
        'ids' => [$mov1->id, $mov2->id],
    ]);

    $response->assertSessionHas('sucesso');
    expect(MovimentacaoFinanceira::find($mov1->id))->toBeNull();
    expect(MovimentacaoFinanceira::find($mov2->id))->toBeNull();
});

test('valida campos obrigatorios para acoes em massa', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->post(route('financeiro.acoes-massa'), [
        'acao' => 'invalida',
        'ids' => [],
    ]);

    $response->assertSessionHasErrors(['acao', 'ids']);
});

test('tecnico nao pode executar acoes em massa no financeiro', function () {
    $tecnico = User::factory()->create(['role' => User::ROLE_TECNICO]);

    $mov = MovimentacaoFinanceira::create([
        'tipo' => MovimentacaoFinanceira::TIPO_RECEITA,
        'categoria' => 'servico',
        'descricao' => 'Teste',
        'valor' => 100.00,
        'data_competencia' => now()->toDateString(),
        'status' => MovimentacaoFinanceira::STATUS_PENDENTE,
    ]);

    $response = $this->actingAs($tecnico)->post(route('financeiro.acoes-massa'), [
        'acao' => 'liquidar',
        'ids' => [$mov->id],
    ]);

    $response->assertForbidden();
});
