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
}
