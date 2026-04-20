<?php

namespace App\Enum;

enum SortBy: string
{
    case Cost = 'cost';
    case Name = 'name';
    case Date = 'date';

    public const VALUES = [
        self::Cost->value,
        self::Name->value,
        self::Date->value,
    ];
}
