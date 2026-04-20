<?php

namespace App\Dto\Tool\Query;

use App\ApiResource\Tool\ToolResource;
use App\Entity\Category;
use App\Enum\SortBy;
use App\Enum\SortOrder;
use App\Validator\Message\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class ListToolsQuery
{
    public const DEFAULT_PAGE = 1;
    public const DEFAULT_LIMIT = 10;
    public const MAX_LIMIT = 100;
    public const DEFAULT_ORDER = SortOrder::Asc;

    public function __construct(
        // department and status are validated upstream by API Platform's schema
        // validator via the `enum` declared on their QueryParameter in ToolResource.
        public ?string $department = null,

        public ?string $status = null,

        #[Assert\PositiveOrZero(message: ValidationMessage::MUST_BE_POSITIVE_OR_ZERO)]
        public ?float $minCost = null,

        #[Assert\PositiveOrZero(message: ValidationMessage::MUST_BE_POSITIVE_OR_ZERO)]
        public ?float $maxCost = null,

        #[Assert\Length(max: Category::MAX_NAME_LENGTH, maxMessage: ValidationMessage::VALUE_TOO_LONG)]
        public ?string $category = null,

        #[Assert\Positive(message: ValidationMessage::MUST_BE_POSITIVE)]
        public ?int $page = null,

        #[Assert\Positive(message: ValidationMessage::MUST_BE_POSITIVE)]
        #[Assert\LessThanOrEqual(value: self::MAX_LIMIT)]
        public ?int $limit = null,

        // sortBy and order are validated upstream by API Platform's schema
        // validator via the `enum` declared on their QueryParameter in ToolResource.
        public ?SortBy $sortBy = null,

        public ?SortOrder $order = null,
    ) {
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if ($this->minCost !== null && $this->maxCost !== null && $this->minCost > $this->maxCost) {
            $context->buildViolation(ValidationMessage::MIN_COST_GREATER_THAN_MAX)
                ->atPath(ToolResource::PARAM_MIN_COST)
                ->addViolation();
        }
    }

    /**
     * @return array<string, string|float|null>
     */
    private function filters(): array
    {
        return [
            ToolResource::PARAM_DEPARTMENT => $this->department,
            ToolResource::PARAM_STATUS => $this->status,
            ToolResource::PARAM_MIN_COST => $this->minCost,
            ToolResource::PARAM_MAX_COST => $this->maxCost,
            ToolResource::PARAM_CATEGORY => $this->category,
        ];
    }

    public function hasFilters(): bool
    {
        foreach ($this->filters() as $value) {
            if ($value !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string|float>
     */
    public function toFilterArray(): array
    {
        return array_filter(
            $this->filters(),
            static fn (mixed $value): bool => $value !== null,
        );
    }

    public function hasPagination(): bool
    {
        return $this->page !== null || $this->limit !== null;
    }

    public function effectivePage(): int
    {
        return $this->page ?? self::DEFAULT_PAGE;
    }

    public function effectiveLimit(): int
    {
        return $this->limit ?? self::DEFAULT_LIMIT;
    }

    public function hasSort(): bool
    {
        return $this->sortBy !== null || $this->order !== null;
    }

    public function effectiveOrder(): SortOrder
    {
        return $this->order ?? self::DEFAULT_ORDER;
    }
}
