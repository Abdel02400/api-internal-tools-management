<?php

namespace App\Factory\Analytics;

use App\ApiResource\Analytics\ExpensiveToolAnalyticsResource;
use App\Dto\Analytics\ExpensiveTool\Query\ExpensiveToolsQuery;
use App\Exception\Domain\InvalidIntegerValueException;
use App\Exception\Domain\InvalidNumericValueException;
use App\Validator\ViolationFactory;
use App\ValueObject\Number\NullableFloat;
use App\ValueObject\Number\NullableInt;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class ExpensiveToolsQueryFactory
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function create(): ExpensiveToolsQuery
    {
        $query = $this->requestStack->getCurrentRequest()?->query;
        $violations = new ConstraintViolationList();

        $limit = null;
        try {
            $limit = NullableInt::from($query?->get(ExpensiveToolAnalyticsResource::PARAM_LIMIT));
        } catch (InvalidIntegerValueException) {
            $violations->add(ViolationFactory::integer(
                ExpensiveToolAnalyticsResource::PARAM_LIMIT,
                $query?->get(ExpensiveToolAnalyticsResource::PARAM_LIMIT),
            ));
        }

        $minCost = null;
        try {
            $minCost = NullableFloat::from($query?->get(ExpensiveToolAnalyticsResource::PARAM_MIN_COST));
        } catch (InvalidNumericValueException) {
            $violations->add(ViolationFactory::numeric(
                ExpensiveToolAnalyticsResource::PARAM_MIN_COST,
                $query?->get(ExpensiveToolAnalyticsResource::PARAM_MIN_COST),
            ));
        }

        $dto = new ExpensiveToolsQuery(limit: $limit, minCost: $minCost);

        if (count($violations) > 0) {
            throw new ValidationFailedException($dto, $violations);
        }

        return $dto;
    }
}
