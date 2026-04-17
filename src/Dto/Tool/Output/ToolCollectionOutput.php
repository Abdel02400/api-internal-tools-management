<?php

namespace App\Dto\Tool\Output;

final readonly class ToolCollectionOutput
{
    /**
     * @param list<ToolOutput>     $data
     * @param array<string, mixed> $filtersApplied
     */
    public function __construct(
        public array $data,
        public int $total,
        public int $filtered,
        public array $filtersApplied,
    ) {
    }
}
