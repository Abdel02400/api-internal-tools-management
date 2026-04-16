<?php

namespace App\Enum;

enum CategoryName: string
{
    case Communication = 'Communication';
    case Development = 'Development';
    case Design = 'Design';
    case Productivity = 'Productivity';
    case Analytics = 'Analytics';
    case Security = 'Security';
    case Marketing = 'Marketing';
    case HR = 'HR';
    case Finance = 'Finance';
    case Infrastructure = 'Infrastructure';
}
