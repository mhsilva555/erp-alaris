<?php

use App\Models\Cliente;
use App\Models\Configuracao;
use App\Models\MovimentacaoFinanceira;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Visita;

test('sincroniza movimentacoes de caixa ao criar, editar, cancelar e excluir ordem de servico', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    Configuracao::set('aliquota_imposto_padrao', '14.0');

    $cliente = Cliente::create([
        'nome' => 'Supermercado Rio Anil',
        'cidade' => 'São Luís',
        'estado' => 'MA',
    ]);

    $tecnico = Tecnico::create([
        'nome' => 'Carlos Alberto',
        'valor_diaria_padrao' => 80.00,
        'ativo' => true,
    ]);

    $dataOS = now()->subDays(5);

    // 1. Cria OS concluída via controller
    $response = $this->actingAs($admin)->post(route('visitas.store'), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => $dataOS->format('Y-m-d H:i'),
        'status' => Visita::STATUS_CONCLUIDA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
        'valor_cobrado' => 500.00,
        'custo_mao_de_obra' => 80.00,
        'custo_deslocamento' => 30.00,
        'gastos_adicionais' => 20.00,
        'motivo_gastos_adicionais' => 'Conexão extra',
    ]);

    $response->assertRedirect(route('visitas.index'));

    $visita = Visita::where('cliente_id', $cliente->id)->first();
    expect($visita)->not->toBeNull();

    // Verifica que as movimentações foram geradas com a data_competencia da OS
    $movReceita = MovimentacaoFinanceira::where('visita_id', $visita->id)
        ->where('tipo', MovimentacaoFinanceira::TIPO_RECEITA)
        ->first();

    expect($movReceita)->not->toBeNull();
    expect((float) $movReceita->valor)->toBe(500.00);
    expect($movReceita->data_competencia->toDateString())->toBe($dataOS->toDateString());
    expect($movReceita->status)->toBe(MovimentacaoFinanceira::STATUS_PENDENTE);

    $movDiaria = MovimentacaoFinanceira::where('visita_id', $visita->id)
        ->where('tipo', MovimentacaoFinanceira::TIPO_DESPESA)
        ->whereIn('categoria', ['mao_de_obra', 'diaria_tecnico'])
        ->first();

    expect($movDiaria)->not->toBeNull();
    expect((float) $movDiaria->valor)->toBe(80.00);
    expect($movDiaria->data_competencia->toDateString())->toBe($dataOS->toDateString());

    $movGastos = MovimentacaoFinanceira::where('visita_id', $visita->id)
        ->where('tipo', MovimentacaoFinanceira::TIPO_DESPESA)
        ->where('descricao', 'like', 'Gastos adicionais OS #%')
        ->first();

    expect($movGastos)->not->toBeNull();
    expect((float) $movGastos->valor)->toBe(20.00);

    // 2. Atualiza a OS alterando valores de faturamento e diária
    $responseUpdate = $this->actingAs($admin)->put(route('visitas.update', $visita), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => $dataOS->format('Y-m-d H:i'),
        'status' => Visita::STATUS_CONCLUIDA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
        'valor_cobrado' => 750.00, // aumentado de 500 para 750
        'custo_mao_de_obra' => 100.00, // alterado para 100
        'custo_deslocamento' => 30.00,
        'gastos_adicionais' => 35.00, // alterado para 35
        'motivo_gastos_adicionais' => 'Conexão e abraçadeiras extras',
    ]);

    $responseUpdate->assertRedirect(route('visitas.show', $visita));

    // Garante que não houve duplicação de lançamentos e que os valores foram devidamente atualizados
    $totalMovs = MovimentacaoFinanceira::where('visita_id', $visita->id)->count();
    expect($totalMovs)->toBe(3);

    $movReceita->refresh();
    expect((float) $movReceita->valor)->toBe(750.00);

    $movDiaria->refresh();
    expect((float) $movDiaria->valor)->toBe(100.00);

    $movGastos->refresh();
    expect((float) $movGastos->valor)->toBe(35.00);
    expect($movGastos->descricao)->toContain('Conexão e abraçadeiras extras');

    // 3. Testa liquidação de receita e visualização na conciliação do DRE
    $movReceita->update(['status' => MovimentacaoFinanceira::STATUS_PAGO]);

    $mesFiltro = $dataOS->format('Y-m');

    $responseRelatorio = $this->actingAs($admin)->get(route('financeiro.relatorio', ['mes' => $mesFiltro]));
    $responseRelatorio->assertOk();
    $responseRelatorio->assertSee('Auditoria & Conciliação', false);
    $responseRelatorio->assertSee('DRE (Competência) vs Fluxo de Caixa (Realizado)', false);
    $responseRelatorio->assertSee('Recebido'); // badge na tabela
    $responseRelatorio->assertSee('R$ 750,00');

    // 4. Testa visualização da conciliação no Fluxo de Caixa
    $responseFluxo = $this->actingAs($admin)->get(route('financeiro.fluxo', ['mes' => $mesFiltro]));
    $responseFluxo->assertOk();
    $responseFluxo->assertSee('Conciliação com Ordens de Serviço');
    $responseFluxo->assertSee('Lucro Bruto & DRE', false);

    // 5. Cancela a visita e verifica que movimentações pendentes são limpas
    $movDiaria->update(['status' => MovimentacaoFinanceira::STATUS_PENDENTE]);

    $this->actingAs($admin)->put(route('visitas.update', $visita), [
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => $dataOS->format('Y-m-d H:i'),
        'status' => Visita::STATUS_CANCELADA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
        'valor_cobrado' => 750.00,
        'custo_mao_de_obra' => 100.00,
        'custo_deslocamento' => 30.00,
        'gastos_adicionais' => 0,
    ]);

    // A diária pendente foi expurgada do fluxo para não poluir
    expect(MovimentacaoFinanceira::where('id', $movDiaria->id)->exists())->toBeFalse();

    // 6. Ao excluir a visita, nenhuma movimentação orfã deve sobrar com visita_id = null
    $this->actingAs($admin)->delete(route('visitas.destroy', $visita));

    expect(MovimentacaoFinanceira::where('visita_id', $visita->id)->exists())->toBeFalse();
});

test('gera relatorio pdf com conciliacao e status de recebimento sem erros', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $cliente = Cliente::create(['nome' => 'Padaria Real', 'cidade' => 'Caxias', 'estado' => 'MA']);
    $tecnico = Tecnico::create(['nome' => 'Marcos Dias', 'ativo' => true]);

    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now(),
        'status' => Visita::STATUS_CONCLUIDA,
        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
        'tipo_servico' => Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
        'valor_cobrado' => 450.00,
        'custo_mao_de_obra' => 50.00,
        'custo_deslocamento' => 20.00,
        'gastos_adicionais' => 15.00,
        'motivo_gastos_adicionais' => 'Tubo flexível',
        'lucro_bruto' => 365.00,
    ]);

    MovimentacaoFinanceira::create([
        'visita_id' => $visita->id,
        'tipo' => MovimentacaoFinanceira::TIPO_RECEITA,
        'categoria' => 'faturamento_servico',
        'descricao' => "Faturamento OS #{$visita->id} - {$cliente->nome}",
        'valor' => 450.00,
        'data_competencia' => now()->toDateString(),
        'status' => MovimentacaoFinanceira::STATUS_PAGO,
    ]);

    $response = $this->actingAs($admin)->get(route('financeiro.relatorio.pdf', ['mes' => now()->format('Y-m')]));
    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});
