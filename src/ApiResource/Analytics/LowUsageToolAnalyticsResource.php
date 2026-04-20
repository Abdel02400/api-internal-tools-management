<?php

namespace App\ApiResource\Analytics;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\ApiResource\QueryParameter\NonNegativeIntegerQueryParameter;
use App\Dto\Analytics\LowUsageTool\Output\LowUsageToolOutput;
use App\Dto\Analytics\LowUsageTool\Output\LowUsageToolsCollectionOutput;
use App\State\Provider\Analytics\LowUsageToolCollectionProvider;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

#[ApiResource(
    class: LowUsageToolOutput::class,
    shortName: self::SHORT_NAME,
    formats: [JsonEncoder::FORMAT],
    operations: [
        new GetCollection(
            uriTemplate: self::URI,
            parameters: [
                self::PARAM_MAX_USERS => new NonNegativeIntegerQueryParameter(),
            ],
            provider: LowUsageToolCollectionProvider::class,
            output: LowUsageToolsCollectionOutput::class,
            paginationEnabled: false,
        ),
    ],
)]
final class LowUsageToolAnalyticsResource
{
    public const SHORT_NAME = 'LowUsageTool';
    public const URI = '/analytics/low-usage-tools';

    public const PARAM_MAX_USERS = 'max_users';
}
