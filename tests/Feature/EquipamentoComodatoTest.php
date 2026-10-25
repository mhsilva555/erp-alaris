<?php

use App\Models\Cliente;
use App\Models\EquipamentoComodato;
use App\Models\Produto;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Visita;

test('gestor pode cadastrar equipamento em comodato e registrar reposicao preventiva', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create([
        'nome' => 'Supermercado Central',
        'cidade' => 'São Paulo',
        'estado' => 'SP',
    ]);
    $produto = Produto::create([
        'nome' => 'Isca Raticida Bloco',
        'natureza' => 'solido',
        'unidade' => 'Kg',
        'unidade_armazenamento' => 'Kg',
        'unidade_aplicacao' => 'g',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 10,
        'preco_custo_unitario' => 45.00,
        'ativo' => true,
    ]);

    $comodato = EquipamentoComodato::create([
        'cliente_id' => $cliente->id,
        'produto_id' => $produto->id,
        'nome_equipamento' => 'Porta-Iscas Doca de Carga PI-001',
        'quantidade' => 2,
        'data_instalacao' => now()->subDays(60),
        'periodicidade_dias' => 30,
        'data_ultima_reposicao' => now()->subDays(31),
        'data_proxima_reposicao' => now()->subDay(),
        'observacoes' => 'Instalado na doca externa',
    ]);

    expect($comodato->estaAtrasado())->toBeTrue();

    // Registrar reposição via model helper
    $comodato->registrarReposicao(now());

    expect($comodato->data_ultima_reposicao->isToday())->toBeTrue();
    expect($comodato->data_proxima_reposicao->format('Y-m-d'))->toBe(now()->addDays(30)->format('Y-m-d'));
    expect($comodato->estaAtrasado())->toBeFalse();

    // Testar rota de registro de reposição
    $response = $this->actingAs($admin)
        ->post(route('comodatos.reposicao', $comodato));

    $response->assertRedirect();
});

test('gestor pode visualizar tela de detalhes do comodato show com prazos e historico de OS', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create([
        'nome' => 'Supermercado Central',
        'cidade' => 'São Paulo',
        'estado' => 'SP',
    ]);
    $comodato = EquipamentoComodato::create([
        'cliente_id' => $cliente->id,
        'nome_equipamento' => 'Armadilha Luminosa L-200',
        'quantidade' => 3,
        'data_instalacao' => now()->subDays(10),
        'periodicidade_dias' => 15,
        'data_ultima_reposicao' => now()->subDays(10),
        'data_proxima_reposicao' => now()->addDays(5),
    ]);

    $response = $this->actingAs($admin)->get(route('comodatos.show', $comodato));
    $response->assertOk();
    $response->assertSee('Armadilha Luminosa L-200');
    $response->assertSee('Supermercado Central');
    $response->assertSee('Histórico de Ordens de Serviço');
    $response->assertSee('Agendar Ordem de Serviço');
});

test('finalizacao de ordem de servico vinculada a comodato baixa automaticamente a reposicao e recalcula proxima data', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create(['nome' => 'ATHOMUS LTDA', 'cidade' => 'Campinas', 'estado' => 'SP']);
    $tecnico = Tecnico::create(['nome' => 'Carlos Silva', 'ativo' => true]);

    $comodato = EquipamentoComodato::create([
        'cliente_id' => $cliente->id,
        'nome_equipamento' => 'Armadilha - T-500',
        'quantidade' => 4,
        'data_instalacao' => now()->subDays(30),
        'periodicidade_dias' => 15,
        'data_ultima_reposicao' => now()->subDays(30),
        'data_proxima_reposicao' => now()->subDays(15),
    ]);

    // Cria a Visita vinculada a esse comodato
    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'comodato_id' => $comodato->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->setTime(10, 0),
        'status' => Visita::STATUS_AGENDADA,
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'observacoes' => 'Reposição de iscas nas armadilhas',
    ]);

    expect($visita->comodato_id)->toBe($comodato->id);

    // Finaliza a visita pelo endpoint de conclusão
    $response = $this->actingAs($admin)->post(route('visitas.finalizar', $visita), [
        'observacoes' => 'Iscas repostas com sucesso em todas as 4 armadilhas.',
    ]);

    $response->assertRedirect(route('visitas.show', $visita));

    $visita->refresh();
    expect($visita->status)->toBe(Visita::STATUS_CONCLUIDA);

    // O comodato deve ter sido atualizado automaticamente!
    $comodato->refresh();
    expect($comodato->data_ultima_reposicao->isToday())->toBeTrue();
    expect($comodato->data_proxima_reposicao->toDateString())->toBe(now()->addDays(15)->toDateString());
});

test('os de comodato registra custo de diaria do tecnico e consolida nos relatorios financeiros', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create([
        'nome' => 'Supermercado Central',
        'cidade' => 'São Luís',
        'estado' => 'MA',
    ]);

    $tecnico = Tecnico::create([
        'nome' => 'Raimundo Dedetizador',
        'valor_diaria_padrao' => 120.00,
        'ativo' => true,
    ]);

    $comodato = EquipamentoComodato::create([
        'cliente_id' => $cliente->id,
        'nome_equipamento' => 'Porta Iscas Refil',
        'quantidade' => 10,
        'data_instalacao' => '2026-10-01',
        'periodicidade_dias' => 30,
        'data_proxima_reposicao' => '2026-11-10',
    ]);

    // Submete abertura de nova OS vinculada ao comodato sem preencher custo_mao_de_obra manualmente
    $responseCreate = $this->actingAs($admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'comodato_id' => $comodato->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => '2026-11-10T09:00',
        'status' => Visita::STATUS_AGENDADA,
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 0.00,
    ]);

    $responseCreate->assertRedirect(route('visitas.index'));

    $visita = Visita::where('comodato_id', $comodato->id)->first();
    expect($visita)->not->toBeNull();
    // Diária padrão do técnico deve ser registrada na OS aberta
    expect((float) $visita->custo_mao_de_obra)->toBe(120.00);

    // Relatório financeiro no mês de novembro deve consolidar a diária da OS aberta de comodato
    $resRelatorio = $this->actingAs($admin)->get(route('financeiro.relatorio', [
        'mes' => '2026-11',
        'status' => 'todas',
    ]));
    $resRelatorio->assertOk();
    $resRelatorio->assertSee('OS #'.$visita->id);
    $resRelatorio->assertSee('120,00');
    $resRelatorio->assertSee('Raimundo Dedetizador');
    $resRelatorio->assertSee('Comodato');
});
