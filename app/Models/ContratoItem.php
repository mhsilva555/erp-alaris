<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratoItem extends Model
{
    protected $table = 'contrato_itens';

    protected $fillable = [
        'contrato_id',
        'tipo_servico',
        'descricao',
        'quantidade',
        'valor_unitario',
        'valor_total',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'decimal:2',
            'valor_unitario' => 'decimal:2',
            'valor_total' => 'decimal:2',
        ];
    }

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function tipoServicoLabel(): string
    {
        return Visita::TIPOS_SERVICO[$this->tipo_servico] ?? ucfirst($this->tipo_servico);
    }
}
