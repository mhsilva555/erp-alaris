<?php

use App\Models\NotificacaoAgendamento;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPushService;

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
