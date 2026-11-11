<?php

use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\ContratoItem;
use App\Models\Produto;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Visita;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->tecnico = Tecnico::create(['nome' => 'Técnico Responsável', 'ativo' => true]);
    $this->tecnicoUser = User::factory()->create(['role' => User::ROLE_TECNICO, 'tecnico_id' => $this->tecnico->id]);

    $this->cliente = Cliente::create([
        'nome' => 'Cliente Teste Lixeira',
        'cidade' => 'Campinas',
        'estado' => 'SP',
    ]);

    $this->produto = Produto::create([
        'nome' => 'Inseticida Gel Barata',
        'natureza' => 'liquido',
        'unidade' => 'L',
        'unidade_armazenamento' => 'L',
        'unidade_aplicacao' => 'mL',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 10,
        'preco_custo_unitario' => 100.00,
        'ativo' => true,
    ]);
});

test('pode mover uma proposta comercial para a lixeira (soft delete)', function () {
    $proposta = Contrato::create([
        'numero' => 'PROP-LIXEIRA-01',
        'cliente_id' => $this->cliente->id,
        'titulo' => 'Proposta Comercial Descartada',
        'status' => Contrato::STATUS_PROPOSTA,
        'tipo_vigencia' => Contrato::VIGENCIA_DETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'valor_total' => 600.00,
    ]);

    ContratoItem::create([
        'contrato_id' => $proposta->id,
        'descricao' => 'Serviço de Controle de Pragas',
        'quantidade' => 1,
        'valor_unitario' => 600.00,
        'valor_total' => 600.00,
    ]);

    $response = $this->actingAs($this->admin)->delete(route('contratos.destroy', $proposta));
    $response->assertRedirect(route('contratos.index'));
    $response->assertSessionHas('sucesso');

    // Não deve aparecer na consulta padrão
    expect(Contrato::find($proposta->id))->toBeNull();

    // DEVE existir na lixeira com deleted_at preenchido
    $propostaNaLixeira = Contrato::onlyTrashed()->find($proposta->id);
    expect($propostaNaLixeira)->not->toBeNull();
    expect($propostaNaLixeira->deleted_at)->not->toBeNull();

    // Seus itens permanecem preservados
    expect($propostaNaLixeira->itens)->toHaveCount(1);
});

test('pode visualizar listagem da lixeira com propostas e contratos', function () {
    $proposta = Contrato::create([
        'numero' => 'PROP-LISTA-LIXEIRA',
        'cliente_id' => $this->cliente->id,
        'titulo' => 'Proposta na Lixeira',
        'status' => Contrato::STATUS_PROPOSTA,
        'tipo_vigencia' => Contrato::VIGENCIA_DETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'valor_total' => 450.00,
    ]);
    $proposta->delete();

    $response = $this->actingAs($this->admin)->get(route('contratos.lixeira'));
    $response->assertOk();
    $response->assertSee('Lixeira de Propostas e Contratos');
    $response->assertSee('PROP-LISTA-LIXEIRA');
    $response->assertSee('Proposta Comercial');
    $response->assertSee('Restaurar');
    $response->assertSee('Excluir');
});

test('pode visualizar detalhes de um contrato ou proposta que esta na lixeira', function () {
    $contrato = Contrato::create([
        'numero' => 'CTR-SHOW-LIXEIRA',
        'cliente_id' => $this->cliente->id,
        'titulo' => 'Contrato Visualizar Lixeira',
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'valor_total' => 800.00,
    ]);
    $contrato->delete();

    $response = $this->actingAs($this->admin)->get(route('contratos.show', $contrato));
    $response->assertOk();
    $response->assertSee('Este registro está atualmente na Lixeira');
    $response->assertSee('Na Lixeira');
    $response->assertSee('Restaurar da Lixeira');
    $response->assertSee('Excluir Permanentemente');
});

test('pode restaurar uma proposta ou contrato da lixeira', function () {
    $proposta = Contrato::create([
        'numero' => 'PROP-RESTAURAR-01',
        'cliente_id' => $this->cliente->id,
        'titulo' => 'Proposta a Restaurar',
        'status' => Contrato::STATUS_PROPOSTA,
        'tipo_vigencia' => Contrato::VIGENCIA_DETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'valor_total' => 700.00,
    ]);
    $proposta->delete();

    expect(Contrato::find($proposta->id))->toBeNull();

    $response = $this->actingAs($this->admin)->post(route('contratos.restaurar', $proposta->id));
    $response->assertRedirect(route('contratos.show', $proposta));
    $response->assertSessionHas('sucesso');

    // Contrato restaurado deve voltar à consulta padrão
    $restaurado = Contrato::find($proposta->id);
    expect($restaurado)->not->toBeNull();
    expect($restaurado->deleted_at)->toBeNull();
});

test('pode excluir definitivamente uma proposta da lixeira (force delete)', function () {
    $proposta = Contrato::create([
        'numero' => 'PROP-FORCE-DELETE',
        'cliente_id' => $this->cliente->id,
        'titulo' => 'Proposta para Exclusão Física',
        'status' => Contrato::STATUS_PROPOSTA,
        'tipo_vigencia' => Contrato::VIGENCIA_DETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'valor_total' => 300.00,
    ]);

    ContratoItem::create([
        'contrato_id' => $proposta->id,
        'descricao' => 'Item de teste',
        'quantidade' => 1,
        'valor_unitario' => 300.00,
        'valor_total' => 300.00,
    ]);

    $proposta->delete();
    expect(Contrato::onlyTrashed()->find($proposta->id))->not->toBeNull();

    $response = $this->actingAs($this->admin)->delete(route('contratos.force-delete', $proposta->id));
    $response->assertRedirect(route('contratos.lixeira'));
    $response->assertSessionHas('sucesso');

    // Não deve existir nem mesmo com withTrashed
    expect(Contrato::withTrashed()->find($proposta->id))->toBeNull();
    expect(ContratoItem::where('contrato_id', $proposta->id)->count())->toBe(0);
});

test('nao permite mover para a lixeira nem excluir definitivamente contrato com visitas executadas', function () {
    $contrato = Contrato::create([
        'numero' => 'CTR-COM-EXECUCAO',
        'cliente_id' => $this->cliente->id,
        'status' => Contrato::STATUS_ATIVO,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'valor_total' => 500.00,
    ]);

    $visitaConcluida = Visita::create([
        'cliente_id' => $this->cliente->id,
        'contrato_id' => $contrato->id,
        'tecnico_id' => $this->tecnico->id,
        'data_hora' => now()->subDays(5),
        'status' => Visita::STATUS_CONCLUIDA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 500.00,
    ]);

    // Tentativa de mover para a lixeira deve ser bloqueada
    $response = $this->actingAs($this->admin)->delete(route('contratos.destroy', $contrato));
    $response->assertRedirect(route('contratos.show', $contrato));
    $response->assertSessionHas('erro');

    expect($contrato->fresh()->deleted_at)->toBeNull();
});

test('pode esvaziar a lixeira excluindo registros sem historico executado', function () {
    $prop1 = Contrato::create([
        'numero' => 'PROP-ESVAZIAR-01',
        'cliente_id' => $this->cliente->id,
        'status' => Contrato::STATUS_PROPOSTA,
        'tipo_vigencia' => Contrato::VIGENCIA_DETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'valor_total' => 100.00,
    ]);
    $prop1->delete();

    $prop2 = Contrato::create([
        'numero' => 'PROP-ESVAZIAR-02',
        'cliente_id' => $this->cliente->id,
        'status' => Contrato::STATUS_PROPOSTA,
        'tipo_vigencia' => Contrato::VIGENCIA_DETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'valor_total' => 200.00,
    ]);
    $prop2->delete();

    expect(Contrato::onlyTrashed()->count())->toBe(2);

    $response = $this->actingAs($this->admin)->post(route('contratos.esvaziar-lixeira'));
    $response->assertRedirect(route('contratos.lixeira'));
    $response->assertSessionHas('sucesso');

    expect(Contrato::onlyTrashed()->count())->toBe(0);
});

test('usuario sem permissao de deletar nao pode mover para a lixeira nem restaurar', function () {
    $proposta = Contrato::create([
        'numero' => 'PROP-PERM-TEST',
        'cliente_id' => $this->cliente->id,
        'status' => Contrato::STATUS_PROPOSTA,
        'tipo_vigencia' => Contrato::VIGENCIA_DETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'valor_total' => 150.00,
    ]);

    // Técnico não possui permissão contratos.deletar
    $response = $this->actingAs($this->tecnicoUser)->delete(route('contratos.destroy', $proposta));
    $response->assertForbidden();

    $proposta->delete();

    $response = $this->actingAs($this->tecnicoUser)->post(route('contratos.restaurar', $proposta->id));
    $response->assertForbidden();

    $response = $this->actingAs($this->tecnicoUser)->delete(route('contratos.force-delete', $proposta->id));
    $response->assertForbidden();
});
