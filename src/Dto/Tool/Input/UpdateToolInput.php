<?php

namespace App\Dto\Tool\Input;

use App\Enum\Department;
use App\Enum\ToolStatus;

final readonly class UpdateToolInput
{
    public function __construct(
        public ?string $name = null,
        public ?int $categoryId = null,
        public ?float $monthlyCost = null,
        public ?Department $ownerDepartment = null,
        public ?ToolStatus $status = null,
        public ?string $description = null,
        public ?string $vendor = null,
        public ?string $websiteUrl = null,
    ) {
    }
}
