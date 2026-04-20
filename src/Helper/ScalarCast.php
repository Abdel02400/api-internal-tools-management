<?php

namespace App\Helper;

/**
 * Narrowing helpers pour les valeurs `mixed` retournées par les requêtes DBAL natives
 * (MySQL renvoie les agrégats/DECIMAL comme string via PDO).
 */
final class ScalarCast
{
    private function __construct()
    {
    }

    public static function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    public static function toFloat(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    public static function toString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
