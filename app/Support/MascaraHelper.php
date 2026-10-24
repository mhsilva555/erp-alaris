<?php

declare(strict_types=1);

namespace App\Support;

class MascaraHelper
{
    /**
     * Remove todos os caracteres não numéricos.
     */
    public static function apenasNumeros(?string $valor): ?string
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        $limpo = preg_replace('/\D/', '', $valor);

        return $limpo !== '' ? $limpo : null;
    }

    /**
     * Formata documento para CPF (11 dígitos) ou CNPJ (14 dígitos).
     */
    public static function formatarDocumento(?string $documento): ?string
    {
        $limpo = self::apenasNumeros($documento);
        if (! $limpo) {
            return null;
        }

        $tam = strlen($limpo);

        if ($tam === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $limpo);
        }

        if ($tam === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $limpo);
        }

        return $limpo;
    }

    /**
     * Formata telefone fixo (10 dígitos) ou celular (11 dígitos).
     */
    public static function formatarTelefone(?string $telefone): ?string
    {
        $limpo = self::apenasNumeros($telefone);
        if (! $limpo) {
            return null;
        }

        $tam = strlen($limpo);

        if ($tam === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $limpo);
        }

        if ($tam === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $limpo);
        }

        return $limpo;
    }

    /**
     * Formata CEP (8 dígitos).
     */
    public static function formatarCep(?string $cep): ?string
    {
        $limpo = self::apenasNumeros($cep);
        if (! $limpo) {
            return null;
        }

        if (strlen($limpo) === 8) {
            return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $limpo);
        }

        return $limpo;
    }
}
