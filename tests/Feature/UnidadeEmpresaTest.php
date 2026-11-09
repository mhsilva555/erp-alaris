<?php

declare(strict_types=1);

use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\Tecnico;
use App\Models\UnidadeEmpresa;
use App\Models\User;
use App\Models\Visita;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->tecnico = Tecnico::create(['nome' => 'Técnico Campo', 'ativo' => true]);
});

test('1. cadastro e gestao de unidades da empresa (matriz e filiais) com persistencia limpa', function () {
    $resMatriz = $this->actingAs($this->admin)->post(route('unidades.store'), [
        'nome' => 'Matriz Imperatriz',
        'tipo' => UnidadeEmpresa::TIPO_MATRIZ,
        'razao_social' => 'ALARIS SERVICE MATRIZ LTDA',
        'nome_fantasia' => 'Alaris Service Matriz',
        'cnpj' => '38.628.132/0001-35',
        'licenca_sanitaria' => 'Alvará Sanitário Imperatriz 001/2026',
        'telefone' => '(99) 98888-1111',
        'email' => 'matriz@alarisservice.com.br',
        'website' => 'www.alarisservice.com.br',
        'slogan' => 'Controle de Pragas & Higienização - Matriz',
        'cep' => '65912-060',
        'endereco' => 'R. Frei Epifânio da Abadia, 100',
        'bairro' => 'Centro',
        'cidade' => 'Imperatriz',
        'estado' => 'ma',
        'padrao' => '1',
        'ativo' => '1',
        'ativo_submitted' => '1',
    ]);

    $resMatriz->assertRedirect(route('unidades.index'));

    $matriz = UnidadeEmpresa::where('nome', 'Matriz Imperatriz')->first();
    expect($matriz)->not->toBeNull();
    expect($matriz->tipo)->toBe(UnidadeEmpresa::TIPO_MATRIZ);
    expect($matriz->cnpj)->toBe('38628132000135');
    expect($matriz->cnpj_formatado)->toBe('38.628.132/0001-35');
    expect($matriz->telefone)->toBe('99988881111');
    expect($matriz->cep)->toBe('65912060');
    expect($matriz->estado)->toBe('MA');
    expect($matriz->padrao)->toBeTrue();

    // Cadastra Filial
    $resFilial = $this->actingAs($this->admin)->post(route('unidades.store'), [
        'nome' => 'Filial Açailândia',
        'tipo' => UnidadeEmpresa::TIPO_FILIAL,
        'razao_social' => 'ALARIS SERVICE FILIAL ACAILANDIA LTDA',
        'nome_fantasia' => 'Alaris Service Açailândia',
        'cnpj' => '38.628.132/0002-16',
        'licenca_sanitaria' => 'Licença Ambiental Açailândia 202/2026',
        'telefone' => '(99) 97777-2222',
        'email' => 'acailandia@alarisservice.com.br',
        'website' => 'www.alarisservice.com.br',
        'cep' => '65930-000',
        'endereco' => 'Av. Santa Luzia, 550',
        'bairro' => 'Centro',
        'cidade' => 'Açailândia',
        'estado' => 'MA',
        'ativo' => '1',
        'ativo_submitted' => '1',
    ]);

    $resFilial->assertRedirect(route('unidades.index'));

    $filial = UnidadeEmpresa::where('nome', 'Filial Açailândia')->first();
    expect($filial)->not->toBeNull();
    expect($filial->ehMatriz())->toBeFalse();
    expect($filial->padrao)->toBeFalse();
    expect($matriz->fresh()->padrao)->toBeTrue();

    // Listagem exibe ambas as unidades
    $indexView = $this->actingAs($this->admin)->get(route('unidades.index'));
    $indexView->assertOk();
    $indexView->assertSee('Matriz Imperatriz');
    $indexView->assertSee('Filial Açailândia');
});

test('2. selecao de unidade (matriz ou filial) na proposta comercial e contrato grava no banco e reflete no cabecalho e rodape do pdf', function () {
    $matriz = UnidadeEmpresa::create([
        'nome' => 'Matriz Imperatriz',
        'tipo' => UnidadeEmpresa::TIPO_MATRIZ,
        'razao_social' => 'ALARIS MATRIZ IMPERATRIZ LTDA',
        'nome_fantasia' => 'Alaris Matriz',
        'cnpj' => '38.628.132/0001-35',
        'licenca_sanitaria' => 'Licença Matriz ITZ 100',
        'telefone' => '(99) 98111-0001',
        'email' => 'matriz@alaris.com.br',
        'endereco' => 'Rua Principal Matriz, 10',
        'bairro' => 'Centro',
        'cidade' => 'Imperatriz',
        'estado' => 'MA',
        'padrao' => true,
        'ativo' => true,
    ]);

    $filial = UnidadeEmpresa::create([
        'nome' => 'Filial Balsas',
        'tipo' => UnidadeEmpresa::TIPO_FILIAL,
        'razao_social' => 'ALARIS FILIAL SUL BALSAS LTDA',
        'nome_fantasia' => 'Alaris Filial Balsas',
        'cnpj' => '38.628.132/0003-05',
        'licenca_sanitaria' => 'Licença Filial Balsas 999',
        'telefone' => '(99) 98222-0002',
        'email' => 'balsas@alaris.com.br',
        'endereco' => 'Av. do Agronegócio, 800',
        'bairro' => 'Industrial',
        'cidade' => 'Balsas',
        'estado' => 'MA',
        'padrao' => false,
        'ativo' => true,
    ]);

    $cliente = Cliente::create(['nome' => 'Agroindustrial Sul Maranhão', 'cidade' => 'Balsas', 'estado' => 'MA']);

    // Cria proposta selecionando a Filial Balsas
    $resStore = $this->actingAs($this->admin)->post(route('contratos.store'), [
        'numero' => 'PROP-2026-0500',
        'cliente_id' => $cliente->id,
        'unidade_id' => $filial->id,
        'titulo' => 'Controle de Pragas em Silos',
        'status' => Contrato::STATUS_PROPOSTA,
        'tipo_vigencia' => Contrato::VIGENCIA_INDETERMINADO,
        'data_inicio' => now()->toDateString(),
        'periodicidade' => Contrato::PERIODICIDADE_MENSAL,
        'tipo_servico' => 'controle_pragas',
        'modelo_cobranca' => Contrato::MODELO_MENSAL,
        'itens' => [
            [
                'descricao' => 'Desratização e Monitoramento',
                'quantidade' => 1,
                'valor_unitario' => '1500,00',
            ],
        ],
    ]);

    $resStore->assertSessionHasNoErrors();

    $contrato = Contrato::where('numero', 'PROP-2026-0500')->first();
    expect($contrato)->not->toBeNull();
    // Verifica que a unidade selecionada ficou registrada no banco de dados
    expect($contrato->unidade_id)->toBe($filial->id);

    // Verifica que o PDF exibe os dados da Filial Balsas no cabeçalho, assinatura e rodapé
    $pdfView = $this->actingAs($this->admin)->get(route('contratos.pdf', $contrato));
    $pdfView->assertOk();
    $pdfView->assertSee('Alaris Filial Balsas');
    $pdfView->assertSee('ALARIS FILIAL SUL BALSAS LTDA');
    $pdfView->assertSee('38.628.132/0003-05');
    $pdfView->assertSee('Av. do Agronegócio, 800');
    $pdfView->assertSee('Licença Filial Balsas 999');
    $pdfView->assertDontSee('ALARIS MATRIZ IMPERATRIZ LTDA');

    // Agora troca a unidade direto na geração do PDF (?unidade_id=matriz) e verifica que atualiza no banco e no PDF
    $pdfTroca = $this->actingAs($this->admin)->get(route('contratos.pdf', [$contrato, 'unidade_id' => $matriz->id]));
    $pdfTroca->assertOk();
    $pdfTroca->assertSee('Alaris Matriz');
    $pdfTroca->assertSee('ALARIS MATRIZ IMPERATRIZ LTDA');
    $pdfTroca->assertSee('38.628.132/0001-35');
    $pdfTroca->assertSee('Licença Matriz ITZ 100');

    expect($contrato->fresh()->unidade_id)->toBe($matriz->id);
});

test('3. selecao de unidade na ordem de servico (OS) grava no banco e reflete no cabecalho e rodape da OS', function () {
    $matriz = UnidadeEmpresa::create([
        'nome' => 'Matriz Imperatriz',
        'tipo' => UnidadeEmpresa::TIPO_MATRIZ,
        'razao_social' => 'ALARIS MATRIZ LTDA',
        'nome_fantasia' => 'Alaris Matriz',
        'cnpj' => '38.628.132/0001-35',
        'endereco' => 'Rua Matriz, 100',
        'cidade' => 'Imperatriz',
        'estado' => 'MA',
        'padrao' => true,
        'ativo' => true,
    ]);

    $filial = UnidadeEmpresa::create([
        'nome' => 'Filial Marabá',
        'tipo' => UnidadeEmpresa::TIPO_FILIAL,
        'razao_social' => 'ALARIS FILIAL MARABA PARA LTDA',
        'nome_fantasia' => 'Alaris Filial Marabá',
        'cnpj' => '38.628.132/0004-88',
        'licenca_sanitaria' => 'Alvará Vigilância Marabá 777',
        'telefone' => '(94) 99111-2233',
        'email' => 'maraba@alaris.com.br',
        'endereco' => 'Folha 27, Quadra 10',
        'bairro' => 'Nova Marabá',
        'cidade' => 'Marabá',
        'estado' => 'PA',
        'padrao' => false,
        'ativo' => true,
    ]);

    $cliente = Cliente::create(['nome' => 'Siderúrgica Carajás', 'cidade' => 'Marabá', 'estado' => 'PA']);

    // Cria OS selecionando a Filial Marabá
    $resOs = $this->actingAs($this->admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'unidade_id' => $filial->id,
        'tecnico_id' => $this->tecnico->id,
        'data_hora' => now()->format('Y-m-d 10:00:00'),
        'status' => Visita::STATUS_AGENDADA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => '850,00',
    ]);

    $resOs->assertSessionHasNoErrors();

    $visita = Visita::where('cliente_id', $cliente->id)->first();
    expect($visita)->not->toBeNull();
    // Unidade registrada no banco de dados
    expect($visita->unidade_id)->toBe($filial->id);

    // Folha de O.S. exibe cabeçalho, assinatura e rodapé da Filial Marabá
    $osPdfView = $this->actingAs($this->admin)->get(route('visitas.os', $visita));
    $osPdfView->assertOk();
    $osPdfView->assertSee('Alaris Filial Marabá');
    $osPdfView->assertSee('ALARIS FILIAL MARABA PARA LTDA');
    $osPdfView->assertSee('38.628.132/0004-88');
    $osPdfView->assertSee('Folha 27, Quadra 10');
    $osPdfView->assertSee('Alvará Vigilância Marabá 777');
    $osPdfView->assertDontSee('ALARIS MATRIZ LTDA');

    // Troca a unidade direto na geração da folha de O.S. (?unidade_id=matriz)
    $osTroca = $this->actingAs($this->admin)->get(route('visitas.os', [$visita, 'unidade_id' => $matriz->id]));
    $osTroca->assertOk();
    $osTroca->assertSee('Alaris Matriz');
    $osTroca->assertSee('ALARIS MATRIZ LTDA');
    $osTroca->assertSee('38.628.132/0001-35');

    expect($visita->fresh()->unidade_id)->toBe($matriz->id);
});
