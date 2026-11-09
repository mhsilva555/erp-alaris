<?php

use App\Models\Cliente;
use App\Models\Configuracao;
use App\Models\Contrato;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->tecnico = User::factory()->create(['role' => User::ROLE_TECNICO]);
});

test('administrador pode visualizar e atualizar informacoes empresariais da alaris nas configuracoes', function () {
    $response = $this->actingAs($this->admin)->get(route('configuracoes.index'));
    $response->assertOk();
    $response->assertSee('Unidades da Empresa (Matriz & Filiais)', false);
    $response->assertSee('Parâmetros Operacionais & Custos Padrão', false);

    // Atualiza os dados da empresa
    $updateResponse = $this->actingAs($this->admin)->put(route('configuracoes.update'), [
        'aliquota_imposto_padrao' => '15.5',
        'diaria_tecnico_padrao' => '65.00',
        'empresa_razao_social' => 'ALARIS CONTROLE DE PRAGAS E VETORES LTDA',
        'empresa_nome_fantasia' => 'Alaris Dedetizadora & Sanitização',
        'empresa_cnpj' => '12.345.678/0001-90',
        'empresa_licenca_sanitaria' => 'Licença ANVISA 998877/2026',
        'empresa_telefone' => '(99) 98888-7777',
        'empresa_email' => 'comercial@alarisdedetizadora.com.br',
        'empresa_website' => 'www.alarisdedetizadora.com.br',
        'empresa_endereco' => 'Av. Getúlio Vargas, 1500',
        'empresa_bairro' => 'Nova Imperatriz',
        'empresa_cidade' => 'Imperatriz',
        'empresa_estado' => 'MA',
        'empresa_cep' => '65900-100',
        'empresa_slogan' => 'Excelência em Proteção Ambiental e Dedetização',
    ]);

    $updateResponse->assertRedirect(route('configuracoes.index'));
    $updateResponse->assertSessionHas('sucesso');

    // Verifica persistência nas configurações
    expect(Configuracao::get('empresa_razao_social'))->toBe('ALARIS CONTROLE DE PRAGAS E VETORES LTDA');
    expect(Configuracao::get('empresa_nome_fantasia'))->toBe('Alaris Dedetizadora & Sanitização');
    expect(Configuracao::get('empresa_cnpj'))->toBe('12.345.678/0001-90');
    expect(Configuracao::get('empresa_email'))->toBe('comercial@alarisdedetizadora.com.br');
    expect(Configuracao::get('empresa_website'))->toBe('www.alarisdedetizadora.com.br');

    $dados = Configuracao::dadosEmpresa();
    expect($dados['razaoSocial'])->toBe('ALARIS CONTROLE DE PRAGAS E VETORES LTDA');
    expect($dados['nomeFantasia'])->toBe('Alaris Dedetizadora & Sanitização');
    expect($dados['cnpj'])->toBe('12.345.678/0001-90');
    expect($dados['website'])->toBe('www.alarisdedetizadora.com.br');
    expect($dados['enderecoCompleto'])->toContain('Av. Getúlio Vargas, 1500');
});

test('informacoes empresariais atualizadas constam no cabecalho e assinaturas da proposta comercial e contrato', function () {
    Configuracao::set('empresa_razao_social', 'ALARIS SERVICE GESTAO AMBIENTAL LTDA');
    Configuracao::set('empresa_nome_fantasia', 'Alaris BioTech');
    Configuracao::set('empresa_cnpj', '77.888.999/0001-11');
    Configuracao::set('empresa_telefone', '(99) 97777-6666');
    Configuracao::set('empresa_email', 'atendimento@alarisbiotech.com.br');
    Configuracao::set('empresa_website', 'www.alarisbiotech.com.br');
    Configuracao::set('empresa_slogan', 'Biotecnologia em Higienização e Controle de Vetores');

    $cliente = Cliente::create(['nome' => 'Supermercado Central', 'cidade' => 'Imperatriz', 'estado' => 'MA']);

    $contrato = Contrato::create([
        'numero' => 'PROP-2026-9999',
        'cliente_id' => $cliente->id,
        'titulo' => 'Proposta Comercial Semestral',
        'status' => Contrato::STATUS_PROPOSTA,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'valor_total' => 950.00,
    ]);

    $response = $this->actingAs($this->admin)->get(route('contratos.pdf', $contrato));
    $response->assertOk();

    // Cabeçalho da Contratada
    $response->assertSee('Alaris BioTech');
    $response->assertSee('Biotecnologia em Higienização e Controle de Vetores');
    $response->assertSee('77.888.999/0001-11');
    $response->assertSee('(99) 97777-6666');
    $response->assertSee('atendimento@alarisbiotech.com.br');
    $response->assertSee('www.alarisbiotech.com.br');

    // Bloco de Assinaturas
    $response->assertSee('ALARIS SERVICE GESTAO AMBIENTAL LTDA');

    // Rodapé Institucional da Empresa
    $response->assertSee('footer-company');
    $response->assertSee('footer-address');
    $response->assertSee('atendimento@alarisbiotech.com.br');
});
