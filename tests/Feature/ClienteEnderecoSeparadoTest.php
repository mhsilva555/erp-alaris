<?php

use App\Models\Cliente;
use App\Models\User;

test('cadastra cliente com campos de endereco separados e formata endereco completo', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post(route('clientes.store'), [
        'nome' => 'Supermercado Central',
        'documento' => '12.345.678/0001-90',
        'telefone' => '(99) 98888-7777',
        'cep' => '65917-350',
        'logradouro' => 'Rua Maria Helena Coutinho',
        'numero' => '120',
        'complemento' => 'Galpão A',
        'bairro' => 'Parque Planalto',
        'cidade' => 'Imperatriz',
        'estado' => 'MA',
        'status_prospeccao' => 'ativo',
    ]);

    $response->assertRedirect(route('clientes.index'));

    $cliente = Cliente::where('nome', 'Supermercado Central')->first();
    expect($cliente)->not->toBeNull()
        ->and($cliente->cep)->toBe('65917350')
        ->and($cliente->logradouro)->toBe('Rua Maria Helena Coutinho')
        ->and($cliente->numero)->toBe('120')
        ->and($cliente->bairro)->toBe('Parque Planalto')
        ->and($cliente->complemento)->toBe('Galpão A')
        ->and($cliente->endereco)->toBe('Rua Maria Helena Coutinho, 120 (Galpão A) - Bairro Parque Planalto');
});

test('atualiza cliente alterando campos de endereco desacoplados', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cliente = Cliente::create([
        'nome' => 'Farmácia Popular',
        'logradouro' => 'Av. Brasil',
        'numero' => '10',
        'bairro' => 'Centro',
        'cidade' => 'Imperatriz',
        'estado' => 'MA',
        'cep' => '65900000',
    ]);

    $response = $this->actingAs($admin)->put(route('clientes.update', $cliente), [
        'nome' => 'Farmácia Popular',
        'logradouro' => 'Rua Ceará',
        'numero' => '500',
        'bairro' => 'Nova Imperatriz',
        'complemento' => 'Sala 02',
        'cidade' => 'Imperatriz',
        'estado' => 'MA',
        'cep' => '65905-100',
        'status_prospeccao' => 'ativo',
    ]);

    $response->assertRedirect(route('clientes.index'));

    $cliente->refresh();
    expect($cliente->logradouro)->toBe('Rua Ceará')
        ->and($cliente->numero)->toBe('500')
        ->and($cliente->bairro)->toBe('Nova Imperatriz')
        ->and($cliente->complemento)->toBe('Sala 02')
        ->and($cliente->cep)->toBe('65905100')
        ->and($cliente->endereco)->toBe('Rua Ceará, 500 (Sala 02) - Bairro Nova Imperatriz');
});

test('busca de cliente encontra registro filtrando por bairro ou logradouro', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Cliente::create([
        'nome' => 'Padaria Estrela',
        'logradouro' => 'Rua Getúlio Vargas',
        'numero' => '88',
        'bairro' => 'Bacuri',
        'cidade' => 'Imperatriz',
        'estado' => 'MA',
    ]);

    $responseBairro = $this->actingAs($admin)->get(route('clientes.index', ['busca' => 'Bacuri']));
    $responseBairro->assertStatus(200);
    $responseBairro->assertSee('Padaria Estrela');

    $responseLogradouro = $this->actingAs($admin)->get(route('clientes.index', ['busca' => 'Getúlio']));
    $responseLogradouro->assertStatus(200);
    $responseLogradouro->assertSee('Padaria Estrela');
});
