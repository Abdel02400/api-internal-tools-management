<?php

namespace App\ApiResource\Analytics;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Dto\Analytics\ToolsByCategory\Output\ToolsByCategoryCollectionOutput;
use App\Dto\Analytics\ToolsByCategory\Output\ToolsByCategoryOutput;
use App\State\Provider\Analytics\ToolsByCategoryCollectionProvider;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

#[ApiResource(
    class: ToolsByCategoryOutput::class,
    shortName: self::SHORT_NAME,
    formats: [JsonEncoder::FORMAT],
    operations: [
        new GetCollection(
            uriTemplate: self::URI,
            provider: ToolsByCategoryCollectionProvider::class,
            output: ToolsByCategoryCollectionOutput::class,
            paginationEnabled: false,
        ),
    ],
)]
final class ToolsByCategoryAnalyticsResource
{
    public const SHORT_NAME = 'ToolsByCategory';
    public const URI = '/analytics/tools-by-category';
}
