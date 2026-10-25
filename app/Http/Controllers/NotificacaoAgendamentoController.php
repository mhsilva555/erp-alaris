<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NotificacaoAgendamento;
use App\Models\PushSubscription;
use App\Services\AgendamentoNotificacaoService;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacaoAgendamentoController extends Controller
{
    /**
     * Retorna a lista de notificações pendentes para emissão no Sistema Operacional.
     */
    public function pendentes(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && ! $user->temPermissao('notificacoes.ver')) {
            return response()->json([
                'sucesso' => true,
                'total' => 0,
                'notificacoes' => [],
            ]);
        }

        $notificacoes = NotificacaoAgendamento::paraUsuario($user)
            ->where('enviada_so', false)
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'asc')
            ->take(10)
            ->get();

        return response()->json([
            'sucesso' => true,
            'total' => $notificacoes->count(),
            'notificacoes' => $notificacoes->map(fn (NotificacaoAgendamento $n) => [
                'id' => $n->id,
                'titulo' => $n->titulo,
                'mensagem' => $n->mensagem,
                'url' => $n->url,
                'tipo_evento' => $n->tipo_evento,
                'data_evento' => $n->data_evento?->format('d/m/Y H:i'),
                'created_at' => $n->created_at?->format('Y-m-d H:i:s'),
            ]),
        ]);
    }

    /**
     * Marca a notificação como entregue/exibida no Sistema Operacional.
     */
    public function marcarExibida(Request $request, NotificacaoAgendamento $notificacao): JsonResponse
    {
        $user = $request->user();

        if ($user && ! $notificacao->pertenceAoUsuario($user)) {
            abort(403, 'Acesso não autorizado a esta notificação.');
        }

        $notificacao->marcarComoEnviada();

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Notificação marcada como exibida no SO.',
        ]);
    }

    /**
     * Marca uma notificação individual como lida.
     */
    public function marcarLida(Request $request, NotificacaoAgendamento $notificacao): JsonResponse
    {
        $user = $request->user();

        if ($user && ! $notificacao->pertenceAoUsuario($user)) {
            abort(403, 'Acesso não autorizado a esta notificação.');
        }

        $notificacao->marcarComoLida();

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Notificação marcada como lida.',
        ]);
    }

    /**
     * Marca todas as notificações como lidas.
     */
    public function marcarTodasLidas(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && ! $user->temPermissao('notificacoes.editar')) {
            abort(403, 'Você não tem permissão para alterar notificações.');
        }

        $afetados = NotificacaoAgendamento::paraUsuario($user)
            ->where('lida', false)
            ->update([
                'lida' => true,
                'enviada_so' => true,
            ]);

        return response()->json([
            'sucesso' => true,
            'total_afetadas' => $afetados,
            'mensagem' => 'Todas as notificações foram marcadas como lidas.',
        ]);
    }

    /**
     * Dispara uma notificação de teste imediata para verificar no computador ou celular.
     */
    public function testar(Request $request, AgendamentoNotificacaoService $service, WebPushService $webPush): JsonResponse
    {
        $user = $request->user();

        if ($user && ! $user->temPermissao('notificacoes.alertas_navegador') && ! $user->temPermissao('notificacoes.ver')) {
            abort(403, 'Sem permissão para testar notificações.');
        }

        // Cria sem disparar o push automático do model, para enviar de forma síncrona e reportar o resultado
        $notificacao = NotificacaoAgendamento::withoutEvents(fn () => NotificacaoAgendamento::create([
            'tipo_evento' => 'visita',
            'evento_id' => 0,
            'titulo' => 'ERP Alaris — Teste de Notificação',
            'mensagem' => 'Notificações de agendamento ativas no seu navegador!',
            'url' => route('agendamentos.index', [], false),
            'data_evento' => now(),
            'user_id' => $user?->id,
            'enviada_so' => false,
        ]));

        $pushEntregues = $webPush->enviarNotificacao($notificacao);

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Notificação de teste criada com sucesso!',
            'push_configurado' => $webPush->configurado(),
            'push_entregues' => $pushEntregues,
            'notificacao' => [
                'id' => $notificacao->id,
                'titulo' => $notificacao->titulo,
                'mensagem' => $notificacao->mensagem,
                'url' => $notificacao->url,
            ],
        ]);
    }

    /**
     * Registra (ou atualiza) a inscrição Web Push do dispositivo atual.
     */
    public function inscreverPush(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'endpoint' => ['required', 'url', 'max:500'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'in:aesgcm,aes128gcm'],
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint' => $dados['endpoint']],
            [
                'user_id' => $request->user()->id,
                'public_key' => $dados['keys']['p256dh'],
                'auth_token' => $dados['keys']['auth'],
                'content_encoding' => $dados['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ]
        );

        return response()->json(['sucesso' => true]);
    }

    /**
     * Remove a inscrição Web Push do dispositivo atual.
     */
    public function cancelarPush(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        PushSubscription::where('endpoint', $dados['endpoint'])
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['sucesso' => true]);
    }
}
