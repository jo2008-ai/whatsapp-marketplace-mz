<?php

namespace App\Config;

class PlanoConfig
{
    private const PLANOS = [
        'gratuito' => [
            'nome' => 'Gratuito',
            'preco_mensal' => 0,
            'max_produtos' => 10,
            'max_numeros' => 1,
            'trial_dias' => 0,
        ],
        'basico' => [
            'nome' => 'Básico',
            'preco_mensal' => 500,
            'max_produtos' => 50,
            'max_numeros' => 1,
            'trial_dias' => 7,
        ],
        'profissional' => [
            'nome' => 'Profissional',
            'preco_mensal' => 1500,
            'max_produtos' => 200,
            'max_numeros' => 3,
            'trial_dias' => 7,
        ],
        'empresarial' => [
            'nome' => 'Empresarial',
            'preco_mensal' => 5000,
            'max_produtos' => -1,
            'max_numeros' => 10,
            'trial_dias' => 14,
        ],
    ];

    /** @return array<string, int|string> */
    public static function obter(string $plano): array
    {
        return self::PLANOS[$plano] ?? self::PLANOS['gratuito'];
    }

    /** @return array<string, array<string, int|string>> */
    public static function todos(): array
    {
        return self::PLANOS;
    }

    public static function obterLimiteProdutos(string $plano): int
    {
        return (int) self::obter($plano)['max_produtos'];
    }

    public static function obterLimiteNumeros(string $plano): int
    {
        return (int) self::obter($plano)['max_numeros'];
    }

    public static function obterPreco(string $plano): float
    {
        return (float) self::obter($plano)['preco_mensal'];
    }

    public static function obterTrialDias(string $plano): int
    {
        return (int) self::obter($plano)['trial_dias'];
    }

    public static function planoValido(string $plano): bool
    {
        return isset(self::PLANOS[$plano]);
    }

    /** @return array<int, string> */
    public static function planosDisponiveis(): array
    {
        return array_keys(self::PLANOS);
    }
}
