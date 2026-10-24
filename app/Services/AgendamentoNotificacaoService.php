<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cliente;
use App\Models\EquipamentoComodato;
use App\Models\NotificacaoAgendamento;
use App\Models\Visita;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AgendamentoNotificacaoService
{
    /**
     * Janela em minutos para alertar visitas antes do horário.
     */
    public const JANELA_VISITA_MINUTOS = 60;

    /**
     * Executa a verificação completa de eventos próximos no módulo de agendamento.
     *
     * @return Collection<int, NotificacaoAgendamento>
     */
    public function verificarEventosProximos(bool $dryRun = false, bool $force = false): Collection
    {
        $novasNotificacoes = collect();

        // 1. Visitas próximas
        $visitas = $this->verificarVisitasProximas($dryRun, $force);
        $novasNotificacoes = $novasNotificacoes->concat($visitas);

        // 2. Comodatos com reposição para hoje ou atrasados
        $comodatos = $this->verificarComodatosProximos($dryRun, $force);
        $novasNotificacoes = $novasNotificacoes->concat($comodatos);

        // 3. Follow-ups agendados para hoje ou pendentes
        $followups = $this->verificarFollowupsProximos($dryRun, $force);
        $novasNotificacoes = $novasNotificacoes->concat($followups);

        return $novasNotificacoes;
    }

    /**
     * Verifica visitas marcadas para os próximos minutos ou atrasadas de hoje.
     *
     * @return Collection<int, NotificacaoAgendamento>
     */
    protected function verificarVisitasProximas(bool $dryRun, bool $force): Collection
    {
        $agora = now();
        $limiteFuturo = $agora->copy()->addMinutes(self::JANELA_VISITA_MINUTOS);
        $limitePassado = $agora->copy()->subMinutes(30);

        $visitas = Visita::with(['cliente', 'tecnico'])
            ->where('status', Visita::STATUS_AGENDADA)
            ->whereBetween('data_hora', [$limitePassado, $limiteFuturo])
            ->get();

        $criadas = collect();

        foreach ($visitas as $visita) {
            $dataHoje = $visita->data_hora->toDateString();

            if (! $force) {
                $jaNotificado = NotificacaoAgendamento::where('tipo_evento', 'visita')
                    ->where('evento_id', $visita->id)
                    ->whereDate('created_at', $agora->toDateString())
                    ->exists();

                if ($jaNotificado) {
                    continue;
                }
            }

            $diffMinutos = (int) round($agora->diffInMinutes($visita->data_hora, false));
            $clienteNome = $visita->cliente?->nome ?? 'Cliente não informado';
            $tecnicoNome = $visita->tecnico?->nome ?? 'Sem técnico atribuído';
            $horario = $visita->data_hora->format('H:i');

            if ($diffMinutos > 0) {
                $tempoTexto = "em {$diffMinutos} minuto(s) (às {$horario})";
            } elseif ($diffMinutos === 0) {
                $tempoTexto = "agora às {$horario}";
            } else {
                $tempoTexto = 'atrasada há '.abs($diffMinutos)." minuto(s) (prevista às {$horario})";
            }

            $titulo = "Visita Agendada: OS #{$visita->id} — {$clienteNome}";
            $mensagem = "Início previsto {$tempoTexto}. Técnico: {$tecnicoNome}. Serviço: {$visita->tipoServicoLabel()}.";

            if ($dryRun) {
                $criadas->push(new NotificacaoAgendamento([
                    'tipo_evento' => 'visita',
                    'evento_id' => $visita->id,
                    'titulo' => $titulo,
                    'mensagem' => $mensagem,
                    'url' => route('visitas.show', $visita, false),
                    'data_evento' => $visita->data_hora,
                ]));
            } else {
                $notif = NotificacaoAgendamento::create([
                    'tipo_evento' => 'visita',
                    'evento_id' => $visita->id,
                    'titulo' => $titulo,
                    'mensagem' => $mensagem,
                    'url' => route('visitas.show', $visita, false),
                    'data_evento' => $visita->data_hora,
                    'user_id' => $visita->tecnico?->user?->id,
                ]);
                $criadas->push($notif);
            }
        }

        return $criadas;
    }

    /**
     * Verifica equipamentos em comodato com reposição hoje ou atrasada.
     *
     * @return Collection<int, NotificacaoAgendamento>
     */
    protected function verificarComodatosProximos(bool $dryRun, bool $force): Collection
    {
        $hoje = now()->toDateString();

        $comodatos = EquipamentoComodato::with(['cliente', 'produto'])
            ->whereDate('data_proxima_reposicao', '<=', $hoje)
            ->get();

        $criadas = collect();

        foreach ($comodatos as $comodato) {
            if (! $force) {
                $jaNotificado = NotificacaoAgendamento::where('tipo_evento', 'comodato')
                    ->where('evento_id', $comodato->id)
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                if ($jaNotificado) {
                    continue;
                }
            }

            $clienteNome = $comodato->cliente?->nome ?? 'Cliente';
            $insumo = $comodato->produto?->nome ? " • Insumo: {$comodato->produto->nome}" : '';
            $status = $comodato->estaAtrasado() ? 'Atrasada' : 'Programada para hoje';

            $titulo = "Comodato: Reposição de {$comodato->nome_equipamento}";
            $mensagem = "Reposição {$status} no cliente {$clienteNome} (Qtd: {$comodato->quantidade} un){$insumo}.";

            $dataEvento = $comodato->data_proxima_reposicao
                ? $comodato->data_proxima_reposicao->copy()->setTime(9, 0)
                : now();

            if ($dryRun) {
                $criadas->push(new NotificacaoAgendamento([
                    'tipo_evento' => 'comodato',
                    'evento_id' => $comodato->id,
                    'titulo' => $titulo,
                    'mensagem' => $mensagem,
                    'url' => route('comodatos.edit', $comodato, false),
                    'data_evento' => $dataEvento,
                ]));
            } else {
                $notif = NotificacaoAgendamento::create([
                    'tipo_evento' => 'comodato',
                    'evento_id' => $comodato->id,
                    'titulo' => $titulo,
                    'mensagem' => $mensagem,
                    'url' => route('comodatos.edit', $comodato, false),
                    'data_evento' => $dataEvento,
                ]);
                $criadas->push($notif);
            }
        }

        return $criadas;
    }

    /**
     * Verifica follow-ups de clientes marcados para hoje ou pendentes.
     *
     * @return Collection<int, NotificacaoAgendamento>
     */
    protected function verificarFollowupsProximos(bool $dryRun, bool $force): Collection
    {
        $hoje = now()->toDateString();

        $clientes = Cliente::comFollowupPendente()
            ->whereDate('data_followup', '<=', $hoje)
            ->get();

        $criadas = collect();

        foreach ($clientes as $cli) {
            if (! $force) {
                $jaNotificado = NotificacaoAgendamento::where('tipo_evento', 'followup')
                    ->where('evento_id', $cli->id)
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                if ($jaNotificado) {
                    continue;
                }
            }

            $titulo = "Follow-up Comercial: {$cli->nome}";
            $obs = $cli->observacoes ? " — {$cli->observacoes}" : '';
            $mensagem = "Retorno de prospecção ({$cli->status_prospeccao_label}) agendado. Contato: ".($cli->telefone ?? 'Sem telefone').$obs;

            $dataEvento = Carbon::parse($cli->data_followup)->setTime(10, 0);

            if ($dryRun) {
                $criadas->push(new NotificacaoAgendamento([
                    'tipo_evento' => 'followup',
                    'evento_id' => $cli->id,
                    'titulo' => $titulo,
                    'mensagem' => $mensagem,
                    'url' => route('clientes.show', $cli, false),
                    'data_evento' => $dataEvento,
                ]));
            } else {
                $notif = NotificacaoAgendamento::create([
                    'tipo_evento' => 'followup',
                    'evento_id' => $cli->id,
                    'titulo' => $titulo,
                    'mensagem' => $mensagem,
                    'url' => route('clientes.show', $cli, false),
                    'data_evento' => $dataEvento,
                ]);
                $criadas->push($notif);
            }
        }

        return $criadas;
    }

    /**
     * Emite uma notificação nativa no Sistema Operacional (Windows, Linux ou macOS).
     */
    public function emitirNotificacaoSistemaOperacional(string $titulo, string $mensagem): bool
    {
        try {
            $osFamily = PHP_OS_FAMILY;

            if ($osFamily === 'Windows') {
                // Notificação nativa via PowerShell no Windows (Balão / Toast)
                $safeTitle = addslashes(str_replace(["\r", "\n"], ' ', $titulo));
                $safeMsg = addslashes(str_replace(["\r", "\n"], ' ', $mensagem));

                $psCommand = "[reflection.assembly]::loadwithpartialname('System.Windows.Forms') | Out-Null; ".
                    "[reflection.assembly]::loadwithpartialname('System.Drawing') | Out-Null; ".
                    '$notify = new-object system.windows.forms.notifyicon; '.
                    '$notify.icon = [system.drawing.systemicons]::Information; '.
                    '$notify.visible = $true; '.
                    "\$notify.showballoontip(10000, '{$safeTitle}', '{$safeMsg}', [system.windows.forms.tooltipicon]::Info);";

                // Executar em background sem bloquear
                pclose(popen("powershell.exe -NoProfile -NonInteractive -Command \"{$psCommand}\" 2>&1", 'r'));

                return true;
            }

            if ($osFamily === 'Linux') {
                $safeTitle = escapeshellarg($titulo);
                $safeMsg = escapeshellarg($mensagem);
                if (exec('which notify-send 2>/dev/null')) {
                    exec("notify-send -i calendar {$safeTitle} {$safeMsg} > /dev/null 2>&1 &");

                    return true;
                }
            }

            if ($osFamily === 'Darwin') { // macOS
                $safeTitle = addslashes($titulo);
                $safeMsg = addslashes($mensagem);
                $script = "display notification \"{$safeMsg}\" with title \"{$safeTitle}\" sound name \"default\"";
                exec('osascript -e '.escapeshellarg($script).' > /dev/null 2>&1 &');

                return true;
            }
        } catch (\Throwable $e) {
            Log::warning('Não foi possível emitir notificação nativa no SO: '.$e->getMessage());
        }

        return false;
    }
}
