<?php

use App\Models\Cliente;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\User;
use App\Models\Visita;

test('catalogo de permissoes possui permissoes comuns para todos os modulos e especificas', function () {
    $catalogo = Permissao::catalogoModulos();

    expect($catalogo)->toHaveKeys([
        'clientes',
        'equipamentos',
        'comodatos',
        'visitas',
        'produtos',
        'agendamentos',
        'tecnicos',
        'financeiro',
        'configuracoes',
        'perfis',
        'usuarios',
    ]);

    // Cada módulo deve conter as 5 permissões comuns: ver, criar, editar, deletar, acessar_dados
    foreach ($catalogo as $modulo => $info) {
        expect($info['permissoes'])->toHaveKey("{$modulo}.ver");
        expect($info['permissoes'])->toHaveKey("{$modulo}.criar");
        expect($info['permissoes'])->toHaveKey("{$modulo}.editar");
        expect($info['permissoes'])->toHaveKey("{$modulo}.deletar");
        expect($info['permissoes'])->toHaveKey("{$modulo}.acessar_dados");
    }

    // Módulos com permissões específicas
    expect($catalogo['visitas']['permissoes'])->toHaveKey('visitas.finalizar');
    expect($catalogo['visitas']['permissoes'])->toHaveKey('visitas.gerar_os');
    expect($catalogo['visitas']['permissoes'])->toHaveKey('visitas.proposta_comercial');
    expect($catalogo['comodatos']['permissoes'])->toHaveKey('comodatos.registrar_reposicao');
    expect($catalogo['financeiro']['permissoes'])->toHaveKey('financeiro.relatorio_dre');
    expect($catalogo['financeiro']['permissoes'])->toHaveKey('financeiro.exportar_pdf');
});

test('seeder cadastra os 4 perfis iniciais predefinidos e suas permissoes', function () {
    $this->artisan('db:seed', ['--class' => 'PerfilPermissaoSeeder']);

    $admin = Perfil::where('slug', 'admin')->first();
    $tecnico = Perfil::where('slug', 'tecnico')->first();
    $gerente = Perfil::where('slug', 'gerente_operacional')->first();
    $financeiro = Perfil::where('slug', 'financeiro')->first();

    expect($admin)->not->toBeNull();
    expect($admin->is_sistema)->toBeTrue();
    expect($admin->temPermissao('visitas.proposta_comercial'))->toBeTrue();

    expect($tecnico)->not->toBeNull();
    expect($tecnico->is_sistema)->toBeTrue();
    expect($tecnico->temPermissao('visitas.finalizar'))->toBeTrue();
    expect($tecnico->temPermissao('financeiro.relatorio_dre'))->toBeFalse();

    expect($gerente)->not->toBeNull();
    expect($gerente->temPermissao('clientes.criar'))->toBeTrue();
    expect($gerente->temPermissao('financeiro.relatorio_dre'))->toBeFalse();

    expect($financeiro)->not->toBeNull();
    expect($financeiro->temPermissao('financeiro.relatorio_dre'))->toBeTrue();
    expect($financeiro->temPermissao('financeiro.exportar_pdf'))->toBeTrue();
    expect($financeiro->temPermissao('produtos.criar'))->toBeFalse();
});

test('administrador pode visualizar listagem de perfis', function () {
    $this->artisan('db:seed', ['--class' => 'PerfilPermissaoSeeder']);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->get(route('perfis.index'));

    $response->assertOk();
    $response->assertSee('Perfis de Acesso & Permissões');
    $response->assertSee('Administrador');
    $response->assertSee('Técnico de Campo');
});

test('administrador pode cadastrar um novo perfil personalizado com permissoes selecionadas', function () {
    $this->artisan('db:seed', ['--class' => 'PerfilPermissaoSeeder']);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->post(route('perfis.store'), [
        'nome' => 'Atendente Comercial',
        'descricao' => 'Responsável por cadastrar clientes e abrir visitas comerciais.',
        'permissoes' => [
            'clientes.ver',
            'clientes.criar',
            'clientes.editar',
            'visitas.ver',
            'visitas.criar',
        ],
    ]);

    $response->assertRedirect(route('perfis.index'));
    $response->assertSessionHas('sucesso');

    $perfil = Perfil::where('nome', 'Atendente Comercial')->first();
    expect($perfil)->not->toBeNull();
    expect($perfil->is_sistema)->toBeFalse();
    expect($perfil->temPermissao('clientes.criar'))->toBeTrue();
    expect($perfil->temPermissao('visitas.criar'))->toBeTrue();
    expect($perfil->temPermissao('financeiro.relatorio_dre'))->toBeFalse();
    expect($perfil->temPermissao('clientes.deletar'))->toBeFalse();
});

test('administrador pode editar um perfil e atualizar suas permissoes', function () {
    $this->artisan('db:seed', ['--class' => 'PerfilPermissaoSeeder']);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $perfil = Perfil::create([
        'nome' => 'Auxiliar de Almoxarifado',
        'descricao' => 'Controle de entrada e saída de defensivos.',
        'is_sistema' => false,
    ]);
    $perfil->sincronizarPermissoes(['produtos.ver']);

    expect($perfil->temPermissao('produtos.ver'))->toBeTrue();
    expect($perfil->temPermissao('produtos.ajustar_estoque'))->toBeFalse();

    $response = $this->actingAs($admin)->put(route('perfis.update', $perfil), [
        'nome' => 'Auxiliar de Almoxarifado Sênior',
        'descricao' => 'Controle total de produtos e ajuste de estoque.',
        'permissoes' => [
            'produtos.ver',
            'produtos.criar',
            'produtos.ajustar_estoque',
        ],
    ]);

    $response->assertRedirect(route('perfis.index'));
    $perfil->refresh();

    expect($perfil->nome)->toBe('Auxiliar de Almoxarifado Sênior');
    expect($perfil->temPermissao('produtos.ajustar_estoque'))->toBeTrue();
    expect($perfil->temPermissao('produtos.criar'))->toBeTrue();
});

test('bloqueia exclusao de perfis nativos do sistema ou perfis com usuarios vinculados', function () {
    $this->artisan('db:seed', ['--class' => 'PerfilPermissaoSeeder']);
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $perfilAdmin = Perfil::where('slug', 'admin')->first();
    $response = $this->actingAs($adminUser)->delete(route('perfis.destroy', $perfilAdmin));
    $response->assertSessionHasErrors('error');
    expect(Perfil::where('slug', 'admin')->exists())->toBeTrue();

    // Perfil personalizado com usuário vinculado
    $perfilCustom = Perfil::create(['nome' => 'Inspetor de Qualidade', 'is_sistema' => false]);
    User::factory()->create(['perfil_id' => $perfilCustom->id]);

    $responseCustom = $this->actingAs($adminUser)->delete(route('perfis.destroy', $perfilCustom));
    $responseCustom->assertSessionHasErrors('error');
    expect(Perfil::where('id', $perfilCustom->id)->exists())->toBeTrue();
});

test('usuario com perfil personalizado valida permissoes granulares via temPermissao e middleware', function () {
    $this->artisan('db:seed', ['--class' => 'PerfilPermissaoSeeder']);

    $perfil = Perfil::create(['nome' => 'Operador de Campo Especial', 'slug' => 'operador_especial']);
    $perfil->sincronizarPermissoes([
        'visitas.ver',
        'visitas.finalizar',
        'clientes.ver',
    ]);

    $usuario = User::factory()->create([
        'role' => 'operador_especial',
        'perfil_id' => $perfil->id,
    ]);

    expect($usuario->temPermissao('visitas.ver'))->toBeTrue();
    expect($usuario->temPermissao('visitas.finalizar'))->toBeTrue();
    expect($usuario->temPermissao('financeiro.relatorio_dre'))->toBeFalse();
    expect($usuario->temPermissao('visitas.proposta_comercial'))->toBeFalse();

    // Teste de autorização em rota com middleware de permissão
    $responseNegada = $this->actingAs($usuario)->get(route('perfis.index'));
    $responseNegada->assertForbidden();
});

test('frontend oculta e exibe botoes e elementos confidenciais conforme permissoes granulares', function () {
    $this->artisan('db:seed', ['--class' => 'PerfilPermissaoSeeder']);

    $perfilLimitado = Perfil::create(['nome' => 'Visualizador', 'slug' => 'visualizador']);
    $perfilLimitado->sincronizarPermissoes([
        'clientes.ver',
        'visitas.ver',
    ]);

    $usuarioLimitado = User::factory()->create([
        'role' => 'visualizador',
        'perfil_id' => $perfilLimitado->id,
    ]);

    $cliente = Cliente::create(['nome' => 'Empresa Alfa Ltda', 'documento' => '12345678901234']);
    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'data_hora' => now(),
        'status' => 'agendada',
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
        'valor_cobrado' => 1500.00,
    ]);

    // 1. Usuário sem clientes.criar não visualiza botão "Novo" de cadastro
    $responseCli = $this->actingAs($usuarioLimitado)->get(route('clientes.index'));
    $responseCli->assertOk();
    $responseCli->assertDontSee(route('clientes.create'));

    // 2. Usuário sem visitas.acessar_dados não visualiza coluna de "Valor / Lucro"
    $responseVis = $this->actingAs($usuarioLimitado)->get(route('visitas.index'));
    $responseVis->assertOk();
    $responseVis->assertDontSee('Valor / Lucro');

    // 3. Usuário sem permissões financeiras não visualiza links de gestão financeira no menu
    $responseLayout = $this->actingAs($usuarioLimitado)->get(route('clientes.index'));
    $responseLayout->assertDontSee('Fluxo de Caixa');
    $responseLayout->assertDontSee('Lucro Bruto & DRE');

    // 4. Ao conceder clientes.criar e visitas.acessar_dados, os elementos passam a ser renderizados
    $perfilLimitado->sincronizarPermissoes([
        'clientes.ver',
        'clientes.criar',
        'visitas.ver',
        'visitas.acessar_dados',
    ]);

    $responseCliPermitido = $this->actingAs($usuarioLimitado->fresh())->get(route('clientes.index'));
    $responseCliPermitido->assertOk();
    $responseCliPermitido->assertSee(route('clientes.create'));

    $responseVisPermitido = $this->actingAs($usuarioLimitado->fresh())->get(route('visitas.index'));
    $responseVisPermitido->assertOk();
    $responseVisPermitido->assertSee('Valor / Lucro');
});
