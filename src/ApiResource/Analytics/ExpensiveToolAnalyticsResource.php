<?php

namespace App\ApiResource\Analytics;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\ApiResource\QueryParameter\PositiveIntegerQueryParameter;
use App\ApiResource\QueryParameter\PositiveNumberQueryParameter;
use App\Dto\Analytics\ExpensiveTool\Output\ExpensiveToolOutput;
use App\Dto\Analytics\ExpensiveTool\Output\ExpensiveToolsCollectionOutput;
use App\Dto\Analytics\ExpensiveTool\Query\ExpensiveToolsQuery;
use App\State\Provider\Analytics\ExpensiveToolCollectionProvider;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

#[ApiResource(
    class: ExpensiveToolOutput::class,
    shortName: self::SHORT_NAME,
    formats: [JsonEncoder::FORMAT],
    operations: [
        new GetCollection(
            uriTemplate: self::URI,
            parameters: [
                self::PARAM_LIMIT => new PositiveIntegerQueryParameter(maximum: ExpensiveToolsQuery::MAX_LIMIT),
                self::PARAM_MIN_COST => new PositiveNumberQueryParameter(),
            ],
            provider: ExpensiveToolCollectionProvider::class,
            output: ExpensiveToolsCollectionOutput::class,
            paginationEnabled: false,
        ),
    ],
)]
final class ExpensiveToolAnalyticsResource
{
    public const SHORT_NAME = 'ExpensiveTool';
    public const URI = '/analytics/expensive-tools';

    public const PARAM_LIMIT = 'limit';
    public const PARAM_MIN_COST = 'min_cost';
}
