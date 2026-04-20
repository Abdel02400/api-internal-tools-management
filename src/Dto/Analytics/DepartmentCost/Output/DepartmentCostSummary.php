<?php

namespace App\Dto\Analytics\DepartmentCost\Output;

final readonly class DepartmentCostSummary
{
    public function __construct(
        public float $totalCompanyCost,
        public ?int $departmentsCount = null,
        public ?string $mostExpensiveDepartment = null,
    ) {
    }
}
