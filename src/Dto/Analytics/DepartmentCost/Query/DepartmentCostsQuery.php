<?php

namespace App\Dto\Analytics\DepartmentCost\Query;

use App\Enum\DepartmentCostSortBy;
use App\Enum\SortOrder;

final readonly class DepartmentCostsQuery
{
    public const DEFAULT_SORT_BY = DepartmentCostSortBy::TotalCost;
    public const DEFAULT_ORDER = SortOrder::Desc;

    public function __construct(
        public ?DepartmentCostSortBy $sortBy = null,
        public ?SortOrder $order = null,
    ) {
    }

    public function effectiveSortBy(): DepartmentCostSortBy
    {
        return $this->sortBy ?? self::DEFAULT_SORT_BY;
    }

    public function effectiveOrder(): SortOrder
    {
        return $this->order ?? self::DEFAULT_ORDER;
    }
}
