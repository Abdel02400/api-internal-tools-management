<?php

namespace App\OpenApi\Example\Analytics;

final class ToolsByCategoryExample
{
    public const DATA = [
        'data' => [
            [
                'category_name' => 'Development',
                'tools_count' => 8,
                'total_cost' => 650.00,
                'total_users' => 67,
                'percentage_of_budget' => 26.5,
                'average_cost_per_user' => 9.70,
            ],
            [
                'category_name' => 'Communication',
                'tools_count' => 5,
                'total_cost' => 240.50,
                'total_users' => 89,
                'percentage_of_budget' => 9.8,
                'average_cost_per_user' => 2.70,
            ],
        ],
        'insights' => [
            'most_expensive_category' => 'Development',
            'most_efficient_category' => 'Communication',
        ],
    ];

    public const EMPTY_DB = [
        'data' => [],
        'insights' => [],
        'message' => 'No analytics data available - ensure tools data exists',
    ];
}
