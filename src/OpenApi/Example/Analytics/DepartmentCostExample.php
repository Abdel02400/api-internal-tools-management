<?php

namespace App\OpenApi\Example\Analytics;

final class DepartmentCostExample
{
    public const DATA = [
        'data' => [
            [
                'department' => 'Engineering',
                'total_cost' => 890.50,
                'tools_count' => 12,
                'total_users' => 45,
                'average_cost_per_tool' => 74.21,
                'cost_percentage' => 36.2,
            ],
            [
                'department' => 'Sales',
                'total_cost' => 456.75,
                'tools_count' => 6,
                'total_users' => 18,
                'average_cost_per_tool' => 76.13,
                'cost_percentage' => 18.6,
            ],
        ],
        'summary' => [
            'total_company_cost' => 2450.80,
            'departments_count' => 6,
            'most_expensive_department' => 'Engineering',
        ],
    ];

    public const EMPTY_DB = [
        'data' => [],
        'summary' => [
            'total_company_cost' => 0,
        ],
        'message' => 'No analytics data available - ensure tools data exists',
    ];

    public const VALIDATION_ERROR = [
        'error' => 'Validation failed',
        'details' => [
            'sort_by' => 'The value you selected is not a valid choice.',
        ],
    ];
}
