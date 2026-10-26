<?php

use App\Models\Cliente;
use App\Models\Configuracao;
use App\Models\Produto;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Visita;

beforeEach(function () {
    Configuracao::set('aliquota_imposto_padrao', '14.0');
    Configuracao::set('diaria_tecnico_padrao', '50.00');
});

test('valida e bloqueia criacao de OS quando produto nao possui estoque suficiente', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create(['nome' => 'Padaria Pão Quente', 'cidade' => 'Campinas', 'estado' => 'SP']);
    $tecnico = Tecnico::create(['nome' => 'Paulo Dedetizador', 'ativo' => true]);

    // Produto com apenas 100 mL (0.10 L) em estoque
    $produto = Produto::create([
        'nome' => 'Inseticida Gel Alpha',
        'natureza' => 'liquido',
        'unidade' => 'L',
        'unidade_armazenamento' => 'L',
        'unidade_aplicacao' => 'mL',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 0.1000,
        'preco_custo_unitario' => 150.00,
        'ativo' => true,
    ]);

    // Tentar criar OS solicitando 250 mL (0.25 L), excedendo o estoque
    $response = $this->actingAs($admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->addHour()->format('Y-m-d\TH:i'),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 300.00,
        'produtos' => [
            ['produto_id' => $produto->id, 'quantidade' => 250],
        ],
    ]);

    $response->assertSessionHasErrors(['produtos']);
    $produto->refresh();
    // Saldo físico deve permanecer inalterado em 0.10 L
    expect((float) $produto->quantidade_estoque)->toBe(0.1);
    expect(Visita::count())->toBe(0);
});

test('debita estoque fracionado imediatamente na criacao de OS agendada e impede que outra OS consuma saldo inexistente', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create(['nome' => 'Restaurante Central', 'cidade' => 'Campinas', 'estado' => 'SP']);
    $tecnico = Tecnico::create(['nome' => 'Paulo Dedetizador', 'ativo' => true]);

    // Produto com 1.0 L (1000 mL) em estoque
    $produto = Produto::create([
        'nome' => 'Cupinicida Líquido Ultra',
        'natureza' => 'liquido',
        'unidade' => 'L',
        'unidade_armazenamento' => 'L',
        'unidade_aplicacao' => 'mL',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 1.0000,
        'preco_custo_unitario' => 200.00,
        'ativo' => true,
    ]);

    // 1. Criar primeira OS agendada consumindo 800 mL (0.80 L)
    $response1 = $this->actingAs($admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->addHour()->format('Y-m-d\TH:i'),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 500.00,
        'produtos' => [
            ['produto_id' => $produto->id, 'quantidade' => 800],
        ],
    ]);

    $response1->assertRedirect(route('visitas.index'));
    $produto->refresh();

    // Saldo em estoque deve ter caído imediatamente para 0.20 L (200 mL restantes)
    expect((float) $produto->quantidade_estoque)->toBe(0.2);

    $visita1 = Visita::first();
    expect($visita1->status)->toBe('agendada');
    expect((float) $visita1->produtos->first()->pivot->fracao_baixada)->toBe(0.8);

    // 2. Tentar criar segunda OS solicitando 400 mL (0.40 L) quando só restam 0.20 L
    $response2 = $this->actingAs($admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->addDays(2)->format('Y-m-d\TH:i'),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 400.00,
        'produtos' => [
            ['produto_id' => $produto->id, 'quantidade' => 400],
        ],
    ]);

    $response2->assertSessionHasErrors(['produtos']);
    $produto->refresh();
    expect((float) $produto->quantidade_estoque)->toBe(0.2);

    // 3. Criar segunda OS com o saldo exato restante de 200 mL (0.20 L)
    $response3 = $this->actingAs($admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->addDays(2)->format('Y-m-d\TH:i'),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 400.00,
        'produtos' => [
            ['produto_id' => $produto->id, 'quantidade' => 200],
        ],
    ]);

    $response3->assertRedirect(route('visitas.index'));
    $produto->refresh();
    // Saldo zerado com precisão
    expect((float) $produto->quantidade_estoque)->toBe(0.0);
});

test('estorna produto ao estoque quando OS e cancelada diretamente', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create(['nome' => 'Lanchonete Express', 'cidade' => 'Campinas', 'estado' => 'SP']);
    $tecnico = Tecnico::create(['nome' => 'Paulo Dedetizador', 'ativo' => true]);

    $produto = Produto::create([
        'nome' => 'Detergente Concentrado',
        'natureza' => 'liquido',
        'unidade' => 'L',
        'unidade_armazenamento' => 'L',
        'unidade_aplicacao' => 'mL',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 5.0000,
        'preco_custo_unitario' => 80.00,
        'ativo' => true,
    ]);

    // Criar OS com 500 mL (0.50 L)
    $this->actingAs($admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->addHour()->format('Y-m-d\TH:i'),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 250.00,
        'produtos' => [
            ['produto_id' => $produto->id, 'quantidade' => 500],
        ],
    ]);

    $produto->refresh();
    expect((float) $produto->quantidade_estoque)->toBe(4.5);

    $visita = Visita::first();

    // Cancelar a OS pelo endpoint dedicado
    $responseCancel = $this->actingAs($admin)->post(route('visitas.cancelar', $visita));
    $responseCancel->assertRedirect(route('visitas.show', $visita));
    $responseCancel->assertSessionHas('sucesso');

    $visita->refresh();
    $produto->refresh();

    // Status deve ser 'cancelada' e estoque estornado para 5.0 L
    expect($visita->status)->toBe(Visita::STATUS_CANCELADA);
    expect((float) $produto->quantidade_estoque)->toBe(5.0);
    expect((float) $visita->produtos->first()->pivot->fracao_baixada)->toBe(0.0);
});

test('estorna produto ao estoque quando OS tem status alterado para cancelada na edicao', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create(['nome' => 'Lanchonete Express', 'cidade' => 'Campinas', 'estado' => 'SP']);
    $tecnico = Tecnico::create(['nome' => 'Paulo Dedetizador', 'ativo' => true]);

    $produto = Produto::create([
        'nome' => 'Isca Granulada Raticida',
        'natureza' => 'solido',
        'unidade' => 'Kg',
        'unidade_armazenamento' => 'Kg',
        'unidade_aplicacao' => 'g',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 2.0000,
        'preco_custo_unitario' => 60.00,
        'ativo' => true,
    ]);

    // Criar OS com 300g (0.30 Kg)
    $this->actingAs($admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->addHour()->format('Y-m-d\TH:i'),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 300.00,
        'produtos' => [
            ['produto_id' => $produto->id, 'quantidade' => 300],
        ],
    ]);

    $produto->refresh();
    expect((float) $produto->quantidade_estoque)->toBe(1.7);

    $visita = Visita::first();

    // Atualizar status para 'cancelada' via edit form
    $this->actingAs($admin)->put(route('visitas.update', $visita), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->addHour()->format('Y-m-d\TH:i'),
        'status' => Visita::STATUS_CANCELADA,
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 300.00,
        'produtos' => [
            ['produto_id' => $produto->id, 'quantidade' => 300],
        ],
    ]);

    $visita->refresh();
    $produto->refresh();

    expect($visita->status)->toBe(Visita::STATUS_CANCELADA);
    expect((float) $produto->quantidade_estoque)->toBe(2.0);
    expect((float) $visita->produtos->first()->pivot->fracao_baixada)->toBe(0.0);
});

test('edicao de OS ajusta estoque proporcionalmente ao aumentar ou diminuir dose ou remover produto', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create(['nome' => 'Padaria Modelo', 'cidade' => 'Campinas', 'estado' => 'SP']);
    $tecnico = Tecnico::create(['nome' => 'Paulo Dedetizador', 'ativo' => true]);

    $produtoA = Produto::create([
        'nome' => 'Produto A',
        'natureza' => 'liquido',
        'unidade' => 'L',
        'unidade_armazenamento' => 'L',
        'unidade_aplicacao' => 'mL',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 1.0000,
        'preco_custo_unitario' => 100.00,
        'ativo' => true,
    ]);

    // 1. Criar OS com 200 mL (0.20 L) de Produto A -> Estoque vai de 1.0 para 0.8 L
    $this->actingAs($admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->addHour()->format('Y-m-d\TH:i'),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 300.00,
        'produtos' => [
            ['produto_id' => $produtoA->id, 'quantidade' => 200],
        ],
    ]);

    $visita = Visita::first();
    expect((float) $produtoA->fresh()->quantidade_estoque)->toBe(0.8);

    // 2. Aumentar dose para 500 mL (0.50 L) -> Delta = +0.30 L. Estoque vai de 0.8 para 0.5 L
    $this->actingAs($admin)->put(route('visitas.update', $visita), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->addHour()->format('Y-m-d\TH:i'),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 300.00,
        'produtos' => [
            ['produto_id' => $produtoA->id, 'quantidade' => 500],
        ],
    ]);

    expect((float) $produtoA->fresh()->quantidade_estoque)->toBe(0.5);

    // 3. Reduzir dose para 100 mL (0.10 L) -> Delta = -0.40 L. Estoque sobe de 0.5 para 0.9 L
    $this->actingAs($admin)->put(route('visitas.update', $visita), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->addHour()->format('Y-m-d\TH:i'),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 300.00,
        'produtos' => [
            ['produto_id' => $produtoA->id, 'quantidade' => 100],
        ],
    ]);

    expect((float) $produtoA->fresh()->quantidade_estoque)->toBe(0.9);

    // 4. Remover produto da OS (enviar lista vazia) -> Devolve os 0.10 L. Estoque volta para 1.0 L
    $this->actingAs($admin)->put(route('visitas.update', $visita), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->addHour()->format('Y-m-d\TH:i'),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 300.00,
        'produtos' => [],
    ]);

    expect((float) $produtoA->fresh()->quantidade_estoque)->toBe(1.0);
    expect($visita->fresh()->produtos)->toBeEmpty();
});

test('exclusao de OS estorna insumos baixados de volta ao estoque', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create(['nome' => 'Armazém São Bento', 'cidade' => 'Campinas', 'estado' => 'SP']);
    $tecnico = Tecnico::create(['nome' => 'Paulo Dedetizador', 'ativo' => true]);

    $produto = Produto::create([
        'nome' => 'Isca Parafinada Bloco',
        'natureza' => 'unidade',
        'unidade' => 'Un',
        'unidade_armazenamento' => 'Un',
        'unidade_aplicacao' => 'Un',
        'fator_conversao' => 1,
        'quantidade_estoque' => 10.0,
        'preco_custo_unitario' => 15.00,
        'ativo' => true,
    ]);

    // Criar OS com 4 unidades
    $this->actingAs($admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->addHour()->format('Y-m-d\TH:i'),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 200.00,
        'produtos' => [
            ['produto_id' => $produto->id, 'quantidade' => 4],
        ],
    ]);

    expect((float) $produto->fresh()->quantidade_estoque)->toBe(6.0);

    $visita = Visita::first();

    // Deletar a visita
    $response = $this->actingAs($admin)->delete(route('visitas.destroy', $visita));
    $response->assertRedirect(route('visitas.index'));

    // Saldo físico deve ter voltado para 10 unidades
    expect((float) $produto->fresh()->quantidade_estoque)->toBe(10.0);
    expect(Visita::count())->toBe(0);
});

test('finalizacao de visita nao duplica baixa de insumos quando ja foram debitados na criacao', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create(['nome' => 'Clube Recreativo', 'cidade' => 'Campinas', 'estado' => 'SP']);
    $tecnico = Tecnico::create(['nome' => 'Paulo Dedetizador', 'ativo' => true]);

    $produto = Produto::create([
        'nome' => 'Inseticida Aerosol Turbo',
        'natureza' => 'liquido',
        'unidade' => 'L',
        'unidade_armazenamento' => 'L',
        'unidade_aplicacao' => 'mL',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 2.0000,
        'preco_custo_unitario' => 90.00,
        'ativo' => true,
    ]);

    // Criar OS com 200 mL (0.20 L)
    $this->actingAs($admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->addHour()->format('Y-m-d\TH:i'),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 350.00,
        'produtos' => [
            ['produto_id' => $produto->id, 'quantidade' => 200],
        ],
    ]);

    // Saldo já baixado para 1.8 L na criação
    expect((float) $produto->fresh()->quantidade_estoque)->toBe(1.8);

    $visita = Visita::first();

    // Finalizar a OS
    $responseFin = $this->actingAs($admin)->post(route('visitas.finalizar', $visita), [
        'observacoes' => 'Serviço concluído.',
    ]);

    $responseFin->assertRedirect(route('visitas.show', $visita));

    // Saldo NÃO deve ter sido debitado novamente: deve continuar exatamente 1.8 L!
    expect((float) $produto->fresh()->quantidade_estoque)->toBe(1.8);
    expect($visita->fresh()->status)->toBe(Visita::STATUS_CONCLUIDA);
});
