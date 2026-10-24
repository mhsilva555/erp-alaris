<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\EquipamentoComodato;
use App\Models\NotificacaoAgendamento;
use App\Models\Produto;
use App\Models\Visita;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $hoje = Carbon::today();
        $user = $request->user();

        $visitasQuery = Visita::query();
        if ($user && $user->isTecnico()) {
            $visitasQuery->where('tecnico_id', $user->tecnico_id);
        }

        $totalClientes = Cliente::count();
        $totalProdutos = Produto::count();
        $totalComodatos = EquipamentoComodato::count();

        $visitasHoje = (clone $visitasQuery)->whereDate('data_hora', $hoje)->count();
        $visitasMes = (clone $visitasQuery)->whereMonth('data_hora', $hoje->month)
            ->whereYear('data_hora', $hoje->year)
            ->count();

        $proximasVisitas = (clone $visitasQuery)->where('status', Visita::STATUS_AGENDADA)
            ->where('data_hora', '>=', now())
            ->where('data_hora', '<=', now()->addDays(2))
            ->with(['cliente', 'tecnico'])
            ->orderBy('data_hora')
            ->limit(10)
            ->get();

        $visitasAtrasadas = (clone $visitasQuery)->where('status', Visita::STATUS_AGENDADA)
            ->where('data_hora', '<', now())
            ->with(['cliente', 'tecnico'])
            ->orderBy('data_hora')
            ->limit(10)
            ->get();

        $estoqueBaixo = Produto::whereColumn('quantidade_estoque', '<=', 'estoque_minimo')
            ->get();

        $comodatoVencendo = EquipamentoComodato::with(['cliente', 'produto'])
            ->reposicaoProxima(3)
            ->orderBy('data_proxima_reposicao')
            ->limit(10)
            ->get();

        $followupsProximos = Cliente::comFollowupPendente()
            ->where('data_followup', '<=', now()->addDays(3)->toDateString())
            ->orderBy('data_followup')
            ->limit(10)
            ->get();

        $visitasRecentes = (clone $visitasQuery)->with(['cliente', 'tecnico'])
            ->orderByDesc('data_hora')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact(
            'totalClientes',
            'totalProdutos',
            'totalComodatos',
            'visitasHoje',
            'visitasMes',
            'proximasVisitas',
            'visitasAtrasadas',
            'estoqueBaixo',
            'comodatoVencendo',
            'followupsProximos',
            'visitasRecentes',
        ));
    }

    public function notificacoes(Request $request)
    {
        $user = $request->user();

        // 1. Notificações geradas no módulo de Agendamento (visitas, comodatos e follow-ups)
        $notificacoesQuery = NotificacaoAgendamento::query();
        if ($user && $user->isTecnico()) {
            $notificacoesQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('tipo_evento', 'visita');
            });
        }

        $totalNaoLidas = (clone $notificacoesQuery)->where('lida', false)->count();

        $itens = (clone $notificacoesQuery)
            ->latest()
            ->take(8)
            ->get()
            ->map(fn (NotificacaoAgendamento $n) => [
                'id' => $n->id,
                'tipo_evento' => $n->tipo_evento,
                'titulo' => $n->titulo,
                'mensagem' => $n->mensagem,
                'url' => ($n->url && $n->url !== '#') ? $n->url : route('agendamentos.index'),
                'lida' => (bool) $n->lida,
                'tempo_relativo' => $n->created_at?->diffForHumans() ?? '',
                'data_evento' => $n->data_evento?->format('d/m/Y H:i'),
            ]);

        // 2. Indicadores gerais do sistema
        $visitasQuery = Visita::where('status', Visita::STATUS_AGENDADA);
        if ($user && $user->isTecnico()) {
            $visitasQuery->where('tecnico_id', $user->tecnico_id);
        }

        $atrasadas = (clone $visitasQuery)->where('data_hora', '<', now())->count();
        $proximas = (clone $visitasQuery)->where('data_hora', '>=', now())
            ->where('data_hora', '<=', now()->addDays(2))->count();

        $estoqueBaixo = ($user && $user->isAdmin())
            ? Produto::whereColumn('quantidade_estoque', '<=', 'estoque_minimo')->count()
            : 0;

        $comodatos = ($user && $user->isAdmin())
            ? EquipamentoComodato::reposicaoProxima(2)->count()
            : 0;

        $followups = ($user && $user->isAdmin())
            ? Cliente::comFollowupPendente()->where('data_followup', '<=', now()->addDays(2)->toDateString())->count()
            : 0;

        $totalGeral = $totalNaoLidas > 0 ? $totalNaoLidas : ($atrasadas + $estoqueBaixo);

        return response()->json([
            'atrasadas' => $atrasadas,
            'proximas' => $proximas,
            'estoque_baixo' => $estoqueBaixo,
            'comodatos' => $comodatos,
            'followups' => $followups,
            'itens' => $itens,
            'total_nao_lidas' => $totalNaoLidas,
            'total' => $totalGeral,
        ]);
    }
}
