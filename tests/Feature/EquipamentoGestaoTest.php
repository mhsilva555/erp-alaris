<?php

use App\Models\CategoriaEquipamento;
use App\Models\Cliente;
use App\Models\Equipamento;
use App\Models\EquipamentoComodato;
use App\Models\User;

test('gestor pode cadastrar, visualizar e editar equipamentos do inventario', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    // Criar equipamento
    $response = $this->actingAs($admin)->post(route('equipamentos.store'), [
        'nome' => 'Porta-Iscas Túnel Profissional',
        'modelo' => 'PIT-2000',
        'tipo' => Equipamento::TIPO_PORTA_ISCAS,
        'quantidade_total' => 50,
        'quantidade_disponivel' => 50,
        'valor_unitario' => 18.50,
        'descricao' => 'Chave de segurança plástica inclusa',
        'ativo' => 1,
    ]);

    $response->assertRedirect(route('equipamentos.index'));
    $this->assertDatabaseHas('equipamentos', [
        'nome' => 'Porta-Iscas Túnel Profissional',
        'modelo' => 'PIT-2000',
        'quantidade_total' => 50,
        'quantidade_disponivel' => 50,
    ]);

    $equipamento = Equipamento::where('modelo', 'PIT-2000')->first();

    // Visualizar tela de listagem
    $viewResponse = $this->actingAs($admin)->get(route('equipamentos.index'));
    $viewResponse->assertOk();
    $viewResponse->assertSee('Porta-Iscas Túnel Profissional');
    $viewResponse->assertSee('50 un');

    // Editar equipamento
    $updateResponse = $this->actingAs($admin)->put(route('equipamentos.update', $equipamento), [
        'nome' => 'Porta-Iscas Túnel Ratoeira Super',
        'modelo' => 'PIT-2000-PLUS',
        'tipo' => Equipamento::TIPO_PORTA_ISCAS,
        'quantidade_total' => 60,
        'quantidade_disponivel' => 60,
        'valor_unitario' => 20.00,
        'descricao' => 'Atualizado',
        'ativo' => 1,
    ]);

    $updateResponse->assertRedirect(route('equipamentos.index'));
    expect($equipamento->fresh()->nome)->toBe('Porta-Iscas Túnel Ratoeira Super');
    expect($equipamento->fresh()->quantidade_total)->toBe(60);
});

test('comodato valida saldo disponivel do equipamento e debita quantidade atomicamente', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create([
        'nome' => 'Padaria Gourmet',
        'cidade' => 'Campinas',
        'estado' => 'SP',
    ]);

    $equipamento = Equipamento::create([
        'nome' => 'Armadilha Luminosa 30W',
        'modelo' => 'AL-30',
        'tipo' => Equipamento::TIPO_ARMADILHA_LUMINOSA,
        'quantidade_total' => 5,
        'quantidade_disponivel' => 5,
        'valor_unitario' => 250.00,
        'ativo' => true,
    ]);

    // Tentar alocar mais do que o disponível (ex: 8 un quando só há 5 un)
    $responseErro = $this->actingAs($admin)->post(route('comodatos.store'), [
        'cliente_id' => $cliente->id,
        'equipamento_id' => $equipamento->id,
        'quantidade' => 8,
        'periodicidade_dias' => 15,
        'data_instalacao' => now()->toDateString(),
    ]);

    $responseErro->assertSessionHasErrors(['quantidade']);
    expect($equipamento->fresh()->quantidade_disponivel)->toBe(5);

    // Alocar quantidade válida (ex: 3 un)
    $responseOk = $this->actingAs($admin)->post(route('comodatos.store'), [
        'cliente_id' => $cliente->id,
        'equipamento_id' => $equipamento->id,
        'quantidade' => 3,
        'periodicidade_dias' => 15,
        'data_instalacao' => now()->toDateString(),
    ]);

    $responseOk->assertRedirect(route('comodatos.index'));
    $this->assertDatabaseHas('equipamentos_comodato', [
        'cliente_id' => $cliente->id,
        'equipamento_id' => $equipamento->id,
        'quantidade' => 3,
    ]);

    // Saldo disponível deve ter caído de 5 para 2
    expect($equipamento->fresh()->quantidade_disponivel)->toBe(2);
    expect($equipamento->fresh()->quantidade_em_comodato)->toBe(3);
});

test('exclusao de comodato restaura saldo disponivel do equipamento', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create([
        'nome' => 'Restaurante Sabor',
        'cidade' => 'Santos',
        'estado' => 'SP',
    ]);

    $equipamento = Equipamento::create([
        'nome' => 'Polvilhadeira Manual',
        'tipo' => Equipamento::TIPO_OUTROS,
        'quantidade_total' => 10,
        'quantidade_disponivel' => 6, // já havia 4 alocados
        'ativo' => true,
    ]);

    $comodato = EquipamentoComodato::create([
        'cliente_id' => $cliente->id,
        'equipamento_id' => $equipamento->id,
        'nome_equipamento' => $equipamento->nome,
        'quantidade' => 4,
        'data_instalacao' => now()->subMonth(),
        'periodicidade_dias' => 30,
        'data_proxima_reposicao' => now()->addDays(5),
    ]);

    expect($equipamento->fresh()->quantidade_disponivel)->toBe(6);

    // Excluir comodato
    $response = $this->actingAs($admin)->delete(route('comodatos.destroy', $comodato));
    $response->assertRedirect(route('comodatos.index'));

    $this->assertDatabaseMissing('equipamentos_comodato', ['id' => $comodato->id]);
    // Saldo restaurado: 6 + 4 = 10
    expect($equipamento->fresh()->quantidade_disponivel)->toBe(10);
});

test('bloqueia exclusao de equipamento que possui comodatos vinculados', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create(['nome' => 'Empresa Teste']);

    $equipamento = Equipamento::create([
        'nome' => 'Atomizador Costal',
        'tipo' => Equipamento::TIPO_PULVERIZADOR,
        'quantidade_total' => 2,
        'quantidade_disponivel' => 1,
        'ativo' => true,
    ]);

    EquipamentoComodato::create([
        'cliente_id' => $cliente->id,
        'equipamento_id' => $equipamento->id,
        'nome_equipamento' => $equipamento->nome,
        'quantidade' => 1,
        'data_instalacao' => now(),
        'periodicidade_dias' => 30,
        'data_proxima_reposicao' => now()->addDays(30),
    ]);

    $response = $this->actingAs($admin)->delete(route('equipamentos.destroy', $equipamento));
    $response->assertRedirect(route('equipamentos.index'));
    $response->assertSessionHas('erro');

    $this->assertDatabaseHas('equipamentos', ['id' => $equipamento->id]);
});

test('gestor pode listar, adicionar e gerenciar categorias de equipamentos', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    // Listar categorias
    $listResponse = $this->actingAs($admin)->getJson(route('equipamentos.categorias.index'));
    $listResponse->assertOk();
    $listResponse->assertJsonStructure(['sucesso', 'categorias']);

    // Criar nova categoria
    $createResponse = $this->actingAs($admin)->postJson(route('equipamentos.categorias.store'), [
        'nome' => 'Gaiolas e Armadilhas de Captura',
    ]);

    $createResponse->assertCreated();
    $createResponse->assertJson(['sucesso' => true]);
    $this->assertDatabaseHas('categoria_equipamentos', [
        'nome' => 'Gaiolas e Armadilhas de Captura',
        'slug' => 'gaiolas_e_armadilhas_de_captura',
    ]);

    $categoria = CategoriaEquipamento::where('nome', 'Gaiolas e Armadilhas de Captura')->first();

    // Validação de duplicidade
    $dupResponse = $this->actingAs($admin)->postJson(route('equipamentos.categorias.store'), [
        'nome' => 'Gaiolas e Armadilhas de Captura',
    ]);
    $dupResponse->assertStatus(422);
    $dupResponse->assertJsonValidationErrors(['nome']);

    // Excluir categoria sem equipamentos
    $deleteResponse = $this->actingAs($admin)->deleteJson(route('equipamentos.categorias.destroy', $categoria));
    $deleteResponse->assertOk();
    $this->assertDatabaseMissing('categoria_equipamentos', ['id' => $categoria->id]);
});

test('bloqueia exclusao de categoria que possui equipamentos vinculados', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $categoria = CategoriaEquipamento::create([
        'nome' => 'Termonebulizadores Pesados',
        'slug' => 'termonebulizadores_pesados',
    ]);

    Equipamento::create([
        'nome' => 'Termonebulizador Fogger 500',
        'tipo' => $categoria->slug,
        'quantidade_total' => 2,
        'quantidade_disponivel' => 2,
    ]);

    $response = $this->actingAs($admin)->deleteJson(route('equipamentos.categorias.destroy', $categoria));
    $response->assertStatus(422);
    $response->assertJson(['sucesso' => false]);

    $this->assertDatabaseHas('categoria_equipamentos', ['id' => $categoria->id]);
});
