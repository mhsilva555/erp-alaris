<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracao extends Model
{
    protected $table = 'configuracoes';

    protected $fillable = [
        'chave',
        'valor',
        'descricao',
    ];

    public static function get(string $chave, ?string $default = null): ?string
    {
        $config = static::where('chave', $chave)->first();

        return $config ? $config->valor : $default;
    }

    public static function set(string $chave, ?string $valor, ?string $descricao = null): self
    {
        return static::updateOrCreate(
            ['chave' => $chave],
            [
                'valor' => $valor,
                'descricao' => $descricao ?? $chave,
            ]
        );
    }
}
