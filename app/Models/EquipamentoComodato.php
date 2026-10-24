<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipamentoComodato extends Model
{
    protected $table = 'equipamentos_comodato';

    protected $fillable = [
        'cliente_id',
        'produto_id',
        'nome_equipamento',
        'quantidade',
        'periodicidade_dias',
        'data_instalacao',
        'data_ultima_reposicao',
        'data_proxima_reposicao',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'integer',
            'periodicidade_dias' => 'integer',
            'data_instalacao' => 'date',
            'data_ultima_reposicao' => 'date',
            'data_proxima_reposicao' => 'date',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function scopeReposicaoProxima(Builder $query, int $dias = 2): Builder
    {
        return $query->where('data_proxima_reposicao', '<=', now()->addDays($dias)->toDateString());
    }

    public function estaAtrasado(): bool
    {
        return $this->data_proxima_reposicao && $this->data_proxima_reposicao->isPast() && ! $this->data_proxima_reposicao->isToday();
    }

    public function registrarReposicao(?Carbon $data = null): void
    {
        $data = $data ?? now();
        $this->data_ultima_reposicao = $data->toDateString();
        $this->data_proxima_reposicao = $data->copy()->addDays($this->periodicidade_dias)->toDateString();
        $this->save();
    }
}
