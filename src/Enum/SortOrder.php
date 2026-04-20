<?php

namespace App\Enum;

enum SortOrder: string
{
    case Asc = 'asc';
    case Desc = 'desc';

    public const VALUES = [
        self::Asc->value,
        self::Desc->value,
    ];
}
