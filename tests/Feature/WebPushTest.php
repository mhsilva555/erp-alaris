<?php

use App\Models\NotificacaoAgendamento;
use App\Models\Perfil;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPushService;
use Database\Seeders\PerfilPermissaoSeeder;

beforeEach(function () {
    // Nunca dispara requisições reais aos serviços de push durante os testes
    config(['webpush.vapid.public_key' => null, 'webpush.vapid.private_key' => null]);
});

function inscricaoFake(User $user, string $sufixo = 'a'): PushSubscription
{
    return PushSubscription::create([
        'user_id' => $user->id,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/teste-'.$sufixo,
        'public_key' => 'BPk',
        'auth_token' => 'auth',
        'content_encoding' => 'aes128gcm',
    ]);
}

test('usuario autenticado registra e atualiza a inscricao web push do dispositivo', function () {
    $user = User::factory()->create(['role' => User::ROLE_TECNICO]);

    $payload = [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
        'keys' => ['p256dh' => 'chave-publica', 'auth' => 'token-auth'],
        'contentEncoding' => 'aes128gcm',
    ];

    $this->actingAs($user)->postJson('/notificacoes/push/inscrever', $payload)->assertOk();
    $payload['keys']['auth'] = 'token-novo';
    $this->actingAs($user)->postJson('/notificacoes/push/inscrever', $payload)->assertOk();

    expect(PushSubscription::count())->toBe(1);
    expect(PushSubscription::first()->auth_token)->toBe('token-novo');
    expect(PushSubscription::first()->user_id)->toBe($user->id);

    $this->actingAs($user)->postJson('/notificacoes/push/cancelar', ['endpoint' => $payload['endpoint']])->assertOk();
    expect(PushSubscription::count())->toBe(0);
});

test('inscricao web push exige autenticacao e dados validos', function () {
    $resposta = $this->postJson('/notificacoes/push/inscrever', []);
    expect($resposta->status())->toBeIn([302, 401]);
    expect(PushSubscription::count())->toBe(0);
});

test('inscricao web push rejeita dados invalidos', function () {
    $user = User::factory()->create();
    $resposta = $this->actingAs($user)->postJson('/notificacoes/push/inscrever', ['endpoint' => 'invalido']);
    expect($resposta->status())->toBeIn([302, 422]);
    expect(PushSubscription::count())->toBe(0);
});

test('push direcionado vai somente ao usuario alvo e geral exclui tecnicos de outras visitas', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $tecnicoA = User::factory()->create(['role' => User::ROLE_TECNICO]);
    $tecnicoB = User::factory()->create(['role' => User::ROLE_TECNICO]);

    foreach ([$admin, $tecnicoA, $tecnicoB] as $i => $u) {
        inscricaoFake($u, (string) $i);
    }

    $direcionada = NotificacaoAgendamento::withoutEvents(fn () => NotificacaoAgendamento::create([
        'tipo_evento' => 'visita', 'evento_id' => 999, 'titulo' => 'OS', 'mensagem' => 'Nova OS',
        'user_id' => $tecnicoA->id, 'data_evento' => now(),
    ]));

    $geral = NotificacaoAgendamento::withoutEvents(fn () => NotificacaoAgendamento::create([
        'tipo_evento' => 'comodato', 'evento_id' => 1, 'titulo' => 'Comodato', 'mensagem' => 'Reposição',
        'user_id' => null, 'data_evento' => now(),
    ]));

    $service = app(WebPushService::class);

    expect($service->destinatarios($direcionada)->pluck('id')->all())->toBe([$tecnicoA->id]);
    expect($service->destinatarios($geral)->pluck('id')->all())->toBe([$admin->id]);
});

test('sem chaves VAPID o envio e ignorado e o polling continua como fallback', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    inscricaoFake($user);

    $notif = NotificacaoAgendamento::create([
        'tipo_evento' => 'followup', 'evento_id' => 1, 'titulo' => 'Follow-up', 'mensagem' => 'Retornar',
        'user_id' => $user->id, 'enviada_so' => false, 'data_evento' => now(),
    ]);

    expect(app(WebPushService::class)->enviarNotificacao($notif))->toBe(0);
    expect($notif->fresh()->enviada_so)->toBeFalse();
});

test('logout remove inscricao push do dispositivo para nao emitir notificacoes sem autenticacao', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $sub = inscricaoFake($user, 'logout-device');

    $this->actingAs($user)->post(route('logout'), [
        'push_endpoint' => $sub->endpoint,
    ])->assertRedirect(route('login'));

    expect(PushSubscription::where('endpoint', $sub->endpoint)->exists())->toBeFalse();
});

test('somente administradores recebem todas as notificacoes e demais usuarios respeitam permissoes', function () {
    $this->seed(PerfilPermissaoSeeder::class);

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    // Perfil com permissão apenas para notificações e comodatos (sem clientes/follow-up nem visitas)
    $perfilComodato = Perfil::create(['nome' => 'Gestor Comodato', 'slug' => 'gestor_comodato']);
    $perfilComodato->sincronizarPermissoes(['notificacoes.ver', 'comodatos.ver']);
    $userComodato = User::factory()->create([
        'role' => 'gestor_comodato',
        'perfil_id' => $perfilComodato->id,
    ]);

    // Perfil financeiro estrito (sem permissão de visitas, comodatos ou clientes)
    $perfilFinanceiro = Perfil::create(['nome' => 'Somente Financeiro', 'slug' => 'somente_financeiro']);
    $perfilFinanceiro->sincronizarPermissoes(['notificacoes.ver', 'financeiro.ver']);
    $userFinanceiro = User::factory()->create([
        'role' => 'somente_financeiro',
        'perfil_id' => $perfilFinanceiro->id,
    ]);

    inscricaoFake($admin, 'admin');
    inscricaoFake($userComodato, 'comodato');
    inscricaoFake($userFinanceiro, 'fin');

    $notifComodato = NotificacaoAgendamento::withoutEvents(fn () => NotificacaoAgendamento::create([
        'tipo_evento' => 'comodato', 'evento_id' => 1, 'titulo' => 'Comodato', 'mensagem' => 'Reposição',
        'user_id' => null, 'data_evento' => now(),
    ]));

    $notifFollowup = NotificacaoAgendamento::withoutEvents(fn () => NotificacaoAgendamento::create([
        'tipo_evento' => 'followup', 'evento_id' => 2, 'titulo' => 'Follow-up', 'mensagem' => 'Retornar',
        'user_id' => null, 'data_evento' => now(),
    ]));

    $notifVisita = NotificacaoAgendamento::withoutEvents(fn () => NotificacaoAgendamento::create([
        'tipo_evento' => 'visita', 'evento_id' => 3, 'titulo' => 'Visita', 'mensagem' => 'OS',
        'user_id' => null, 'data_evento' => now(),
    ]));

    $service = app(WebPushService::class);

    // Push: admin recebe tudo; gestor_comodato recebe só comodato; financeiro não recebe nenhum dos três
    expect($service->destinatarios($notifComodato)->pluck('id')->all())
        ->toEqualCanonicalizing([$admin->id, $userComodato->id]);
    expect($service->destinatarios($notifFollowup)->pluck('id')->all())
        ->toBe([$admin->id]);
    expect($service->destinatarios($notifVisita)->pluck('id')->all())
        ->toBe([$admin->id]);

    // Escopo da central/polling espelha exatamente a mesma regra
    expect(NotificacaoAgendamento::paraUsuario($admin)->count())->toBe(3);
    expect(NotificacaoAgendamento::paraUsuario($userComodato)->pluck('id')->all())->toBe([$notifComodato->id]);
    expect(NotificacaoAgendamento::paraUsuario($userFinanceiro)->count())->toBe(0);
});
