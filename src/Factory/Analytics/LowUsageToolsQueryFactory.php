<?php

namespace App\Factory\Analytics;

use App\ApiResource\Analytics\LowUsageToolAnalyticsResource;
use App\Dto\Analytics\LowUsageTool\Query\LowUsageToolsQuery;
use App\Exception\Domain\InvalidIntegerValueException;
use App\Validator\ViolationFactory;
use App\ValueObject\Number\NullableInt;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class LowUsageToolsQueryFactory
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function create(): LowUsageToolsQuery
    {
        $query = $this->requestStack->getCurrentRequest()?->query;
        $violations = new ConstraintViolationList();

        $maxUsers = null;
        try {
            $maxUsers = NullableInt::from($query?->get(LowUsageToolAnalyticsResource::PARAM_MAX_USERS));
        } catch (InvalidIntegerValueException) {
            $violations->add(ViolationFactory::integer(
                LowUsageToolAnalyticsResource::PARAM_MAX_USERS,
                $query?->get(LowUsageToolAnalyticsResource::PARAM_MAX_USERS),
            ));
        }

        $dto = new LowUsageToolsQuery(maxUsers: $maxUsers);

        if (count($violations) > 0) {
            throw new ValidationFailedException($dto, $violations);
        }

        return $dto;
    }
}
