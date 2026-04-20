<?php

namespace App\OpenApi\Example\Analytics;

final class ExpensiveToolExample
{
    public const DATA = [
        'data' => [
            [
                'id' => 15,
                'name' => 'Enterprise CRM',
                'monthly_cost' => 199.99,
                'active_users_count' => 12,
                'cost_per_user' => 16.67,
                'department' => 'Sales',
                'vendor' => 'BigCorp',
                'efficiency_rating' => 'low',
            ],
            [
                // Cas orphan : 0 users. `cost_per_user` est omis du JSON
                // (skip_null_values global — pas calculable, div/0). Le client
                // déduit l'état depuis `active_users_count: 0`.
                'id' => 23,
                'name' => 'Orphan License',
                'monthly_cost' => 89.99,
                'active_users_count' => 0,
                'department' => 'Marketing',
                'vendor' => 'SmallVendor',
                'efficiency_rating' => 'low',
            ],
        ],
        'analysis' => [
            'total_tools_analyzed' => 18,
            'avg_cost_per_user_company' => 12.45,
            'potential_savings_identified' => 345.50,
        ],
    ];

    public const EMPTY_DB = [
        'data' => [],
        'analysis' => [
            'total_tools_analyzed' => 0,
            'avg_cost_per_user_company' => 0,
            'potential_savings_identified' => 0,
        ],
        'message' => 'No analytics data available - ensure tools data exists',
    ];

    public const NO_MATCH = [
        'data' => [],
        'analysis' => [
            'total_tools_analyzed' => 0,
            'avg_cost_per_user_company' => 12.45,
            'potential_savings_identified' => 0,
        ],
        'message' => 'No tools match the applied filters',
    ];

    public const VALIDATION_ERROR = [
        'error' => 'Invalid analytics parameter',
        'details' => [
            'limit' => 'Must be positive integer between 1 and 100',
            'min_cost' => 'Must be a positive number',
        ],
    ];
}
