<?php

namespace App\OpenApi\Example;

final class ErrorResponseExample
{
    public const VALIDATION_FAILED = [
        'error' => 'Validation failed',
        'details' => [
            'department' => 'The value you selected is not a valid choice.',
            'min_cost' => 'Must be a number',
        ],
    ];

    public const TOOL_NOT_FOUND = [
        'error' => 'Tool not found',
        'message' => 'Tool with ID 999 does not exist',
    ];

    public const RESOURCE_NOT_FOUND = [
        'error' => 'Resource not found',
        'message' => 'The requested resource could not be found',
    ];

    public const DATABASE_UNAVAILABLE = [
        'error' => 'Internal server error',
        'message' => 'Database connection failed',
    ];
}
