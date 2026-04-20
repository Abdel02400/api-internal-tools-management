<?php

namespace App\Dto\Analytics\ExpensiveTool\Query;

use App\Validator\Message\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ExpensiveToolsQuery
{
    public const DEFAULT_LIMIT = 10;
    public const MAX_LIMIT = 100;

    public function __construct(
        #[Assert\Positive(message: ValidationMessage::MUST_BE_POSITIVE)]
        #[Assert\LessThanOrEqual(value: self::MAX_LIMIT)]
        public ?int $limit = null,

        #[Assert\PositiveOrZero(message: ValidationMessage::MUST_BE_POSITIVE_OR_ZERO)]
        public ?float $minCost = null,
    ) {
    }

    public function effectiveLimit(): int
    {
        return $this->limit ?? self::DEFAULT_LIMIT;
    }

    public function hasMinCostFilter(): bool
    {
        return $this->minCost !== null;
    }
}
