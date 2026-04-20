<?php

namespace App\Dto\Analytics\DepartmentCost\Output;

final readonly class DepartmentCostCollectionOutput
{
    /**
     * @param list<DepartmentCostOutput> $data
     */
    public function __construct(
        public array $data,
        public DepartmentCostSummary $summary,
        public ?string $message = null,
    ) {
    }
}
