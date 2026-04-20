<?php

namespace App\Dto\Tool\Output;

final readonly class ToolCollectionOutput
{
    /**
     * @param list<ToolOutput>           $data
     * @param array<string, mixed>|null  $filtersApplied
     * @param array<string, mixed>|null  $paginationApplied
     * @param array<string, mixed>|null  $sortApplied
     */
    public function __construct(
        public array $data,
        public int $total,
        public ?int $filtered = null,
        public ?array $filtersApplied = null,
        public ?array $paginationApplied = null,
        public ?array $sortApplied = null,
        public ?string $message = null,
    ) {
    }
}
