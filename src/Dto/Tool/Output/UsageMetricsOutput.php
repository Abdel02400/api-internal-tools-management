<?php

namespace App\Dto\Tool\Output;

final readonly class UsageMetricsOutput
{
    public function __construct(
        public int $totalSessions,
        public int $avgSessionMinutes,
    ) {
    }
}
