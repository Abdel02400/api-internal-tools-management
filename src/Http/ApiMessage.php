<?php

namespace App\Http;

final class ApiMessage
{
    public const NO_ANALYTICS_DATA = 'No analytics data available - ensure tools data exists';

    private const NO_RESOURCE_AVAILABLE_TEMPLATE = 'No %s available in the database';
    private const NO_MATCH_TEMPLATE = 'No %s match the applied filters';
    private const PAGE_OUT_OF_RANGE_TEMPLATE = 'Page exceeds available range (max page: %d)';

    public static function noResourceAvailable(string $resource): string
    {
        return sprintf(self::NO_RESOURCE_AVAILABLE_TEMPLATE, $resource);
    }

    public static function noMatch(string $resource): string
    {
        return sprintf(self::NO_MATCH_TEMPLATE, $resource);
    }

    public static function pageOutOfRange(int $lastPage): string
    {
        return sprintf(self::PAGE_OUT_OF_RANGE_TEMPLATE, $lastPage);
    }
}
