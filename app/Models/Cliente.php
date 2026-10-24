<?php

namespace App\Models;

use App\Support\MascaraHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    public const STATUS_ATIVO = 'ativo';

    public const STATUS_NEGOCIACAO = 'em_negociacao';

    public const STATUS_PROSPECCAO = 'prospeccao';

    public const STATUS_RETORNAR_3_MESES = 'retornar_3_meses';

    public const STATUS_CONTRATO_CONCORRENTE = 'contrato_concorrente';

    public const STATUS_INATIVO = 'inativo';

    protected $fillable = [
        'nome',
        'documento',
        'email',
        'telefone',
        'endereco',
        'cidade',
        'estado',
        'cep',
        'origem',
        'periodicidade',
        'status_prospeccao',
        'data_followup',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'data_followup' => 'date',
        ];
    }

    protected function documento(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => MascaraHelper::apenasNumeros($value),
        );
    }

    protected function telefone(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => MascaraHelper::apenasNumeros($value),
        );
    }

    protected function cep(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => MascaraHelper::apenasNumeros($value),
        );
    }

    public function getDocumentoFormatadoAttribute(): ?string
    {
        return MascaraHelper::formatarDocumento($this->documento);
    }

    public function getTelefoneFormatadoAttribute(): ?string
    {
        return MascaraHelper::formatarTelefone($this->telefone);
    }

    public function getCepFormatadoAttribute(): ?string
    {
        return MascaraHelper::formatarCep($this->cep);
    }

    public function visitas(): HasMany
    {
        return $this->hasMany(Visita::class);
    }

    public function comodatos(): HasMany
    {
        return $this->hasMany(EquipamentoComodato::class);
    }

    public function scopeComFollowupPendente(Builder $query): Builder
    {
        return $query->whereNotNull('data_followup');
    }

    public function getCidadeEstadoAttribute(): string
    {
        if ($this->cidade && $this->estado) {
            return "{$this->cidade} / {$this->estado}";
        }

        return $this->cidade ?? $this->estado ?? 'Não informada';
    }

    public function getStatusProspeccaoLabelAttribute(): string
    {
        return match ($this->status_prospeccao) {
            self::STATUS_RETORNAR_3_MESES => 'Retornar em 3 meses',
            self::STATUS_CONTRATO_CONCORRENTE => 'Contrato com concorrente',
            self::STATUS_NEGOCIACAO => 'Em negociação',
            self::STATUS_PROSPECCAO => 'Prospecção ativa',
            self::STATUS_ATIVO => 'Cliente ativo',
            self::STATUS_INATIVO => 'Inativo',
            default => $this->status_prospeccao ?? 'Não definido',
        };
    }
}
