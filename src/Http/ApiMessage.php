<?php

namespace App\Http;

final class ApiMessage
{
    private const NO_RESOURCE_AVAILABLE_TEMPLATE = 'No %s available in the database';
    private const NO_MATCH_TEMPLATE = 'No %s match the applied filters';

    public static function noResourceAvailable(string $resource): string
    {
        return sprintf(self::NO_RESOURCE_AVAILABLE_TEMPLATE, $resource);
    }

    public static function noMatch(string $resource): string
    {
        return sprintf(self::NO_MATCH_TEMPLATE, $resource);
    }
}
