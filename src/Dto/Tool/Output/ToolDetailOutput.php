<?php

namespace App\Dto\Tool\Output;

use App\Enum\Department;
use App\Enum\ToolStatus;
use DateTimeImmutable;

final readonly class ToolDetailOutput
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public ?string $vendor,
        public string $category,
        public float $monthlyCost,
        public Department $ownerDepartment,
        public ?ToolStatus $status,
        public ?string $websiteUrl,
        public int $activeUsersCount,
        public float $totalMonthlyCost,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public UsageMetricsOutput $usageMetrics,
    ) {
    }
}
