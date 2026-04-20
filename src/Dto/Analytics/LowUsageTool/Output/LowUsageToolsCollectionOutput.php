<?php

namespace App\Dto\Analytics\LowUsageTool\Output;

final readonly class LowUsageToolsCollectionOutput
{
    /**
     * @param list<LowUsageToolOutput> $data
     */
    public function __construct(
        public array $data,
        public LowUsageToolsSavingsAnalysis $savingsAnalysis,
        public ?string $message = null,
    ) {
    }
}
