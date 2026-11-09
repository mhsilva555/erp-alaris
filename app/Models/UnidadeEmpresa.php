<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\MascaraHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadeEmpresa extends Model
{
    protected $table = 'unidades_empresa';

    public const TIPO_MATRIZ = 'matriz';

    public const TIPO_FILIAL = 'filial';

    public const TIPOS = [
        self::TIPO_MATRIZ => 'Matriz / Sede Principal',
        self::TIPO_FILIAL => 'Filial / Unidade Regional',
    ];

    protected $fillable = [
        'nome',
        'tipo',
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'licenca_sanitaria',
        'telefone',
        'email',
        'website',
        'slogan',
        'cep',
        'endereco',
        'bairro',
        'cidade',
        'estado',
        'padrao',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'padrao' => 'boolean',
            'ativo' => 'boolean',
        ];
    }

    protected function cnpj(): Attribute
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

    protected function estado(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value ? mb_strtoupper(trim($value)) : null,
        );
    }

    public function getCnpjFormatadoAttribute(): ?string
    {
        return MascaraHelper::formatarDocumento($this->cnpj) ?? $this->cnpj;
    }

    public function getTelefoneFormatadoAttribute(): ?string
    {
        return MascaraHelper::formatarTelefone($this->telefone) ?? $this->telefone;
    }

    public function getCepFormatadoAttribute(): ?string
    {
        return MascaraHelper::formatarCep($this->cep) ?? $this->cep;
    }

    public function getCidadeEstadoAttribute(): string
    {
        if ($this->cidade && $this->estado) {
            return "{$this->cidade}/{$this->estado}";
        }

        return $this->cidade ?: ($this->estado ?: 'Não informada');
    }

    public function getEnderecoCompletoAttribute(): string
    {
        $partes = array_filter([
            $this->endereco,
            $this->bairro ? "Bairro {$this->bairro}" : null,
            ($this->cidade && $this->estado) ? "{$this->cidade}/{$this->estado}" : ($this->cidade ?: $this->estado),
            $this->cep_formatado ? "CEP: {$this->cep_formatado}" : null,
        ]);

        return implode(' • ', $partes);
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst((string) $this->tipo);
    }

    public function ehMatriz(): bool
    {
        return $this->tipo === self::TIPO_MATRIZ;
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class, 'unidade_id');
    }

    public function visitas(): HasMany
    {
        return $this->hasMany(Visita::class, 'unidade_id');
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public static function padraoAtiva(): ?self
    {
        return static::where('ativo', true)
            ->orderByDesc('padrao')
            ->orderByRaw("CASE WHEN tipo = 'matriz' THEN 0 ELSE 1 END ASC")
            ->orderBy('id')
            ->first();
    }

    /**
     * Retorna o array padronizado de informações empresariais para impressão no cabeçalho,
     * assinaturas e rodapé de Propostas Comerciais, Contratos e Ordens de Serviço (O.S.).
     */
    public function dadosParaDocumento(): array
    {
        return [
            'unidadeId' => $this->id,
            'nomeUnidade' => $this->nome,
            'tipoUnidade' => $this->tipo,
            'tipoUnidadeLabel' => $this->tipoLabel(),
            'razaoSocial' => $this->razao_social ?: 'ALARIS SERVICE LTDA',
            'nomeFantasia' => $this->nome_fantasia ?: 'Alaris Service',
            'cnpj' => $this->cnpj_formatado ?: '38.628.132/0001-35',
            'licenca' => $this->licenca_sanitaria,
            'telefone' => $this->telefone_formatado,
            'email' => $this->email,
            'website' => $this->website,
            'endereco' => $this->endereco,
            'bairro' => $this->bairro,
            'cidade' => $this->cidade,
            'estado' => $this->estado,
            'cep' => $this->cep_formatado,
            'slogan' => $this->slogan ?: 'Soluções em Controle de Pragas & Higienização',
            'enderecoCompleto' => $this->endereco_completo,
        ];
    }
}
