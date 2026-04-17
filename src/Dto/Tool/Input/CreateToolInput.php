<?php

namespace App\Dto\Tool\Input;

use App\Enum\Department;

final readonly class CreateToolInput
{
    public function __construct(
        public string $name,
        public int $categoryId,
        public float $monthlyCost,
        public Department $ownerDepartment,
        public ?string $description = null,
        public ?string $vendor = null,
        public ?string $websiteUrl = null,
    ) {
    }
}
