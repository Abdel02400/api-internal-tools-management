<?php

namespace App\Dto\Analytics\ExpensiveTool\Output;

final readonly class ExpensiveToolsCollectionOutput
{
    /**
     * @param list<ExpensiveToolOutput> $data
     */
    public function __construct(
        public array $data,
        public ExpensiveToolsAnalysis $analysis,
        public ?string $message = null,
    ) {
    }
}
