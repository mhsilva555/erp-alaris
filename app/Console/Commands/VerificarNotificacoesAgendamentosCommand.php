<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AgendamentoNotificacaoService;
use Illuminate\Console\Command;

class VerificarNotificacoesAgendamentosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agendamentos:verificar-notificacoes
                            {--dry-run : Apenas simula a checagem sem gravar ou disparar notificações}
                            {--force : Força o envio de notificações mesmo se já enviadas hoje}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica minuto a minuto eventos próximos no módulo de Agendamento (visitas, comodatos e follow-ups) e emite notificações';

    /**
     * Execute the console command.
     */
    public function handle(AgendamentoNotificacaoService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $this->info('['.now()->format('Y-m-d H:i:s').'] Verificando eventos no módulo de Agendamento...');

        $notificacoes = $service->verificarEventosProximos($dryRun, $force);

        if ($notificacoes->isEmpty()) {
            $this->comment('Nenhum evento iminente precisando de nova notificação no momento.');

            return self::SUCCESS;
        }

        $this->info("Encontrado(s) {$notificacoes->count()} evento(s) iminente(s):");

        $rows = $notificacoes->map(fn ($n) => [
            'Tipo' => strtoupper($n->tipo_evento),
            'Título' => $n->titulo,
            'Mensagem' => $n->mensagem,
            'Data Evento' => $n->data_evento?->format('d/m/Y H:i') ?? '—',
            'Status' => $dryRun ? '[SIMULADO]' : 'NOTIFICADO',
        ])->toArray();

        $this->table(['Tipo', 'Título', 'Mensagem', 'Data Evento', 'Status'], $rows);

        return self::SUCCESS;
    }
}
