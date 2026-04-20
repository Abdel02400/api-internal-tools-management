<?php

namespace App\Dto\Analytics\ToolsByCategory\Output;

final readonly class ToolsByCategoryOutput
{
    public function __construct(
        public string $categoryName,
        public int $toolsCount,
        public float $totalCost,
        public int $totalUsers,
        public float $percentageOfBudget,
        public ?float $averageCostPerUser,
    ) {
    }
}
