<?php

namespace App\ApiResource\Analytics;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\ApiResource\QueryParameter\EnumQueryParameter;
use App\Dto\Analytics\DepartmentCost\Output\DepartmentCostCollectionOutput;
use App\Dto\Analytics\DepartmentCost\Output\DepartmentCostOutput;
use App\Enum\DepartmentCostSortBy;
use App\Enum\SortOrder;
use App\State\Provider\Analytics\DepartmentCostCollectionProvider;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

#[ApiResource(
    class: DepartmentCostOutput::class,
    shortName: self::SHORT_NAME,
    formats: [JsonEncoder::FORMAT],
    operations: [
        new GetCollection(
            uriTemplate: self::URI,
            parameters: [
                self::PARAM_SORT_BY => new EnumQueryParameter(DepartmentCostSortBy::VALUES),
                self::PARAM_ORDER => new EnumQueryParameter(SortOrder::VALUES),
            ],
            provider: DepartmentCostCollectionProvider::class,
            output: DepartmentCostCollectionOutput::class,
            paginationEnabled: false,
        ),
    ],
)]
final class DepartmentCostAnalyticsResource
{
    public const SHORT_NAME = 'DepartmentCost';
    public const URI = '/analytics/department-costs';

    public const PARAM_SORT_BY = 'sort_by';
    public const PARAM_ORDER = 'order';
}
