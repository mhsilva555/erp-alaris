<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NotificacaoAgendamento;
use App\Services\AgendamentoNotificacaoService;
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
    public function testar(Request $request, AgendamentoNotificacaoService $service): JsonResponse
    {
        $user = $request->user();

        if ($user && ! $user->temPermissao('notificacoes.alertas_navegador') && ! $user->temPermissao('notificacoes.ver')) {
            abort(403, 'Sem permissão para testar notificações.');
        }

        $notificacao = NotificacaoAgendamento::create([
            'tipo_evento' => 'visita',
            'evento_id' => 0,
            'titulo' => 'ERP Alaris — Teste de Notificação',
            'mensagem' => 'Notificações de agendamento ativas no seu navegador!',
            'url' => route('agendamentos.index', [], false),
            'data_evento' => now(),
            'user_id' => $user?->id,
            'enviada_so' => false,
        ]);

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Notificação de teste criada com sucesso!',
            'notificacao' => [
                'id' => $notificacao->id,
                'titulo' => $notificacao->titulo,
                'mensagem' => $notificacao->mensagem,
                'url' => $notificacao->url,
            ],
        ]);
    }
}
