<?php

namespace App\Console\Commands;

use App\Models\Contrato;
use App\Services\ContratoAgendamentoService;
use Illuminate\Console\Command;

class GerarVisitasContratosCommand extends Command
{
    protected $signature = 'contratos:gerar-visitas {--dias=90 : Quantidade de dias futuros para abastecer a agenda}';

    protected $description = 'Gera progressivamente as visitas agendadas para contratos ativos e atualiza contratos expirados';

    public function handle(ContratoAgendamentoService $service): int
    {
        $dias = (int) $this->option('dias');
        $horizonte = now()->addDays($dias);

        $this->info("Iniciando abastecimento de agenda para contratos ativos até {$horizonte->format('d/m/Y')}...");

        // 1. Atualizar contratos expirados
        $expirados = $service->verificarContratosExpirados();
        if ($expirados > 0) {
            $this->warn("Contratos expirados identificados e atualizados: {$expirados}");
        }

        // 2. Buscar contratos ativos
        $contratos = Contrato::where('status', Contrato::STATUS_ATIVO)->get();
        $totalVisitas = 0;

        foreach ($contratos as $contrato) {
            $criadas = $service->gerarVisitas($contrato, $horizonte);
            $totalVisitas += $criadas;

            if ($criadas > 0) {
                $this->line("Contrato {$contrato->numero}: +{$criadas} visitas agendadas.");
            }
        }

        $this->info("Processamento concluído. Total de contratos verificados: {$contratos->count()} | Novas visitas geradas: {$totalVisitas}");

        return self::SUCCESS;
    }
}
