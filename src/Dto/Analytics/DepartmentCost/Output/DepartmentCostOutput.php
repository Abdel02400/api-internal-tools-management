<?php

namespace App\Dto\Analytics\DepartmentCost\Output;

final readonly class DepartmentCostOutput
{
    public function __construct(
        public string $department,
        public float $totalCost,
        public int $toolsCount,
        public int $totalUsers,
        public float $averageCostPerTool,
        public float $costPercentage,
    ) {
    }
}
