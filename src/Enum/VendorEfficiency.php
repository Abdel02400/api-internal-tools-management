<?php

namespace App\Enum;

enum VendorEfficiency: string
{
    case Excellent = 'excellent';
    case Good = 'good';
    case Average = 'average';
    case Poor = 'poor';

    public const VALUES = [
        self::Excellent->value,
        self::Good->value,
        self::Average->value,
        self::Poor->value,
    ];
}
