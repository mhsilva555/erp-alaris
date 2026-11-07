<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contrato extends Model
{
    // Status
    public const STATUS_PROPOSTA = 'proposta';

    public const STATUS_RASCUNHO = 'rascunho';

    public const STATUS_ATIVO = 'ativo';

    public const STATUS_SUSPENSO = 'suspenso';

    public const STATUS_ENCERRADO = 'encerrado';

    public const STATUS_CANCELADO = 'cancelado';

    public const STATUS_EXPIRADO = 'expirado';

    public const STATUSES = [
        self::STATUS_PROPOSTA => 'Proposta Comercial',
        self::STATUS_ATIVO => 'Ativo',
        self::STATUS_RASCUNHO => 'Rascunho',
        self::STATUS_SUSPENSO => 'Suspenso',
        self::STATUS_ENCERRADO => 'Encerrado',
        self::STATUS_CANCELADO => 'Cancelado',
        self::STATUS_EXPIRADO => 'Expirado',
    ];

    // Vigência
    public const VIGENCIA_DETERMINADO = 'determinado';

    public const VIGENCIA_INDETERMINADO = 'indeterminado';

    public const VIGENCIAS = [
        self::VIGENCIA_INDETERMINADO => 'Prazo Indeterminado',
        self::VIGENCIA_DETERMINADO => 'Prazo Determinado',
    ];

    // Periodicidade
    public const PERIODICIDADE_UNICO = 'atendimento_unico';

    public const PERIODICIDADE_SEMANAL = 'semanal';

    public const PERIODICIDADE_QUINZENAL = 'quinzenal';

    public const PERIODICIDADE_MENSAL = 'mensal';

    public const PERIODICIDADE_BIMESTRAL = 'bimestral';

    public const PERIODICIDADE_TRIMESTRAL = 'trimestral';

    public const PERIODICIDADE_SEMESTRAL = 'semestral';

    public const PERIODICIDADE_ANUAL = 'anual';

    public const PERIODICIDADE_PERSONALIZADO = 'personalizado';

    public const PERIODICIDADES = [
        self::PERIODICIDADE_MENSAL => 'Mensal',
        self::PERIODICIDADE_QUINZENAL => 'Quinzenal',
        self::PERIODICIDADE_SEMANAL => 'Semanal',
        self::PERIODICIDADE_BIMESTRAL => 'Bimestral',
        self::PERIODICIDADE_TRIMESTRAL => 'Trimestral',
        self::PERIODICIDADE_SEMESTRAL => 'Semestral',
        self::PERIODICIDADE_ANUAL => 'Anual',
        self::PERIODICIDADE_UNICO => 'Atendimento Único / Pontual',
        self::PERIODICIDADE_PERSONALIZADO => 'Personalizado (Intervalo de dias)',
    ];

    // Modelo de Cobrança
    public const MODELO_MENSAL = 'mensal';

    public const MODELO_POR_VISITA = 'por_visita';

    public const MODELO_FECHAMENTO_PERIODO = 'fechamento_periodo';

    public const MODELO_FIXO_ANUAL = 'fixo_anual';

    public const MODELOS_COBRANCA = [
        self::MODELO_MENSAL => 'Mensalidade Fixa',
        self::MODELO_POR_VISITA => 'Por Visita Executada',
        self::MODELO_FECHAMENTO_PERIODO => 'Fechamento por Período',
        self::MODELO_FIXO_ANUAL => 'Anual Fixo',
    ];

    // Dias da semana
    public const DIAS_SEMANA = [
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
        0 => 'Domingo',
    ];

    protected $fillable = [
        'numero',
        'cliente_id',
        'titulo',
        'descricao',
        'status',
        'tipo_vigencia',
        'data_inicio',
        'data_fim',
        'permite_renovacao',
        'periodicidade',
        'periodicidade_dias',
        'dia_preferencial',
        'dia_semana',
        'tipo_servico',
        'valor_total',
        'modelo_cobranca',
        'exige_nota_fiscal',
        'percentual_imposto',
        'custo_diaria_padrao',
        'custo_deslocamento_padrao',
        'meio_pagamento_id',
        'condicoes_pagamento',
        'desconto',
        'observacoes_comerciais',
        'proxima_geracao_ate',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'proxima_geracao_ate' => 'date',
            'permite_renovacao' => 'boolean',
            'exige_nota_fiscal' => 'boolean',
            'percentual_imposto' => 'decimal:2',
            'custo_diaria_padrao' => 'decimal:2',
            'custo_deslocamento_padrao' => 'decimal:2',
            'valor_total' => 'decimal:2',
            'desconto' => 'decimal:2',
            'dia_preferencial' => 'integer',
            'dia_semana' => 'integer',
            'periodicidade_dias' => 'integer',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function meioPagamento(): BelongsTo
    {
        return $this->belongsTo(MeioPagamento::class, 'meio_pagamento_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ContratoItem::class);
    }

    public function visitas(): HasMany
    {
        return $this->hasMany(Visita::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ATIVO);
    }

    public function scopePropostas(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PROPOSTA);
    }

    public function estaAtivo(): bool
    {
        return $this->status === self::STATUS_ATIVO;
    }

    public function ehProposta(): bool
    {
        return $this->status === self::STATUS_PROPOSTA;
    }

    public function estaExpirado(): bool
    {
        if ($this->tipo_vigencia === self::VIGENCIA_DETERMINADO && $this->data_fim) {
            return $this->data_fim->isPast();
        }

        return false;
    }

    public function podeAtivar(): bool
    {
        return in_array($this->status, [self::STATUS_PROPOSTA, self::STATUS_RASCUNHO, self::STATUS_SUSPENSO], true);
    }

    public function podeSuspender(): bool
    {
        return $this->status === self::STATUS_ATIVO;
    }

    public function podeCancelar(): bool
    {
        return in_array($this->status, [self::STATUS_PROPOSTA, self::STATUS_RASCUNHO, self::STATUS_ATIVO, self::STATUS_SUSPENSO], true);
    }

    public function podeEncerrar(): bool
    {
        return in_array($this->status, [self::STATUS_ATIVO, self::STATUS_SUSPENSO], true);
    }

    public function podeEditar(): bool
    {
        return ! in_array($this->status, [self::STATUS_CANCELADO, self::STATUS_ENCERRADO], true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            self::STATUS_PROPOSTA => 'badge-info',
            self::STATUS_ATIVO => 'badge-success',
            self::STATUS_RASCUNHO => 'badge-neutral',
            self::STATUS_SUSPENSO => 'badge-warning',
            self::STATUS_EXPIRADO => 'badge-danger',
            self::STATUS_ENCERRADO => 'badge-neutral',
            self::STATUS_CANCELADO => 'badge-danger',
            default => 'badge-neutral',
        };
    }

    public function vigenciaLabel(): string
    {
        return self::VIGENCIAS[$this->tipo_vigencia] ?? ucfirst($this->tipo_vigencia);
    }

    public function periodicidadeLabel(): string
    {
        if ($this->periodicidade === self::PERIODICIDADE_PERSONALIZADO) {
            return "A cada {$this->periodicidade_dias} dias";
        }

        return self::PERIODICIDADES[$this->periodicidade] ?? ucfirst($this->periodicidade);
    }

    public function modeloCobrancaLabel(): string
    {
        return self::MODELOS_COBRANCA[$this->modelo_cobranca] ?? ucfirst($this->modelo_cobranca);
    }

    public function tipoServicoLabel(): string
    {
        return Visita::TIPOS_SERVICO[$this->tipo_servico] ?? ucfirst($this->tipo_servico);
    }

    public function recalcularValorTotal(): float
    {
        $subtotal = (float) $this->itens()->sum('valor_total');
        $desconto = (float) $this->desconto;
        $total = max(0, $subtotal - $desconto);

        $this->update(['valor_total' => $total]);

        return $total;
    }

    public function valorImposto(): float
    {
        if (! $this->exige_nota_fiscal || (float) $this->percentual_imposto <= 0) {
            return 0.00;
        }

        return round((float) $this->valor_total * ((float) $this->percentual_imposto / 100), 2);
    }

    public function valorLiquidoDisponivel(): float
    {
        return round((float) $this->valor_total - $this->valorImposto(), 2);
    }

    public function visitasPrevistasPorCiclo(): int
    {
        return match ($this->periodicidade) {
            self::PERIODICIDADE_SEMANAL => 4,
            self::PERIODICIDADE_QUINZENAL => 2,
            default => 1,
        };
    }

    public function custoOperacionalPrevisto(): float
    {
        $visitas = $this->visitasPrevistasPorCiclo();
        $diaria = (float) ($this->custo_diaria_padrao ?? 0.0);
        $deslocamento = (float) ($this->custo_deslocamento_padrao ?? 0.0);

        return round(($diaria + $deslocamento) * $visitas, 2);
    }

    public function lucroLiquidoPrevisto(): float
    {
        return round($this->valorLiquidoDisponivel() - $this->custoOperacionalPrevisto(), 2);
    }

    public function margemLiquidaPrevista(): float
    {
        $bruto = (float) $this->valor_total;
        if ($bruto <= 0) {
            return 0.0;
        }

        return round(($this->lucroLiquidoPrevisto() / $bruto) * 100, 1);
    }

    public function custoTotalVisitasNoPeriodo(Carbon $inicio, Carbon $fim): float
    {
        $visitas = $this->visitas()
            ->where('status', '!=', Visita::STATUS_CANCELADA)
            ->whereDate('data_hora', '>=', $inicio->toDateString())
            ->whereDate('data_hora', '<=', $fim->toDateString())
            ->get();

        return round((float) $visitas->sum(function ($v) {
            return (float) $v->custo_produtos + (float) $v->custo_mao_de_obra + (float) $v->custo_deslocamento + (float) $v->gastos_adicionais;
        }), 2);
    }

    public function saldoVerbaNoPeriodo(Carbon $inicio, Carbon $fim): float
    {
        return round($this->valorLiquidoDisponivel() - $this->custoTotalVisitasNoPeriodo($inicio, $fim), 2);
    }

    public static function gerarProximoNumero(): string
    {
        $ano = now()->year;
        $ultimo = self::where('numero', 'like', "CTR-{$ano}-%")->orderByDesc('id')->first();

        if ($ultimo && preg_match('/CTR-\d{4}-(\d+)/', $ultimo->numero, $matches)) {
            $seq = (int) $matches[1] + 1;
        } else {
            $seq = 1;
        }

        return sprintf('CTR-%d-%04d', $ano, $seq);
    }
}
