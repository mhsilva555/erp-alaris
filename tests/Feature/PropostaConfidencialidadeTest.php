<?php

use App\Models\Cliente;
use App\Models\Produto;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Visita;

test('garante a regra de ouro de sigilo comercial na proposta comercial para o cliente', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $cliente = Cliente::create([
        'nome' => 'Indústria Metalúrgica Alfa',
        'cidade' => 'Jundiaí',
        'estado' => 'SP',
    ]);

    $tecnico = Tecnico::create([
        'nome' => 'Marcos Aplicador',
        'valor_diaria_padrao' => 75.00,
        'ativo' => true,
    ]);

    $produto = Produto::create([
        'nome' => 'Veneno Químico Segredo X',
        'natureza' => 'liquido',
        'unidade' => 'L',
        'unidade_armazenamento' => 'L',
        'unidade_aplicacao' => 'mL',
        'fator_conversao' => 1000,
        'quantidade_estoque' => 50,
        'preco_custo_unitario' => 350.00,
        'ativo' => true,
    ]);

    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now(),
        'status' => 'concluida',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 1250.00,
        'custo_produtos' => 45.50,
        'custo_mao_de_obra' => 75.00,
        'custo_deslocamento' => 40.00,
        'exige_nota_fiscal' => true,
        'aliquota_imposto' => 14.0,
        'valor_imposto' => 175.00,
        'lucro_bruto' => 914.50,
    ]);

    $visita->produtos()->attach($produto->id, [
        'quantidade' => 130, // 130 mL
        'custo_proporcional' => 45.50,
    ]);

    $response = $this->actingAs($admin)->get(route('visitas.proposta', $visita));

    $response->assertOk();

    // DEVE conter o cliente, serviço prestado e o valor total final
    $response->assertSee('Indústria Metalúrgica Alfa');
    $response->assertSee('Controle de Pragas');
    $response->assertSee('1.250,00');

    // NUNCA deve exibir custos unitários internos, doses em mL de químicos, diária técnica, impostos ou lucro bruto
    $response->assertDontSee('Veneno Químico Segredo X');
    $response->assertDontSee('130 mL');
    $response->assertDontSee('45,50'); // custo insumos
    $response->assertDontSee('75,00'); // diária técnico
    $response->assertDontSee('40,00'); // deslocamento
    $response->assertDontSee('914,50'); // lucro bruto
    $response->assertDontSee('175,00'); // imposto retido
    $response->assertDontSee('14,0%');
});
