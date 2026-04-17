<?php

namespace App\Enum;

enum Department: string
{
    case Engineering = 'Engineering';
    case Sales = 'Sales';
    case Marketing = 'Marketing';
    case HR = 'HR';
    case Finance = 'Finance';
    case Operations = 'Operations';
    case Design = 'Design';

    public const VALUES = [
        self::Engineering->value,
        self::Sales->value,
        self::Marketing->value,
        self::HR->value,
        self::Finance->value,
        self::Operations->value,
        self::Design->value,
    ];
}
