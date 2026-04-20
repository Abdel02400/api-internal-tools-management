<?php

namespace App\Dto\Analytics\ExpensiveTool\Output;

use App\Enum\Department;
use App\Enum\EfficiencyRating;

final readonly class ExpensiveToolOutput
{
    public function __construct(
        public int $id,
        public string $name,
        public float $monthlyCost,
        public int $activeUsersCount,
        public ?float $costPerUser,
        public Department $department,
        public ?string $vendor,
        public EfficiencyRating $efficiencyRating,
    ) {
    }
}
