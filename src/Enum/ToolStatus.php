<?php

namespace App\Enum;

enum ToolStatus: string
{
    case Active = 'active';
    case Deprecated = 'deprecated';
    case Trial = 'trial';

    public const VALUES = [
        self::Active->value,
        self::Deprecated->value,
        self::Trial->value,
    ];
}
