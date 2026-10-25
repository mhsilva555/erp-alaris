<?php

use App\Models\Cliente;
use App\Models\Configuracao;
use App\Models\Produto;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Visita;

test('calcula baixa de estoque fracionada e custo proporcional na conclusao da visita', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    Configuracao::set('aliquota_imposto_padrao', '14.0');
    Configuracao::set('diaria_tecnico_padrao', '50.00');

    $cliente = Cliente::create([
        'nome' => 'Restaurante Sabor',
        'cidade' => 'Campinas',
        'estado' => 'SP',
    ]);

    $tecnico = Tecnico::create([
        'nome' => 'Carlos Silva',
        'valor_diaria_padrao' => 60.00,
        'ativo' => true,
    ]);

    // Produto líquido: 10 Litros em estoque, R$ 120,00 por Litro. Fator de conversão 1000 (mL)
    $produto = Produto::create([
        'nome' => 'Inseticida Concentrado',
        'natureza' => 'liquido',
        'unidade' => 'L',
        'unidade_armazenamento' => 'L',
        'unidade_aplicacao' => 'mL',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 10.0,
        'preco_custo_unitario' => 120.00,
        'ativo' => true,
    ]);

    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now(),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 450.00,
        'custo_deslocamento' => 30.00,
        'custo_mao_de_obra' => 60.00,
        'exige_nota_fiscal' => true,
        'aliquota_imposto' => 14.0,
    ]);

    // Aplicação de 50 mL do inseticida
    // Baixa esperada: 50 / 1000 = 0.05 L
    // Custo proporcional esperado: 0.05 * 120 = R$ 6.00
    $dose = 50.0;
    $fracao = $produto->calcularFracaoBaixa($dose);
    $custo = $produto->calcularCustoDose($dose);

    expect($fracao)->toBe(0.05);
    expect($custo)->toBe(6.00);

    // Concluir a visita via controller update
    $response = $this->actingAs($admin)->put(route('visitas.update', $visita), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->format('Y-m-d\TH:i'),
        'status' => 'concluida',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 450.00,
        'custo_deslocamento' => 30.00,
        'custo_mao_de_obra' => 60.00,
        'exige_nota_fiscal' => '1',
        'aliquota_imposto' => 14.0,
        'produtos' => [
            ['produto_id' => $produto->id, 'quantidade' => 50],
        ],
    ]);

    $response->assertRedirect();

    $visita->refresh();
    $produto->refresh();

    // Estoque deve ter baixado de 10.0 para 9.95
    expect((float) $produto->quantidade_estoque)->toBe(9.95);

    // Custo de produtos da visita deve ser R$ 6,00
    expect((float) $visita->custo_produtos)->toBe(6.00);

    // Imposto esperado: 14% de 450.00 = 63.00
    expect((float) $visita->valor_imposto)->toBe(63.00);

    // Custo operacional direto: Insumos (6) + Mão de obra (60) + Deslocamento (30) = 96.00
    expect($visita->custoOperacionalTotal())->toBe(96.00);

    // Lucro bruto: 450.00 - (96.00 + 63.00) = 291.00
    expect((float) $visita->lucro_bruto)->toBe(291.00);
});

test('formulario de cadastro e edicao de produto renderiza selects de unidades predefinidas', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $responseCreate = $this->actingAs($admin)->get(route('produtos.create'));
    $responseCreate->assertOk();
    $responseCreate->assertSee('select id="unidade_armazenamento"', false);
    $responseCreate->assertSee('select id="unidade_aplicacao"', false);
    $responseCreate->assertSee('L (Litro)');
    $responseCreate->assertSee('mL (Mililitro)');
    $responseCreate->assertSee('Kg (Quilograma)');
    $responseCreate->assertSee('Un (Unidade)');

    $produto = Produto::create([
        'nome' => 'Raticida Bloco',
        'natureza' => 'solido',
        'unidade' => 'Kg',
        'unidade_armazenamento' => 'Kg',
        'unidade_aplicacao' => 'g',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 50,
        'estoque_minimo' => 5,
        'preco_custo_unitario' => 45.00,
    ]);

    $responseEdit = $this->actingAs($admin)->get(route('produtos.edit', $produto));
    $responseEdit->assertOk();
    $responseEdit->assertSee('select id="unidade_armazenamento"', false);
    $responseEdit->assertSee('select id="unidade_aplicacao"', false);
    $responseEdit->assertSee('value="Kg" selected', false);
    $responseEdit->assertSee('value="g" selected', false);
});

test('admin pode cadastrar e editar produto utilizando unidades predefinidas', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $postData = [
        'nome' => 'Gel Baraticida Formiga',
        'natureza' => 'unidade',
        'unidade_armazenamento' => 'Un',
        'unidade_aplicacao' => 'Dose',
        'fator_conversao' => 30,
        'quantidade_estoque' => 15,
        'estoque_minimo' => 2,
        'preco_custo_unitario' => 38.50,
        'descricao' => 'Seringa aplicadora de 30g',
    ];

    $responseStore = $this->actingAs($admin)->post(route('produtos.store'), $postData);
    $responseStore->assertRedirect(route('produtos.index'));

    $produto = Produto::where('nome', 'Gel Baraticida Formiga')->first();
    expect($produto)->not->toBeNull();
    expect($produto->unidade_armazenamento)->toBe('Un');
    expect($produto->unidade_aplicacao)->toBe('Dose');
    expect((float) $produto->fator_conversao)->toBe(30.0);

    // Atualizar
    $updateData = [
        'nome' => 'Gel Baraticida Formiga Turbinado',
        'natureza' => 'liquido',
        'unidade_armazenamento' => 'L',
        'unidade_aplicacao' => 'mL',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 20,
        'estoque_minimo' => 3,
        'preco_custo_unitario' => 55.00,
        'descricao' => 'Versão líquida 1L',
    ];

    $responseUpdate = $this->actingAs($admin)->put(route('produtos.update', $produto), $updateData);
    $responseUpdate->assertRedirect(route('produtos.index'));

    $produto->refresh();
    expect($produto->unidade_armazenamento)->toBe('L');
    expect($produto->unidade_aplicacao)->toBe('mL');
    expect((float) $produto->fator_conversao)->toBe(1000.0);
});

test('produto liquido de 1L utilizado com 50ml na OS baixa estoque para 0,95L', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create(['nome' => 'Supermercado Central', 'cidade' => 'Campinas', 'estado' => 'SP']);
    $tecnico = Tecnico::create(['nome' => 'Marcos Lima', 'ativo' => true]);

    $produto = Produto::create([
        'nome' => 'Veneno Alpha Concentrado',
        'natureza' => 'liquido',
        'unidade' => 'L',
        'unidade_armazenamento' => 'L',
        'unidade_aplicacao' => 'mL',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 1.0000,
        'estoque_minimo' => 0.2000,
        'preco_custo_unitario' => 200.00,
    ]);

    $response = $this->actingAs($admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->format('Y-m-d\TH:i'),
        'status' => 'concluida',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 350.00,
        'produtos' => [
            ['produto_id' => $produto->id, 'quantidade' => 50],
        ],
    ]);

    $response->assertRedirect(route('visitas.index'));

    $produto->refresh();
    // 1 L - (50 mL / 1000) = 0.95 L
    expect((float) $produto->quantidade_estoque)->toBe(0.95);
    expect($produto->quantidade_estoque_formatada)->toBe('0,95');

    $visita = Visita::latest()->first();
    expect((float) $visita->custo_produtos)->toBe(10.00); // 0.05 * 200
});

test('produto em unidade ou pacote utilizado com meia unidade (0.5) na OS baixa estoque corretamente', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create(['nome' => 'Padaria Estrela', 'cidade' => 'Campinas', 'estado' => 'SP']);
    $tecnico = Tecnico::create(['nome' => 'Marcos Lima', 'ativo' => true]);

    // Produto cadastrado em Pacote (Pct)
    $produtoPct = Produto::create([
        'nome' => 'Pacote Iscas Parafinadas',
        'natureza' => 'unidade',
        'unidade' => 'Pct',
        'unidade_armazenamento' => 'Pct',
        'unidade_aplicacao' => 'Pct',
        'fator_conversao' => 1,
        'quantidade_estoque' => 1.0000,
        'estoque_minimo' => 0.5000,
        'preco_custo_unitario' => 50.00,
    ]);

    // Produto cadastrado em Unidade (Un)
    $produtoUn = Produto::create([
        'nome' => 'Porta-Iscas Especial',
        'natureza' => 'unidade',
        'unidade' => 'Un',
        'unidade_armazenamento' => 'Un',
        'unidade_aplicacao' => 'Un',
        'fator_conversao' => 1,
        'quantidade_estoque' => 1.0000,
        'estoque_minimo' => 0.5000,
        'preco_custo_unitario' => 20.00,
    ]);

    $response = $this->actingAs($admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->format('Y-m-d\TH:i'),
        'status' => 'concluida',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 400.00,
        'produtos' => [
            ['produto_id' => $produtoPct->id, 'quantidade' => 0.5],
            ['produto_id' => $produtoUn->id, 'quantidade' => 0.5],
        ],
    ]);

    $response->assertRedirect(route('visitas.index'));

    $produtoPct->refresh();
    $produtoUn->refresh();

    // 1.0 Pct - 0.5 Pct = 0.5 Pct
    expect((float) $produtoPct->quantidade_estoque)->toBe(0.5);
    expect($produtoPct->quantidade_estoque_formatada)->toBe('0,50');

    // 1.0 Un - 0.5 Un = 0.5 Un
    expect((float) $produtoUn->quantidade_estoque)->toBe(0.5);
    expect($produtoUn->quantidade_estoque_formatada)->toBe('0,50');

    $visita = Visita::latest()->first();
    // Custo: (0.5 * 50) + (0.5 * 20) = 25 + 10 = 35.00
    expect((float) $visita->custo_produtos)->toBe(35.00);
});

test('cadastro de produto calcula fator de conversao padrao automaticamente quando omitido', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->post(route('produtos.store'), [
        'nome' => 'Detergente Enzimático',
        'natureza' => 'liquido',
        'unidade_armazenamento' => 'L',
        'unidade_aplicacao' => 'mL',
        'quantidade_estoque' => 5,
        'estoque_minimo' => 1,
        'preco_custo_unitario' => 80.00,
    ]);

    $response->assertRedirect(route('produtos.index'));

    $prod = Produto::where('nome', 'Detergente Enzimático')->first();
    expect($prod)->not->toBeNull();
    expect((float) $prod->fator_conversao)->toBe(1000.0);
    expect($prod->unidade)->toBe('L');
});
