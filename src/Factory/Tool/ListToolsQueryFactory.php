<?php

namespace App\Factory\Tool;

use App\ApiResource\Tool\ToolResource;
use App\Dto\Tool\Query\ListToolsQuery;
use App\Enum\SortBy;
use App\Enum\SortOrder;
use App\Exception\Domain\InvalidIntegerValueException;
use App\Exception\Domain\InvalidNumericValueException;
use App\Validator\ViolationFactory;
use App\ValueObject\Number\NullableFloat;
use App\ValueObject\Number\NullableInt;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class ListToolsQueryFactory
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function create(): ListToolsQuery
    {
        $query = $this->requestStack->getCurrentRequest()?->query;
        $violations = new ConstraintViolationList();

        $minCost = $this->parseFloat(
            $query?->get(ToolResource::PARAM_MIN_COST),
            ToolResource::PARAM_MIN_COST,
            $violations,
        );
        $maxCost = $this->parseFloat(
            $query?->get(ToolResource::PARAM_MAX_COST),
            ToolResource::PARAM_MAX_COST,
            $violations,
        );
        $page = $this->parseInt(
            $query?->get(ToolResource::PARAM_PAGE),
            ToolResource::PARAM_PAGE,
            $violations,
        );
        $limit = $this->parseInt(
            $query?->get(ToolResource::PARAM_LIMIT),
            ToolResource::PARAM_LIMIT,
            $violations,
        );

        $dto = new ListToolsQuery(
            department: $query?->get(ToolResource::PARAM_DEPARTMENT),
            status: $query?->get(ToolResource::PARAM_STATUS),
            minCost: $minCost,
            maxCost: $maxCost,
            category: $query?->get(ToolResource::PARAM_CATEGORY),
            page: $page,
            limit: $limit,
            sortBy: $this->parseEnum($query?->get(ToolResource::PARAM_SORT_BY), SortBy::class),
            order: $this->parseEnum($query?->get(ToolResource::PARAM_ORDER), SortOrder::class),
        );

        if (count($violations) > 0) {
            throw new ValidationFailedException($dto, $violations);
        }

        return $dto;
    }

    private function parseFloat(mixed $raw, string $field, ConstraintViolationList $violations): ?float
    {
        try {
            return NullableFloat::from($raw);
        } catch (InvalidNumericValueException) {
            $violations->add(ViolationFactory::numeric($field, $raw));
            return null;
        }
    }

    private function parseInt(mixed $raw, string $field, ConstraintViolationList $violations): ?int
    {
        try {
            return NullableInt::from($raw);
        } catch (InvalidIntegerValueException) {
            $violations->add(ViolationFactory::integer($field, $raw));
            return null;
        }
    }

    /**
     * @template T of \BackedEnum
     *
     * @param class-string<T> $enumClass
     *
     * @return T|null
     */
    private function parseEnum(mixed $raw, string $enumClass): ?\BackedEnum
    {
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        return $enumClass::tryFrom($raw);
    }
}
