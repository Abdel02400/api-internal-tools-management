<?php

namespace App\OpenApi\Example;

final class ToolCollectionExample
{
    public const NO_FILTERS = [
        'data' => [
            [
                'id' => 1,
                'name' => 'Slack',
                'description' => 'Team messaging and collaboration platform',
                'vendor' => 'Slack Technologies',
                'category' => 'Communication',
                'monthly_cost' => 8.00,
                'owner_department' => 'Engineering',
                'status' => 'active',
                'website_url' => 'https://slack.com',
                'active_users_count' => 25,
                'created_at' => '2025-05-01T09:00:00+00:00',
            ],
        ],
        'total' => 24,
    ];

    public const WITH_FILTERS = [
        'data' => [
            [
                'id' => 3,
                'name' => 'GitHub',
                'description' => 'Version control and collaboration',
                'vendor' => 'GitHub Inc.',
                'category' => 'Development',
                'monthly_cost' => 21.00,
                'owner_department' => 'Engineering',
                'status' => 'active',
                'website_url' => 'https://github.com',
                'active_users_count' => 10,
                'created_at' => '2025-05-01T09:00:00+00:00',
            ],
        ],
        'total' => 24,
        'filtered' => 7,
        'filters_applied' => [
            'department' => 'Engineering',
            'status' => 'active',
        ],
    ];

    public const WITH_PAGINATION_AND_SORT = [
        'data' => [],
        'total' => 24,
        'filtered' => 7,
        'filters_applied' => [
            'department' => 'Engineering',
        ],
        'pagination_applied' => [
            'page' => 1,
            'limit' => 5,
            'total_pages' => 2,
        ],
        'sort_applied' => [
            'sort_by' => 'cost',
            'order' => 'desc',
        ],
    ];

    public const EMPTY_DB = [
        'data' => [],
        'total' => 0,
        'message' => 'No tools available in the database',
    ];

    public const NO_MATCH = [
        'data' => [],
        'total' => 24,
        'filtered' => 0,
        'filters_applied' => [
            'min_cost' => 99999,
        ],
        'message' => 'No tools match the applied filters',
    ];

    public const PAGE_OUT_OF_RANGE = [
        'data' => [],
        'total' => 24,
        'pagination_applied' => [
            'page' => 100,
            'limit' => 10,
            'total_pages' => 3,
        ],
        'message' => 'Page exceeds available range (max page: 3)',
    ];
}
