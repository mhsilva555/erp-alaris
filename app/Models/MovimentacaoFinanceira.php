<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimentacaoFinanceira extends Model
{
    protected $table = 'movimentacoes_financeiras';

    public const TIPO_RECEITA = 'receita';

    public const TIPO_DESPESA = 'despesa';

    public const STATUS_PENDENTE = 'pendente';

    public const STATUS_PAGO = 'pago';

    public const STATUS_CANCELADO = 'cancelado';

    protected $fillable = [
        'visita_id',
        'tipo',
        'categoria',
        'descricao',
        'valor',
        'data_competencia',
        'data_pagamento',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'data_competencia' => 'date',
            'data_pagamento' => 'date',
        ];
    }

    public function visita(): BelongsTo
    {
        return $this->belongsTo(Visita::class);
    }

    public function scopeReceitas(Builder $query): Builder
    {
        return $query->where('tipo', self::TIPO_RECEITA);
    }

    public function scopeDespesas(Builder $query): Builder
    {
        return $query->where('tipo', self::TIPO_DESPESA);
    }

    public function scopePagas(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAGO);
    }

    public function scopePendentes(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDENTE);
    }
}
