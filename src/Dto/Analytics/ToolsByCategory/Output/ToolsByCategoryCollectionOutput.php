<?php

namespace App\Dto\Analytics\ToolsByCategory\Output;

final readonly class ToolsByCategoryCollectionOutput
{
    /**
     * @param list<ToolsByCategoryOutput> $data
     */
    public function __construct(
        public array $data,
        public ToolsByCategoryInsights $insights,
        public ?string $message = null,
    ) {
    }
}
