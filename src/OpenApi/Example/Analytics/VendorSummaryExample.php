<?php

namespace App\OpenApi\Example\Analytics;

final class VendorSummaryExample
{
    public const DATA = [
        'data' => [
            [
                'vendor' => 'Google',
                'tools_count' => 4,
                'total_monthly_cost' => 234.50,
                'total_users' => 67,
                'departments' => 'Engineering,Marketing,Sales',
                'average_cost_per_user' => 3.50,
                'vendor_efficiency' => 'excellent',
            ],
            [
                'vendor' => 'BigCorp',
                'tools_count' => 1,
                'total_monthly_cost' => 199.99,
                'total_users' => 2,
                'departments' => 'Sales',
                'average_cost_per_user' => 100.00,
                'vendor_efficiency' => 'poor',
            ],
        ],
        'vendor_insights' => [
            'single_tool_vendors' => 8,
            'most_expensive_vendor' => 'BigCorp',
            'most_efficient_vendor' => 'Google',
        ],
    ];

    public const EMPTY_DB = [
        'data' => [],
        'vendor_insights' => [
            'single_tool_vendors' => 0,
        ],
        'message' => 'No analytics data available - ensure tools data exists',
    ];
}
