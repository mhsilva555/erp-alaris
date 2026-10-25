<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produto extends Model
{
    public const NATUREZA_LIQUIDO = 'liquido';

    public const NATUREZA_SOLIDO = 'solido';

    public const NATUREZA_UNIDADE = 'unidade';

    public const UNIDADES_ARMAZENAMENTO = [
        'L' => 'L (Litro)',
        'mL' => 'mL (Mililitro)',
        'Gal' => 'Gal (Galão)',
        'Bombona' => 'Bombona',
        'Frasco' => 'Frasco',
        'Kg' => 'Kg (Quilograma)',
        'g' => 'g (Grama)',
        'Un' => 'Un (Unidade)',
        'Cx' => 'Cx (Caixa)',
        'Pct' => 'Pct (Pacote)',
    ];

    public const UNIDADES_APLICACAO = [
        'mL' => 'mL (Mililitro)',
        'L' => 'L (Litro)',
        'g' => 'g (Grama)',
        'Kg' => 'Kg (Quilograma)',
        'Un' => 'Un (Unidade)',
        'Dose' => 'Dose',
        'Sachê' => 'Sachê',
        'Pastilha' => 'Pastilha',
        'Bloco' => 'Bloco',
    ];

    protected $fillable = [
        'nome',
        'natureza',
        'unidade',
        'unidade_armazenamento',
        'unidade_aplicacao',
        'fator_conversao',
        'quantidade_estoque',
        'estoque_minimo',
        'preco_custo_unitario',
        'descricao',
    ];

    protected function casts(): array
    {
        return [
            'fator_conversao' => 'decimal:4',
            'quantidade_estoque' => 'decimal:2',
            'estoque_minimo' => 'decimal:2',
            'preco_custo_unitario' => 'decimal:2',
        ];
    }

    public function visitas(): BelongsToMany
    {
        return $this->belongsToMany(Visita::class, 'visita_produto')
            ->withPivot('quantidade', 'custo_proporcional');
    }

    public function comodatos(): HasMany
    {
        return $this->hasMany(EquipamentoComodato::class);
    }

    public function estoqueBaixo(): bool
    {
        return $this->quantidade_estoque <= $this->estoque_minimo;
    }

    public function calcularCustoDose(float $dose): float
    {
        $fator = (float) $this->fator_conversao;
        $fracao = $fator > 0 ? ($dose / $fator) : $dose;

        return round($fracao * (float) $this->preco_custo_unitario, 2);
    }

    public function calcularFracaoBaixa(float $dose): float
    {
        $fator = (float) $this->fator_conversao;

        return $fator > 0 ? round($dose / $fator, 4) : $dose;
    }
}
