<?php

namespace App\OpenApi\Example;

final class ToolDetailExample
{
    public const FOUND = [
        'id' => 5,
        'name' => 'Confluence',
        'description' => 'Team collaboration and documentation',
        'vendor' => 'Atlassian',
        'category' => 'Development',
        'monthly_cost' => 5.50,
        'owner_department' => 'Engineering',
        'status' => 'active',
        'website_url' => 'https://confluence.atlassian.com',
        'active_users_count' => 10,
        'total_monthly_cost' => 55.00,
        'created_at' => '2025-05-01T09:00:00+00:00',
        'updated_at' => '2025-05-01T09:00:00+00:00',
        'usage_metrics' => [
            'last_30_days' => [
                'total_sessions' => 127,
                'avg_session_minutes' => 45,
            ],
        ],
    ];
}
