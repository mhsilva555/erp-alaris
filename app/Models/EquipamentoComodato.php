<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipamentoComodato extends Model
{
    protected $table = 'equipamentos_comodato';

    protected $fillable = [
        'cliente_id',
        'contrato_id',
        'equipamento_id',
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

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function equipamento(): BelongsTo
    {
        return $this->belongsTo(Equipamento::class);
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function visitas(): HasMany
    {
        return $this->hasMany(Visita::class, 'comodato_id');
    }

    /**
     * Escopo para filtrar comodatos que estão operacionais em campo
     * (Comodatos avulsos sem contrato ou vinculados a contratos com status 'ativo').
     */
    public function scopeOperacionalmenteAtivos(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('contrato_id')
                ->orWhereHas('contrato', function (Builder $cq) {
                    $cq->where('status', Contrato::STATUS_ATIVO);
                });
        });
    }

    public function estaOperacionalmenteAtivo(): bool
    {
        if (! $this->contrato_id) {
            return true;
        }

        return $this->contrato && $this->contrato->status === Contrato::STATUS_ATIVO;
    }

    public function scopeReposicaoProxima(Builder $query, int $dias = 2): Builder
    {
        return $query->operacionalmenteAtivos()
            ->whereNotNull('data_proxima_reposicao')
            ->whereDate('data_proxima_reposicao', '<=', now()->addDays($dias)->toDateString());
    }

    public function estaAtrasado(): bool
    {
        if (! $this->estaOperacionalmenteAtivo()) {
            return false;
        }

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
