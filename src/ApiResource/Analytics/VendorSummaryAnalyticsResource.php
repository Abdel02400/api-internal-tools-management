<?php

namespace App\ApiResource\Analytics;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Dto\Analytics\VendorSummary\Output\VendorSummaryCollectionOutput;
use App\Dto\Analytics\VendorSummary\Output\VendorSummaryOutput;
use App\State\Provider\Analytics\VendorSummaryCollectionProvider;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

#[ApiResource(
    class: VendorSummaryOutput::class,
    shortName: self::SHORT_NAME,
    formats: [JsonEncoder::FORMAT],
    operations: [
        new GetCollection(
            uriTemplate: self::URI,
            provider: VendorSummaryCollectionProvider::class,
            output: VendorSummaryCollectionOutput::class,
            paginationEnabled: false,
        ),
    ],
)]
final class VendorSummaryAnalyticsResource
{
    public const SHORT_NAME = 'VendorSummary';
    public const URI = '/analytics/vendor-summary';
}
