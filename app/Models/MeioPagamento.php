<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeioPagamento extends Model
{
    use HasFactory;

    protected $table = 'meios_pagamento';

    protected $fillable = [
        'nome',
        'codigo',
        'descricao',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class, 'meio_pagamento_id');
    }
}
