<?php

namespace App\Helper;

final class NumberFormatter
{
    public const MONEY_DECIMALS = 2;
    public const PERCENT_DECIMALS = 1;

    private function __construct()
    {
    }

    public static function money(float $value): float
    {
        return round($value, self::MONEY_DECIMALS);
    }

    public static function percent(float $value): float
    {
        return round($value, self::PERCENT_DECIMALS);
    }
}
