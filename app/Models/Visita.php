<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visita extends Model
{
    public const STATUS_AGENDADA = 'agendada';

    public const STATUS_EM_ANDAMENTO = 'em_andamento';

    public const STATUS_CONCLUIDA = 'concluida';

    public const STATUS_CANCELADA = 'cancelada';

    public const STATUSES = [
        self::STATUS_AGENDADA => 'Agendada',
        self::STATUS_EM_ANDAMENTO => 'Em andamento',
        self::STATUS_CONCLUIDA => 'Concluída',
        self::STATUS_CANCELADA => 'Cancelada',
    ];

    public const TIPO_VISITA_COMERCIAL = 'comercial';

    public const TIPO_VISITA_TECNICA = 'tecnica';

    public const TIPO_VISITA_DEMONSTRACAO = 'demonstracao';

    public const TIPO_VISITA_EXECUCAO = 'execucao';

    public const TIPOS_VISITA = [
        self::TIPO_VISITA_COMERCIAL => 'Visita Comercial',
        self::TIPO_VISITA_TECNICA => 'Visita Técnica',
        self::TIPO_VISITA_DEMONSTRACAO => 'Demonstração',
        self::TIPO_VISITA_EXECUCAO => 'Execução de Serviço',
    ];

    public const TIPO_SERVICO_CONTROLE_PRAGAS = 'controle_pragas';

    public const TIPO_SERVICO_LIMPEZA = 'limpeza';

    public const TIPOS_SERVICO = [
        self::TIPO_SERVICO_CONTROLE_PRAGAS => 'Controle de Pragas (Dedetização)',
        self::TIPO_SERVICO_LIMPEZA => 'Higienização e Limpeza',
    ];

    protected $fillable = [
        'cliente_id',
        'comodato_id',
        'tecnico_id',
        'data_hora',
        'status',
        'tipo_visita',
        'tipo_servico',
        'tipo_praga',
        'area_tratada',
        'observacoes',
        'proxima_visita',
        'valor_cobrado',
        'custo_produtos',
        'custo_mao_de_obra',
        'custo_deslocamento',
        'gastos_adicionais',
        'motivo_gastos_adicionais',
        'exige_nota_fiscal',
        'percentual_imposto',
        'valor_imposto',
        'lucro_bruto',
    ];

    protected function casts(): array
    {
        return [
            'data_hora' => 'datetime',
            'proxima_visita' => 'datetime',
            'valor_cobrado' => 'decimal:2',
            'custo_produtos' => 'decimal:2',
            'custo_mao_de_obra' => 'decimal:2',
            'custo_deslocamento' => 'decimal:2',
            'gastos_adicionais' => 'decimal:2',
            'exige_nota_fiscal' => 'boolean',
            'percentual_imposto' => 'decimal:2',
            'valor_imposto' => 'decimal:2',
            'lucro_bruto' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function comodato(): BelongsTo
    {
        return $this->belongsTo(EquipamentoComodato::class, 'comodato_id');
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Visita $visita) {
            $visita->estornarEstoqueProdutos();
        });
    }

    public function produtos(): BelongsToMany
    {
        return $this->belongsToMany(Produto::class, 'visita_produto')
            ->withPivot('quantidade', 'custo_proporcional', 'fracao_baixada');
    }

    /**
     * Estorna e devolve ao saldo físico do produto qualquer fração de insumo
     * que tenha sido baixada por esta ordem de serviço.
     */
    public function estornarEstoqueProdutos(): void
    {
        $this->loadMissing('produtos');
        foreach ($this->produtos as $produto) {
            $fracao = (float) ($produto->pivot->fracao_baixada ?? 0);
            if ($fracao > 0) {
                $produto->increment('quantidade_estoque', $fracao);
                $produto->pivot->fracao_baixada = 0;
                $this->produtos()->updateExistingPivot($produto->id, [
                    'fracao_baixada' => 0,
                ]);
            }
        }
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(MovimentacaoFinanceira::class);
    }

    public function movimentacaoReceita(): ?MovimentacaoFinanceira
    {
        return $this->movimentacoes->firstWhere('tipo', MovimentacaoFinanceira::TIPO_RECEITA);
    }

    public function statusRecebimento(): string
    {
        if ((float) $this->valor_cobrado <= 0) {
            return 'isento';
        }

        $receita = $this->movimentacaoReceita();
        if (! $receita) {
            return 'pendente';
        }

        return $receita->status;
    }

    public function movimentacaoDiaria(): ?MovimentacaoFinanceira
    {
        return $this->movimentacoes
            ->where('tipo', MovimentacaoFinanceira::TIPO_DESPESA)
            ->first(fn ($m) => in_array($m->categoria, ['mao_de_obra', 'diaria_tecnico']));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function tipoVisitaLabel(): string
    {
        return self::TIPOS_VISITA[$this->tipo_visita] ?? $this->tipo_visita;
    }

    public function tipoServicoLabel(): string
    {
        return self::TIPOS_SERVICO[$this->tipo_servico] ?? $this->tipo_servico;
    }

    public function custoOperacionalTotal(): float
    {
        return round(
            (float) $this->custo_produtos +
            (float) $this->custo_mao_de_obra +
            (float) $this->custo_deslocamento +
            (float) $this->gastos_adicionais,
            2
        );
    }

    public function margemPercentual(): float
    {
        $cobrado = (float) $this->valor_cobrado;
        if ($cobrado <= 0) {
            return 0.0;
        }

        return round(((float) $this->lucro_bruto / $cobrado) * 100, 1);
    }
}
