<?php

namespace App\Dto\Analytics\ExpensiveTool\Output;

final readonly class ExpensiveToolsAnalysis
{
    public function __construct(
        public int $totalToolsAnalyzed,
        public float $avgCostPerUserCompany,
        public float $potentialSavingsIdentified,
    ) {
    }
}
