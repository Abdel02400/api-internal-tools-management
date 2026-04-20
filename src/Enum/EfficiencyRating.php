<?php

namespace App\Enum;

enum EfficiencyRating: string
{
    case Excellent = 'excellent';
    case Good = 'good';
    case Average = 'average';
    case Low = 'low';

    public const VALUES = [
        self::Excellent->value,
        self::Good->value,
        self::Average->value,
        self::Low->value,
    ];
}
