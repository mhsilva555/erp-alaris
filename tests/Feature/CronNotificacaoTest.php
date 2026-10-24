<?php

use App\Models\Cliente;
use App\Models\Configuracao;
use App\Models\EquipamentoComodato;
use App\Models\NotificacaoAgendamento;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Support\Facades\Artisan;

test('comando cron:install registra a instalacao da cron e verifica status', function () {
    $exitCode = Artisan::call('cron:install');
    expect($exitCode)->toBe(0);

    expect(Configuracao::get('cron_instalada'))->toBe('true');
    expect(Configuracao::get('cron_instalada_em'))->not->toBeNull();

    // Consulta de status
    $statusExit = Artisan::call('cron:install', ['--status' => true]);
    expect($statusExit)->toBe(0);
});

test('agendamentos:verificar-notificacoes identifica visitas iminentes, comodatos e followups', function () {
    $cliente = Cliente::create([
        'nome' => 'Supermercado Central',
        'cidade' => 'São Paulo',
        'estado' => 'SP',
        'data_followup' => now()->toDateString(),
        'status_prospeccao' => Cliente::STATUS_RETORNAR_3_MESES,
    ]);

    $tecnico = Tecnico::create([
        'nome' => 'Carlos Silva',
        'ativo' => true,
    ]);

    // Visita marcada para daqui a 30 minutos (dentro da janela de 60 minutos)
    $visita = Visita::create([
        'cliente_id' => $cliente->id,
        'tecnico_id' => $tecnico->id,
        'data_hora' => now()->addMinutes(30),
        'status' => Visita::STATUS_AGENDADA,
        'tipo_visita' => 'execucao',
        'tipo_servico' => 'controle_pragas',
    ]);

    // Comodato com reposição programada para hoje
    $comodato = EquipamentoComodato::create([
        'cliente_id' => $cliente->id,
        'nome_equipamento' => 'Túnel Porta-Iscas',
        'quantidade' => 4,
        'data_instalacao' => now()->subDays(15),
        'periodicidade_dias' => 15,
        'data_proxima_reposicao' => now()->toDateString(),
    ]);

    // Executa a verificação via Artisan
    $exitCode = Artisan::call('agendamentos:verificar-notificacoes');
    expect($exitCode)->toBe(0);

    // Deve ter gerado 3 notificações no banco
    $notificacoes = NotificacaoAgendamento::all();
    expect($notificacoes->count())->toBe(3);

    $notifVisita = $notificacoes->firstWhere('tipo_evento', 'visita');
    expect($notifVisita)->not->toBeNull();
    expect($notifVisita->evento_id)->toBe($visita->id);
    expect($notifVisita->titulo)->toContain('Visita Agendada');
    expect($notifVisita->titulo)->toContain('Supermercado Central');

    $notifComodato = $notificacoes->firstWhere('tipo_evento', 'comodato');
    expect($notifComodato)->not->toBeNull();
    expect($notifComodato->evento_id)->toBe($comodato->id);
    expect($notifComodato->titulo)->toContain('Túnel Porta-Iscas');

    $notifFollowup = $notificacoes->firstWhere('tipo_evento', 'followup');
    expect($notifFollowup)->not->toBeNull();
    expect($notifFollowup->evento_id)->toBe($cliente->id);
    expect($notifFollowup->titulo)->toContain('Follow-up Comercial');
});

test('api de notificacoes pendentes entrega registros e permite marcar como exibida no SO', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $notif = NotificacaoAgendamento::create([
        'tipo_evento' => 'visita',
        'evento_id' => 99,
        'titulo' => 'Alerta de Visita Próxima',
        'mensagem' => 'A visita se inicia em 20 minutos',
        'url' => '/visitas/99',
        'data_evento' => now()->addMinutes(20),
        'enviada_so' => false,
    ]);

    // Consulta pendentes autenticado
    $response = $this->actingAs($user)->getJson(route('notificacoes.agendamentos.pendentes'));
    $response->assertOk();
    $response->assertJsonPath('sucesso', true);
    $response->assertJsonPath('total', 1);
    $response->assertJsonFragment(['titulo' => 'Alerta de Visita Próxima']);

    // Marca como exibida
    $responseMarcar = $this->actingAs($user)->postJson(route('notificacoes.agendamentos.marcar-exibida', $notif));
    $responseMarcar->assertOk();

    expect($notif->fresh()->enviada_so)->toBeTrue();

    // Consulta pendentes novamente - agora deve vir vazio
    $responseVazio = $this->actingAs($user)->getJson(route('notificacoes.agendamentos.pendentes'));
    $responseVazio->assertJsonPath('total', 0);
});

test('usuario autenticado pode testar notificacao no sistema operacional', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($user)->postJson(route('notificacoes.agendamentos.testar'));
    $response->assertOk();
    $response->assertJsonPath('sucesso', true);

    $notif = NotificacaoAgendamento::where('titulo', 'like', '%ERP Alaris%')->latest()->first();
    expect($notif)->not->toBeNull();
    expect($notif->enviada_so)->toBeFalse();
});

test('dropdown do header exibe as notificacoes do agendamento e permite marcar como lidas', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $notif1 = NotificacaoAgendamento::create([
        'tipo_evento' => 'visita',
        'evento_id' => 10,
        'titulo' => 'Visita Agendada: OS #10',
        'mensagem' => 'Visita agendada para o cliente Alpha',
        'url' => '/visitas/10',
        'data_evento' => now()->addMinutes(15),
        'lida' => false,
    ]);

    $notif2 = NotificacaoAgendamento::create([
        'tipo_evento' => 'comodato',
        'evento_id' => 20,
        'titulo' => 'Reposição: Porta-Iscas',
        'mensagem' => 'Reposição programada no cliente Beta',
        'url' => '/comodatos/20',
        'data_evento' => now(),
        'lida' => false,
    ]);

    // Rota /notificacoes chamada pelo polling do dropdown do header
    $response = $this->actingAs($user)->getJson(route('notificacoes'));
    $response->assertOk();
    $response->assertJsonPath('total_nao_lidas', 2);
    $response->assertJsonFragment(['titulo' => 'Visita Agendada: OS #10']);
    $response->assertJsonFragment(['titulo' => 'Reposição: Porta-Iscas']);

    // Marca notif1 como lida individualmente
    $this->actingAs($user)->postJson(route('notificacoes.agendamentos.marcar-lida', $notif1))->assertOk();
    expect($notif1->fresh()->lida)->toBeTrue();

    // Rota /notificacoes agora deve ter 1 não lida
    $response2 = $this->actingAs($user)->getJson(route('notificacoes'));
    $response2->assertJsonPath('total_nao_lidas', 1);

    // Marca todas como lidas
    $this->actingAs($user)->postJson(route('notificacoes.agendamentos.marcar-todas-lidas'))->assertOk();
    expect($notif2->fresh()->lida)->toBeTrue();

    $response3 = $this->actingAs($user)->getJson(route('notificacoes'));
    $response3->assertJsonPath('total_nao_lidas', 0);
});
