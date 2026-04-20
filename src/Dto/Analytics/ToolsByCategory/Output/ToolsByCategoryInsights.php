<?php

namespace App\Dto\Analytics\ToolsByCategory\Output;

final readonly class ToolsByCategoryInsights
{
    public function __construct(
        public ?string $mostExpensiveCategory = null,
        public ?string $mostEfficientCategory = null,
    ) {
    }
}
