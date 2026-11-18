<?php

use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\MovimentacaoFinanceira;
use App\Models\Produto;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Visita;

test('exibe mensagem de erro no layout ao tentar excluir contrato com OS executada', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create([
        'nome' => 'Cliente Teste Bloqueio',
        'cidade' => 'Imperatriz',
        'estado' => 'MA',
    ]);

    $tecnico = Tecnico::create([
        'nome' => 'Técnico Bloqueio',
        'ativo' => true,
    ]);

    $contrato = Contrato::create([
        'numero' => 'CTR-BLQ-0001',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'valor_total' => 300.00,
    ]);

    Visita::create([
        'cliente_id' => $cliente->id,
        'contrato_id' => $contrato->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now(),
        'status' => Visita::STATUS_CONCLUIDA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
        'valor_cobrado' => 0.00,
    ]);

    $response = $this->actingAs($admin)
        ->followingRedirects()
        ->delete(route('contratos.destroy', $contrato));

    $response->assertOk();
    $response->assertSee('não pode ser excluído/movido para a lixeira porque existem ordens de serviço executadas ou em andamento');
    expect(Contrato::find($contrato->id))->not->toBeNull();
});

test('impede exclusao direta de faturamento vinculado a contrato ativo ou OS concluida com mensagem explicativa', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create([
        'nome' => 'Empresa Faturamento Protegido',
        'cidade' => 'Imperatriz',
        'estado' => 'MA',
    ]);

    $tecnico = Tecnico::create([
        'nome' => 'Técnico Faturamento',
        'ativo' => true,
    ]);

    $contrato = Contrato::create([
        'numero' => 'CTR-FIN-0001',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->startOfMonth()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'valor_total' => 500.00,
    ]);

    $movContrato = $contrato->sincronizarMovimentacaoMes(now());

    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now(),
        'status' => Visita::STATUS_CONCLUIDA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
        'valor_cobrado' => 250.00,
    ]);

    $movOs = MovimentacaoFinanceira::create([
        'visita_id' => $visita->id,
        'tipo' => MovimentacaoFinanceira::TIPO_RECEITA,
        'categoria' => 'faturamento_servico',
        'descricao' => "Faturamento OS #{$visita->id}",
        'valor' => 250.00,
        'data_competencia' => now()->toDateString(),
        'status' => MovimentacaoFinanceira::STATUS_PENDENTE,
    ]);

    // Tentativa individual em faturamento de contrato ativo
    $respCtr = $this->actingAs($admin)->delete(route('financeiro.destroy', $movContrato));
    $respCtr->assertSessionHas('erro');
    expect(MovimentacaoFinanceira::find($movContrato->id))->not->toBeNull();

    // Tentativa individual em faturamento de OS concluída
    $respOs = $this->actingAs($admin)->delete(route('financeiro.destroy', $movOs));
    $respOs->assertSessionHas('erro');
    expect(MovimentacaoFinanceira::find($movOs->id))->not->toBeNull();

    // Tentativa em massa
    $respMassa = $this->actingAs($admin)->post(route('financeiro.acoes-massa'), [
        'acao' => 'excluir',
        'ids' => [$movContrato->id, $movOs->id],
    ]);
    $respMassa->assertSessionHas('erro');
    expect(MovimentacaoFinanceira::find($movContrato->id))->not->toBeNull();
    expect(MovimentacaoFinanceira::find($movOs->id))->not->toBeNull();
});

test('impede exclusao de cliente, produto e tecnico com relacionamentos e informa motivo', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create([
        'nome' => 'Cliente Vinculado',
        'cidade' => 'Imperatriz',
        'estado' => 'MA',
    ]);

    $tecnico = Tecnico::create([
        'nome' => 'Técnico Vinculado',
        'ativo' => true,
    ]);

    $produto = Produto::create([
        'nome' => 'Inseticida Teste',
        'natureza' => 'liquido',
        'unidade_armazenamento' => 'L',
        'unidade_aplicacao' => 'mL',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 10,
        'estoque_minimo' => 1,
        'preco_custo_unitario' => 100.00,
    ]);

    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now(),
        'status' => Visita::STATUS_AGENDADA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
        'valor_cobrado' => 100.00,
    ]);

    $visita->produtos()->attach($produto->id, [
        'quantidade' => 10,
        'custo_proporcional' => 1.00,
        'fracao_baixada' => 0.01,
    ]);

    $this->actingAs($admin)->delete(route('clientes.destroy', $cliente))->assertSessionHas('erro');
    $this->actingAs($admin)->delete(route('tecnicos.destroy', $tecnico))->assertSessionHas('erro');
    $this->actingAs($admin)->delete(route('produtos.destroy', $produto))->assertSessionHas('erro');

    expect(Cliente::find($cliente->id))->not->toBeNull();
    expect(Tecnico::find($tecnico->id))->not->toBeNull();
    expect(Produto::find($produto->id))->not->toBeNull();
});

test('polling de notificacoes nao consome mensagem flash de erro antes do redirect', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create([
        'nome' => 'Cliente Polling Flash',
        'cidade' => 'Imperatriz',
        'estado' => 'MA',
    ]);

    Contrato::create([
        'numero' => 'CTR-POLL-0001',
        'cliente_id' => $cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'valor_total' => 200.00,
    ]);

    $this->actingAs($admin)->from(route('clientes.index'))
        ->delete(route('clientes.destroy', $cliente))
        ->assertRedirect(route('clientes.index'))
        ->assertSessionHas('erro');

    // Simula requisições concorrentes de polling antes de carregar a página redirecionada
    $this->actingAs($admin)->getJson(route('notificacoes'))->assertOk();
    $this->actingAs($admin)->getJson(route('notificacoes.agendamentos.pendentes'))->assertOk();

    // A mensagem de erro continua intacta e é exibida na página de clientes
    $page = $this->actingAs($admin)->get(route('clientes.index'));
    $page->assertOk();
    $page->assertSee('não pode ser excluído porque possui contrato(s) ou proposta(s) comercial(is) vinculada(s)');
});
