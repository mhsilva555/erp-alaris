<?php

namespace App\Models;

use App\Support\MascaraHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tecnico extends Model
{
    protected $fillable = [
        'nome',
        'telefone',
        'email',
        'especialidade',
        'valor_diaria_padrao',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'valor_diaria_padrao' => 'decimal:2',
        ];
    }

    protected function telefone(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => MascaraHelper::apenasNumeros($value),
        );
    }

    public function getTelefoneFormatadoAttribute(): ?string
    {
        return MascaraHelper::formatarTelefone($this->telefone);
    }

    public function visitas(): HasMany
    {
        return $this->hasMany(Visita::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
