<?php

namespace App\Dto\Analytics\VendorSummary\Output;

final readonly class VendorSummaryInsights
{
    public function __construct(
        public int $singleToolVendors = 0,
        public ?string $mostExpensiveVendor = null,
        public ?string $mostEfficientVendor = null,
    ) {
    }
}
