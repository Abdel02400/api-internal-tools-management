<?php

namespace App\OpenApi\Example;

final class UpdateToolExample
{
    public const INPUT = [
        'monthly_cost' => 7.00,
        'status' => 'deprecated',
        'description' => 'Updated description after renewal',
    ];

    public const UPDATED = [
        'id' => 5,
        'name' => 'Confluence',
        'description' => 'Updated description after renewal',
        'vendor' => 'Atlassian',
        'website_url' => 'https://confluence.atlassian.com',
        'category' => 'Development',
        'monthly_cost' => 7.00,
        'owner_department' => 'Engineering',
        'status' => 'deprecated',
        'active_users_count' => 10,
        'created_at' => '2025-05-01T09:00:00+00:00',
        'updated_at' => '2026-04-20T15:45:00+00:00',
    ];

    public const VALIDATION_ERRORS = [
        'error' => 'Validation failed',
        'details' => [
            'name' => 'A tool with this name already exists',
            'monthly_cost' => 'Must have at most 2 decimals',
            'status' => 'Invalid value',
            'category_id' => 'Category does not exist',
        ],
    ];
}
