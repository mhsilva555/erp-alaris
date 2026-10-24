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
