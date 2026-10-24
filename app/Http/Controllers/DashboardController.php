<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\EquipamentoComodato;
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

        $total = $atrasadas + $proximas + $estoqueBaixo + $comodatos + $followups;

        return response()->json([
            'atrasadas' => $atrasadas,
            'proximas' => $proximas,
            'estoque_baixo' => $estoqueBaixo,
            'comodatos' => $comodatos,
            'followups' => $followups,
            'total' => $total,
        ]);
    }
}
