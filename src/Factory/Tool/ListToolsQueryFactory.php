<?php

namespace App\Factory\Tool;

use App\ApiResource\Tool\ToolResource;
use App\Dto\Tool\Query\ListToolsQuery;
use App\Exception\Domain\InvalidNumericValueException;
use App\Validator\ViolationFactory;
use App\ValueObject\Number\NullableFloat;
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

        $dto = new ListToolsQuery(
            department: $query?->get(ToolResource::PARAM_DEPARTMENT),
            status: $query?->get(ToolResource::PARAM_STATUS),
            minCost: $minCost,
            maxCost: $maxCost,
            category: $query?->get(ToolResource::PARAM_CATEGORY),
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
}
