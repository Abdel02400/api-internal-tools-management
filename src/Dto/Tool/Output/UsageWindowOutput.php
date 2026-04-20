<?php

namespace App\Dto\Tool\Output;

final readonly class UsageWindowOutput
{
    public function __construct(
        public int $totalSessions,
        public int $avgSessionMinutes,
    ) {
    }
}
