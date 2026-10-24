<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipamento extends Model
{
    use HasFactory;

    protected $table = 'equipamentos';

    public const TIPO_PORTA_ISCAS = 'porta_iscas';

    public const TIPO_ARMADILHA_LUMINOSA = 'armadilha_luminosa';

    public const TIPO_PULVERIZADOR = 'pulverizador';

    public const TIPO_OUTROS = 'outros';

    public const TIPOS = [
        self::TIPO_PORTA_ISCAS => 'Porta-Iscas / Túnel',
        self::TIPO_ARMADILHA_LUMINOSA => 'Armadilha Luminosa',
        self::TIPO_PULVERIZADOR => 'Pulverizador / Atomizador',
        self::TIPO_OUTROS => 'Outros Equipamentos',
    ];

    protected $fillable = [
        'nome',
        'modelo',
        'tipo',
        'quantidade_total',
        'quantidade_disponivel',
        'valor_unitario',
        'descricao',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'quantidade_total' => 'integer',
            'quantidade_disponivel' => 'integer',
            'valor_unitario' => 'decimal:2',
            'ativo' => 'boolean',
        ];
    }

    public function comodatos(): HasMany
    {
        return $this->hasMany(EquipamentoComodato::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaEquipamento::class, 'tipo', 'slug');
    }

    public function tipoLabel(): string
    {
        return $this->categoria?->nome ?? self::TIPOS[$this->tipo] ?? ucwords(str_replace('_', ' ', $this->tipo));
    }

    public function getTipoFormatadoAttribute(): string
    {
        return $this->tipoLabel();
    }

    public function getQuantidadeEmComodatoAttribute(): int
    {
        return (int) $this->comodatos()->sum('quantidade');
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeDisponiveis(Builder $query): Builder
    {
        return $query->where('ativo', true)->where('quantidade_disponivel', '>', 0);
    }
}
