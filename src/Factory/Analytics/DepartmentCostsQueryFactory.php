<?php

namespace App\Factory\Analytics;

use App\ApiResource\Analytics\DepartmentCostAnalyticsResource;
use App\Dto\Analytics\DepartmentCost\Query\DepartmentCostsQuery;
use App\Enum\DepartmentCostSortBy;
use App\Enum\SortOrder;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class DepartmentCostsQueryFactory
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function create(): DepartmentCostsQuery
    {
        $query = $this->requestStack->getCurrentRequest()?->query;

        $sortByRaw = $query?->get(DepartmentCostAnalyticsResource::PARAM_SORT_BY);
        $orderRaw = $query?->get(DepartmentCostAnalyticsResource::PARAM_ORDER);

        return new DepartmentCostsQuery(
            sortBy: is_string($sortByRaw) ? DepartmentCostSortBy::tryFrom($sortByRaw) : null,
            order: is_string($orderRaw) ? SortOrder::tryFrom($orderRaw) : null,
        );
    }
}
