<?php

namespace App\Dto\Analytics\VendorSummary\Output;

final readonly class VendorSummaryCollectionOutput
{
    /**
     * @param list<VendorSummaryOutput> $data
     */
    public function __construct(
        public array $data,
        public VendorSummaryInsights $vendorInsights,
        public ?string $message = null,
    ) {
    }
}
