<?php

namespace App\Dto\Analytics\LowUsageTool\Output;

final readonly class LowUsageToolsSavingsAnalysis
{
    public function __construct(
        public int $totalUnderutilizedTools,
        public float $potentialMonthlySavings,
        public float $potentialAnnualSavings,
    ) {
    }
}
