<?php

namespace App\Enum;

enum DepartmentCostSortBy: string
{
    case TotalCost = 'total_cost';
    case ToolsCount = 'tools_count';
    case TotalUsers = 'total_users';
    case AverageCostPerTool = 'average_cost_per_tool';
    case Department = 'department';

    public const VALUES = [
        self::TotalCost->value,
        self::ToolsCount->value,
        self::TotalUsers->value,
        self::AverageCostPerTool->value,
        self::Department->value,
    ];
}
