<?php

namespace App\Dto\Analytics\LowUsageTool\Query;

use App\Validator\Message\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class LowUsageToolsQuery
{
    public const DEFAULT_MAX_USERS = 5;

    public function __construct(
        #[Assert\GreaterThanOrEqual(value: 0, message: ValidationMessage::MUST_BE_POSITIVE_OR_ZERO)]
        public ?int $maxUsers = null,
    ) {
    }

    public function effectiveMaxUsers(): int
    {
        return $this->maxUsers ?? self::DEFAULT_MAX_USERS;
    }
}
