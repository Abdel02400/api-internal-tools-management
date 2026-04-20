<?php

namespace App\OpenApi\Example\Analytics;

final class LowUsageToolExample
{
    public const DATA = [
        'data' => [
            [
                'id' => 23,
                'name' => 'Specialized Analytics',
                'monthly_cost' => 89.99,
                'active_users_count' => 2,
                'cost_per_user' => 45.00,
                'department' => 'Marketing',
                'vendor' => 'SmallVendor',
                'warning_level' => 'medium',
                'potential_action' => 'Review usage and consider optimization',
            ],
            [
                'id' => 40,
                'name' => 'Expensive Niche Tool',
                'monthly_cost' => 199.99,
                'active_users_count' => 1,
                'cost_per_user' => 199.99,
                'department' => 'Operations',
                'vendor' => 'NichePro',
                'warning_level' => 'high',
                'potential_action' => 'Consider canceling or downgrading',
            ],
        ],
        'savings_analysis' => [
            'total_underutilized_tools' => 5,
            'potential_monthly_savings' => 287.50,
            'potential_annual_savings' => 3450.00,
        ],
    ];

    public const EMPTY_DB = [
        'data' => [],
        'savings_analysis' => [
            'total_underutilized_tools' => 0,
            'potential_monthly_savings' => 0,
            'potential_annual_savings' => 0,
        ],
        'message' => 'No analytics data available - ensure tools data exists',
    ];

    public const VALIDATION_ERROR = [
        'error' => 'Invalid analytics parameter',
        'details' => [
            'max_users' => 'Must be a non-negative integer',
        ],
    ];
}
