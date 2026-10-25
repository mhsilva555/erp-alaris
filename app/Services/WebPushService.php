<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NotificacaoAgendamento;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * Envio de notificações via Web Push (VAPID) para o Service Worker (public/sw.js).
 *
 * Diferente do polling em JavaScript, o push é entregue pelo serviço de push do
 * navegador (FCM / Apple / Mozilla) e desperta o Service Worker mesmo com o PWA
 * fechado, em segundo plano ou com o celular bloqueado.
 */
class WebPushService
{
    public function configurado(): bool
    {
        return filled(config('webpush.vapid.public_key'))
            && filled(config('webpush.vapid.private_key'));
    }

    public function chavePublica(): ?string
    {
        return config('webpush.vapid.public_key') ?: null;
    }

    /**
     * Resolve os usuários que devem receber o push de uma notificação, espelhando
     * as regras de visibilidade de NotificacaoAgendamento::scopeParaUsuario().
     *
     * - Notificação direcionada (user_id): somente o usuário alvo.
     * - Notificação geral (user_id nulo): administradores, perfis não técnicos e
     *   o técnico responsável quando for evento de visita.
     *
     * @return Collection<int, User>
     */
    public function destinatarios(NotificacaoAgendamento $notificacao): Collection
    {
        $usuarios = User::query()
            ->with('perfil')
            ->whereIn('id', PushSubscription::query()->select('user_id'))
            ->get();

        return $usuarios->filter(function (User $user) use ($notificacao): bool {
            if (! $user->temPermissao('notificacoes.ver')) {
                return false;
            }

            if ($notificacao->user_id) {
                return (int) $notificacao->user_id === (int) $user->id;
            }

            if ($user->isAdmin() || ! $user->isTecnico()) {
                return true;
            }

            return $notificacao->pertenceAoUsuario($user);
        })->values();
    }

    /**
     * Envia o push da notificação para todos os dispositivos inscritos dos destinatários.
     * Retorna a quantidade de entregas aceitas pelo serviço de push.
     */
    public function enviarNotificacao(NotificacaoAgendamento $notificacao): int
    {
        if (! $this->configurado()) {
            return 0;
        }

        try {
            $userIds = $this->destinatarios($notificacao)->pluck('id');

            if ($userIds->isEmpty()) {
                return 0;
            }

            $inscricoes = PushSubscription::query()->whereIn('user_id', $userIds)->get();

            $entregues = $this->enviarParaInscricoes($inscricoes, [
                'id' => $notificacao->id,
                'titulo' => $notificacao->titulo,
                'mensagem' => $notificacao->mensagem,
                'url' => $notificacao->url ?: '/agendamentos',
                'tipo_evento' => $notificacao->tipo_evento,
            ]);

            if ($entregues > 0 && ! $notificacao->enviada_so) {
                // Evita que o polling da página exiba a mesma notificação em duplicidade
                $notificacao->marcarComoEnviada();
            }

            return $entregues;
        } catch (Throwable $e) {
            Log::warning('Falha ao enviar Web Push da notificação #'.$notificacao->id.': '.$e->getMessage());

            return 0;
        }
    }

    /**
     * @param  Collection<int, PushSubscription>  $inscricoes
     * @param  array<string, mixed>  $payload
     */
    public function enviarParaInscricoes(Collection $inscricoes, array $payload): int
    {
        if (! $this->configurado() || $inscricoes->isEmpty()) {
            return 0;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => (string) config('webpush.vapid.subject'),
                'publicKey' => (string) config('webpush.vapid.public_key'),
                'privateKey' => (string) config('webpush.vapid.private_key'),
            ],
        ], [
            'TTL' => (int) config('webpush.ttl', 86400),
            'urgency' => 'high',
        ]);
        $webPush->setReuseVAPIDHeaders(true);

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

        /** @var array<string, PushSubscription> $porEndpoint */
        $porEndpoint = [];
        foreach ($inscricoes as $inscricao) {
            $porEndpoint[$inscricao->endpoint] = $inscricao;
            $webPush->queueNotification(new Subscription(
                $inscricao->endpoint,
                $inscricao->public_key,
                $inscricao->auth_token,
                $inscricao->content_encoding ?: 'aes128gcm',
            ), $json);
        }

        $entregues = 0;
        foreach ($webPush->flush() as $relatorio) {
            $inscricao = $porEndpoint[$relatorio->getEndpoint()] ?? null;

            if ($relatorio->isSuccess()) {
                $entregues++;
                $inscricao?->forceFill(['ultimo_envio_em' => now()])->save();

                continue;
            }

            if ($relatorio->isSubscriptionExpired()) {
                // 404/410: aparelho desinstalou o PWA ou revogou a permissão
                $inscricao?->delete();

                continue;
            }

            Log::warning('Web Push rejeitado: '.$relatorio->getReason());
        }

        return $entregues;
    }
}
