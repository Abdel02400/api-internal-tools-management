<?php

namespace App\Dto\Analytics\LowUsageTool\Output;

use App\Enum\Department;
use App\Enum\WarningLevel;

final readonly class LowUsageToolOutput
{
    public function __construct(
        public int $id,
        public string $name,
        public float $monthlyCost,
        public int $activeUsersCount,
        public ?float $costPerUser,
        public Department $department,
        public ?string $vendor,
        public WarningLevel $warningLevel,
        public string $potentialAction,
    ) {
    }
}
