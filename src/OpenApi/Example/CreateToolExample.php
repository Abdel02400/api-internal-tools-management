<?php

namespace App\OpenApi\Example;

final class CreateToolExample
{
    public const INPUT = [
        'name' => 'Linear',
        'description' => 'Issue tracking and project management',
        'vendor' => 'Linear',
        'website_url' => 'https://linear.app',
        'category_id' => 2,
        'monthly_cost' => 8.00,
        'owner_department' => 'Engineering',
    ];

    public const CREATED = [
        'id' => 21,
        'name' => 'Linear',
        'description' => 'Issue tracking and project management',
        'vendor' => 'Linear',
        'website_url' => 'https://linear.app',
        'category' => 'Development',
        'monthly_cost' => 8.00,
        'owner_department' => 'Engineering',
        'status' => 'active',
        'active_users_count' => 0,
        'created_at' => '2026-04-20T14:30:00+00:00',
        'updated_at' => '2026-04-20T14:30:00+00:00',
    ];

    public const VALIDATION_ERRORS = [
        'error' => 'Validation failed',
        'details' => [
            'name' => 'A tool with this name already exists',
            'monthly_cost' => 'Must have at most 2 decimals',
            'owner_department' => 'Invalid value',
            'website_url' => 'Must be a valid URL',
            'category_id' => 'Category does not exist',
        ],
    ];

    public const UNKNOWN_FIELDS = [
        'error' => 'Validation failed',
        'details' => [
            'extra_field' => 'Unknown field',
        ],
    ];
}
