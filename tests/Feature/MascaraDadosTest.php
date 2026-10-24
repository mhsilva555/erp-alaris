<?php

use App\Models\Cliente;
use App\Models\Tecnico;
use App\Models\User;
use App\Support\MascaraHelper;

test('mascara helper limpa e formata corretamente documentos, telefones e ceps', function () {
    // Apenas números
    expect(MascaraHelper::apenasNumeros('123.456.789-00'))->toBe('12345678900');
    expect(MascaraHelper::apenasNumeros('(11) 98765-4321'))->toBe('11987654321');
    expect(MascaraHelper::apenasNumeros('01234-567'))->toBe('01234567');
    expect(MascaraHelper::apenasNumeros(null))->toBeNull();
    expect(MascaraHelper::apenasNumeros('   '))->toBeNull();

    // Formatação de CPF e CNPJ
    expect(MascaraHelper::formatarDocumento('12345678901'))->toBe('123.456.789-01');
    expect(MascaraHelper::formatarDocumento('12345678000195'))->toBe('12.345.678/0001-95');

    // Formatação de Telefone
    expect(MascaraHelper::formatarTelefone('11987654321'))->toBe('(11) 98765-4321');
    expect(MascaraHelper::formatarTelefone('1134567890'))->toBe('(11) 3456-7890');

    // Formatação de CEP
    expect(MascaraHelper::formatarCep('01234567'))->toBe('01234-567');
});

test('ao salvar cliente com mascara no frontend os dados sao salvos limpos no backend', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $dadosCliente = [
        'nome' => 'Empresa Teste Máscaras Ltda',
        'documento' => '12.345.678/0001-95', // CNPJ mascarado
        'telefone' => '(11) 98765-4321', // Celular mascarado
        'cep' => '01310-100', // CEP mascarado
        'cidade' => 'São Paulo',
        'estado' => 'SP',
        'origem' => 'Trafego Pago',
        'periodicidade' => 'mensal',
        'status_prospeccao' => 'ativo',
    ];

    $response = $this->actingAs($admin)->post(route('clientes.store'), $dadosCliente);
    $response->assertRedirect(route('clientes.index'));

    $cliente = Cliente::where('nome', 'Empresa Teste Máscaras Ltda')->first();
    expect($cliente)->not->toBeNull();

    // Dados limpos no banco de dados (sem pontuação)
    expect($cliente->documento)->toBe('12345678000195');
    expect($cliente->telefone)->toBe('11987654321');
    expect($cliente->cep)->toBe('01310100');

    // Accessors formatados
    expect($cliente->documento_formatado)->toBe('12.345.678/0001-95');
    expect($cliente->telefone_formatado)->toBe('(11) 98765-4321');
    expect($cliente->cep_formatado)->toBe('01310-100');
});

test('ao atualizar cliente com CPF mascarado os dados sao limpos e salvos', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create([
        'nome' => 'Pessoa Física',
        'cidade' => 'Campinas',
        'estado' => 'SP',
    ]);

    $response = $this->actingAs($admin)->put(route('clientes.update', $cliente), [
        'nome' => 'Pessoa Física Atualizada',
        'documento' => '123.456.789-09',
        'telefone' => '(19) 3232-4040',
        'cep' => '13010-000',
        'cidade' => 'Campinas',
        'estado' => 'SP',
    ]);
    $response->assertRedirect(route('clientes.index'));

    $cliente->refresh();
    expect($cliente->documento)->toBe('12345678909');
    expect($cliente->telefone)->toBe('1932324040');
    expect($cliente->cep)->toBe('13010000');
    expect($cliente->documento_formatado)->toBe('123.456.789-09');
    expect($cliente->telefone_formatado)->toBe('(19) 3232-4040');
});

test('ao cadastrar e atualizar tecnico o telefone e salvo limpo', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->post(route('tecnicos.store'), [
        'nome' => 'Técnico Especialista',
        'telefone' => '(11) 99887-7665',
        'email' => 'tecnico@alaris.com',
        'especialidade' => 'Controle de Roedores',
        'valor_diaria_padrao' => 60.00,
        'ativo' => '1',
    ]);
    $response->assertRedirect(route('tecnicos.index'));

    $tecnico = Tecnico::where('nome', 'Técnico Especialista')->first();
    expect($tecnico)->not->toBeNull();
    expect($tecnico->telefone)->toBe('11998877665');
    expect($tecnico->telefone_formatado)->toBe('(11) 99887-7665');
});

test('busca de clientes localiza registros tanto com pontuacao quanto com digitos puros', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    Cliente::create([
        'nome' => 'Cliente Busca Fácil',
        'documento' => '98765432100',
        'telefone' => '11912345678',
        'cidade' => 'Santos',
        'estado' => 'SP',
    ]);

    // Busca com pontuação: 987.654.321-00
    $resFormatado = $this->actingAs($admin)->get(route('clientes.index', ['busca' => '987.654.321-00']));
    $resFormatado->assertOk();
    $resFormatado->assertSee('Cliente Busca Fácil');

    // Busca por telefone com máscara: (11) 91234-5678
    $resTel = $this->actingAs($admin)->get(route('clientes.index', ['busca' => '(11) 91234-5678']));
    $resTel->assertOk();
    $resTel->assertSee('Cliente Busca Fácil');

    // Busca apenas números
    $resLimpo = $this->actingAs($admin)->get(route('clientes.index', ['busca' => '98765432100']));
    $resLimpo->assertOk();
    $resLimpo->assertSee('Cliente Busca Fácil');
});
