<?php

namespace App\Services;

use App\Models\Contrato;
use App\Models\MovimentacaoFinanceira;
use App\Models\Visita;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContratoAgendamentoService
{
    /**
     * Horizonte padrão para geração progressiva de visitas em contratos por prazo indeterminado (em dias).
     */
    public const HORIZONTE_PADRAO_DIAS = 90;

    /**
     * Calcula e persiste as visitas agendadas para um contrato ativo até o horizonte estipulado.
     * Garante idempotência: datas já geradas ou existentes não são duplicadas.
     *
     * @param  Carbon|null  $ateData  Limite superior para geração (default: hoje + 90 dias ou data_fim)
     * @return int Quantidade de novas visitas criadas
     */
    public function gerarVisitas(Contrato $contrato, ?Carbon $ateData = null): int
    {
        if ($contrato->status !== Contrato::STATUS_ATIVO) {
            return 0;
        }

        // Determinar limite superior de geração
        $limite = $ateData ? $ateData->copy()->endOfDay() : now()->addDays(self::HORIZONTE_PADRAO_DIAS)->endOfDay();

        if ($contrato->tipo_vigencia === Contrato::VIGENCIA_DETERMINADO && $contrato->data_fim) {
            $fimContrato = $contrato->data_fim->copy()->endOfDay();
            if ($fimContrato->lt($limite)) {
                $limite = $fimContrato;
            }
        }

        // Se a data de início for posterior ao limite, nada a gerar
        if ($contrato->data_inicio->gt($limite)) {
            return 0;
        }

        // Calcular todas as datas previstas no intervalo [data_inicio, limite]
        $datasPrevistas = $this->calcularDatasPrevistas($contrato, $limite);

        $novasVisitasCriadas = 0;

        DB::transaction(function () use ($contrato, $datasPrevistas, $limite, &$novasVisitasCriadas) {
            foreach ($datasPrevistas as $dataHora) {
                // Prevenção de duplicidade (idempotência):
                // Não cria se já existir visita não cancelada para o mesmo contrato e mesma data
                $jaExiste = Visita::where('contrato_id', $contrato->id)
                    ->whereDate('data_hora', $dataHora->toDateString())
                    ->where('status', '!=', Visita::STATUS_CANCELADA)
                    ->exists();

                if (! $jaExiste) {
                    Visita::create([
                        'cliente_id' => $contrato->cliente_id,
                        'contrato_id' => $contrato->id,
                        'data_hora' => $dataHora,
                        'status' => Visita::STATUS_AGENDADA,
                        'tipo_visita' => Visita::TIPO_VISITA_EXECUCAO,
                        'tipo_servico' => $contrato->tipo_servico ?? Visita::TIPO_SERVICO_CONTROLE_PRAGAS,
                        'valor_cobrado' => $contrato->modelo_cobranca === Contrato::MODELO_POR_VISITA ? $contrato->valor_total : 0.00,
                        'observacoes' => "Visita agendada gerada pelo Contrato {$contrato->numero}.",
                    ]);

                    $novasVisitasCriadas++;
                }
            }

            // Atualiza o marcador de horizonte de geração
            $contrato->update([
                'proxima_geracao_ate' => $limite->toDateString(),
            ]);
        });

        return $novasVisitasCriadas;
    }

    /**
     * Calcula as datas previstas de atendimento para um período delimitado (ex: mês visualizado na Agenda).
     *
     * @return Collection<int, Carbon>
     */
    public function calcularDatasNoPeriodo(Contrato $contrato, Carbon $inicioPeriodo, Carbon $fimPeriodo): Collection
    {
        if ($contrato->status !== Contrato::STATUS_ATIVO) {
            return collect();
        }

        // Se o contrato inicia após o período ou expira antes do período, retorna vazio
        if ($contrato->data_inicio->gt($fimPeriodo)) {
            return collect();
        }

        if ($contrato->tipo_vigencia === Contrato::VIGENCIA_DETERMINADO && $contrato->data_fim && $contrato->data_fim->lt($inicioPeriodo)) {
            return collect();
        }

        $limiteCalculo = $fimPeriodo->copy()->endOfDay();
        if ($contrato->tipo_vigencia === Contrato::VIGENCIA_DETERMINADO && $contrato->data_fim && $contrato->data_fim->lt($limiteCalculo)) {
            $limiteCalculo = $contrato->data_fim->copy()->endOfDay();
        }

        $todasDatas = $this->calcularDatasPrevistas($contrato, $limiteCalculo);

        return $todasDatas->filter(function (Carbon $d) use ($inicioPeriodo, $fimPeriodo) {
            return $d->gte($inicioPeriodo->copy()->startOfDay()) && $d->lte($fimPeriodo->copy()->endOfDay());
        })->values();
    }

    /**
     * Calcula a lista de momentos (Carbon) para atendimento de acordo com a periodicidade e regras do contrato.
     *
     * @return Collection<int, Carbon>
     */
    public function calcularDatasPrevistas(Contrato $contrato, Carbon $limite): Collection
    {
        $datas = collect();
        $inicio = $contrato->data_inicio->copy()->startOfDay();

        // Se o contrato já expirou antes do início, retorna vazio
        if ($inicio->gt($limite)) {
            return $datas;
        }

        switch ($contrato->periodicidade) {
            case Contrato::PERIODICIDADE_UNICO:
                if ($inicio->lte($limite)) {
                    $datas->push($inicio->copy()->setTime(9, 0));
                }
                break;

            case Contrato::PERIODICIDADE_SEMANAL:
                $diaSemanaAlvo = $contrato->dia_semana !== null ? (int) $contrato->dia_semana : $inicio->dayOfWeek;
                $cur = $inicio->copy();

                // Avança até o primeiro dia da semana alvo se necessário
                while ($cur->dayOfWeek !== $diaSemanaAlvo) {
                    $cur->addDay();
                }

                while ($cur->lte($limite)) {
                    if ($cur->gte($inicio)) {
                        $datas->push($cur->copy()->setTime(9, 0));
                    }
                    $cur->addWeek();
                }
                break;

            case Contrato::PERIODICIDADE_QUINZENAL:
                // Ciclo quinzenal a cada 14 dias a partir da data de início
                $cur = $inicio->copy();
                while ($cur->lte($limite)) {
                    $datas->push($cur->copy()->setTime(9, 0));
                    $cur->addDays(14);
                }
                break;

            case Contrato::PERIODICIDADE_PERSONALIZADO:
                $intervaloDias = max(1, (int) ($contrato->periodicidade_dias ?: 30));
                $cur = $inicio->copy();
                while ($cur->lte($limite)) {
                    $datas->push($cur->copy()->setTime(9, 0));
                    $cur->addDays($intervaloDias);
                }
                break;

            case Contrato::PERIODICIDADE_MENSAL:
            case Contrato::PERIODICIDADE_BIMESTRAL:
            case Contrato::PERIODICIDADE_TRIMESTRAL:
            case Contrato::PERIODICIDADE_SEMESTRAL:
            case Contrato::PERIODICIDADE_ANUAL:
                $mesesPasso = match ($contrato->periodicidade) {
                    Contrato::PERIODICIDADE_BIMESTRAL => 2,
                    Contrato::PERIODICIDADE_TRIMESTRAL => 3,
                    Contrato::PERIODICIDADE_SEMESTRAL => 6,
                    Contrato::PERIODICIDADE_ANUAL => 12,
                    default => 1,
                };

                $diaPreferencial = (int) ($contrato->dia_preferencial ?: $inicio->day);

                $mesRef = $inicio->copy()->startOfMonth();
                $fimRef = $limite->copy()->endOfMonth();

                while ($mesRef->lte($fimRef)) {
                    // Tratamento explícito de datas inexistentes (ex.: dia 31 em meses com 28, 29 ou 30 dias):
                    // Se o dia preferencial for maior que a quantidade de dias do mês, ajusta para o último dia do mês.
                    $diasNoMes = $mesRef->daysInMonth;
                    $diaEfetivo = min($diaPreferencial, $diasNoMes);

                    $dataEfetiva = $mesRef->copy()->day($diaEfetivo)->setTime(9, 0);

                    // A visita só é incluída se estiver no intervalo válido [data_inicio, limite]
                    if ($dataEfetiva->gte($inicio) && $dataEfetiva->lte($limite)) {
                        $datas->push($dataEfetiva);
                    }

                    $mesRef->addMonthsNoOverflow($mesesPasso);
                }
                break;
        }

        return $datas->sort()->values();
    }

    /**
     * Sincroniza agendamentos futuros após alteração de periodicidade ou vigência.
     * Preserva estritamente visitas concluídas e em andamento.
     * Reavalia apenas visitas futuras agendadas.
     *
     * @return int Novas visitas criadas
     */
    public function sincronizarAgendamentosFuturos(Contrato $contrato): int
    {
        return DB::transaction(function () use ($contrato) {
            // Remove visitas futuras agendadas que ainda não foram executadas
            $hoje = now()->startOfDay();

            $visitasFuturas = Visita::where('contrato_id', $contrato->id)
                ->where('status', Visita::STATUS_AGENDADA)
                ->whereDate('data_hora', '>=', $hoje->toDateString())
                ->get();

            foreach ($visitasFuturas as $vf) {
                // Estorna estoques caso tenha reservado fracoes
                $vf->estornarEstoqueProdutos();
                $vf->movimentacoes()->delete();
                $vf->produtos()->detach();
                $vf->delete();
            }

            // Ordens de Serviço são emitidas manualmente sob demanda pelo gestor.
            // Não gera agendamentos automáticos no banco de dados.
            return 0;

            return 0;
        });
    }

    /**
     * Trata transições de suspensão, encerramento ou cancelamento de contratos.
     * Cancela visitas futuras ainda não executadas, preservando o histórico de atendimentos.
     */
    public function tratarSuspensaoOuCancelamento(Contrato $contrato, string $novoStatus): void
    {
        DB::transaction(function () use ($contrato, $novoStatus) {
            $hoje = now()->startOfDay();

            $visitasPendentes = Visita::where('contrato_id', $contrato->id)
                ->where('status', Visita::STATUS_AGENDADA)
                ->whereDate('data_hora', '>=', $hoje->toDateString())
                ->get();

            foreach ($visitasPendentes as $vf) {
                $vf->estornarEstoqueProdutos();
                if ($novoStatus === Contrato::STATUS_CANCELADO) {
                    $vf->status = Visita::STATUS_CANCELADA;
                    $vf->observacoes = trim(($vf->observacoes ?? '').' [Cancelada devido ao cancelamento do Contrato '.$contrato->numero.']');
                    $vf->save();
                } else {
                    // Suspenso ou Encerrado: cancela a visita pendente futura
                    $vf->status = Visita::STATUS_CANCELADA;
                    $vf->observacoes = trim(($vf->observacoes ?? '').' [Visita suspensa/cancelada por alteração de status do Contrato '.$contrato->numero.']');
                    $vf->save();
                }
            }

            // Remove movimentações financeiras de mensalidade pendentes a partir do mês corrente
            $contrato->movimentacoes()
                ->where('status', MovimentacaoFinanceira::STATUS_PENDENTE)
                ->whereDate('data_competencia', '>=', now()->startOfMonth()->toDateString())
                ->delete();

            $contrato->update(['status' => $novoStatus]);
        });
    }

    /**
     * Varredura de contratos por prazo determinado para marcar como expirados quando ultrapassarem data_fim.
     *
     * @return int Quantidade de contratos expirados
     */
    public function verificarContratosExpirados(): int
    {
        $hoje = now()->toDateString();

        $contratos = Contrato::where('status', Contrato::STATUS_ATIVO)
            ->where('tipo_vigencia', Contrato::VIGENCIA_DETERMINADO)
            ->whereDate('data_fim', '<', $hoje)
            ->get();

        foreach ($contratos as $c) {
            $this->tratarSuspensaoOuCancelamento($c, Contrato::STATUS_EXPIRADO);
        }

        return $contratos->count();
    }
}
