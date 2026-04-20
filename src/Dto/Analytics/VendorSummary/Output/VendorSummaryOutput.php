<?php

namespace App\Dto\Analytics\VendorSummary\Output;

use App\Enum\VendorEfficiency;

final readonly class VendorSummaryOutput
{
    public function __construct(
        public string $vendor,
        public int $toolsCount,
        public float $totalMonthlyCost,
        public int $totalUsers,
        public string $departments,
        public ?float $averageCostPerUser,
        public VendorEfficiency $vendorEfficiency,
    ) {
    }
}
