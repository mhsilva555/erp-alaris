<?php

use App\Models\Cliente;
use App\Models\EquipamentoComodato;
use App\Models\Produto;
use App\Models\User;

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
