<?php

namespace App\Dto\Tool\Output;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class UsageMetricsOutput
{
    public function __construct(
        #[SerializedName('last_30_days')]
        public UsageWindowOutput $last30Days,
    ) {
    }
}
