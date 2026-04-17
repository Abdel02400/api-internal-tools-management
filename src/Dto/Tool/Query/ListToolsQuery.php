<?php

namespace App\Dto\Tool\Query;

use App\ApiResource\Tool\ToolResource;
use App\Entity\Category;
use App\Validator\Message\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class ListToolsQuery
{
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
}
