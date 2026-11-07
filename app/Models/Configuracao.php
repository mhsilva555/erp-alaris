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

    public static function dadosEmpresa(): array
    {
        $razaoSocial = static::get('empresa_razao_social', 'ALARIS SERVICE LTDA');
        $nomeFantasia = static::get('empresa_nome_fantasia', 'Alaris Service');
        $cnpj = static::get('empresa_cnpj', '38.628.132/0001-35');
        $licenca = static::get('empresa_licenca_sanitaria', 'Alvará Sanitário / Licença Ambiental');
        $telefone = static::get('empresa_telefone', '(99) 99999-9999');
        $email = static::get('empresa_email', 'alaris@alarisservice.com.br');
        $endereco = static::get('empresa_endereco', 'R. Frei Epifânio da Abadia');
        $bairro = static::get('empresa_bairro', 'Centro');
        $cidade = static::get('empresa_cidade', 'Imperatriz');
        $estado = static::get('empresa_estado', 'MA');
        $cep = static::get('empresa_cep', '65912-060');
        $slogan = static::get('empresa_slogan', 'Soluções em Controle de Pragas & Higienização');

        $partesEndereco = array_filter([
            $endereco,
            $bairro ? "Bairro {$bairro}" : null,
            ($cidade && $estado) ? "{$cidade}/{$estado}" : ($cidade ?: $estado),
            $cep ? "CEP: {$cep}" : null,
        ]);

        $enderecoCompleto = implode(' • ', $partesEndereco);

        return compact(
            'razaoSocial',
            'nomeFantasia',
            'cnpj',
            'licenca',
            'telefone',
            'email',
            'endereco',
            'bairro',
            'cidade',
            'estado',
            'cep',
            'slogan',
            'enderecoCompleto'
        );
    }
}
