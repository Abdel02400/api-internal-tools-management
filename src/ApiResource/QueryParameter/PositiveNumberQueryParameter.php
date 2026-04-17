<?php

namespace App\ApiResource\QueryParameter;

use ApiPlatform\Metadata\QueryParameter;

final class PositiveNumberQueryParameter extends QueryParameter
{
    public function __construct()
    {
        parent::__construct(
            schema: ['type' => 'number', 'minimum' => 0],
        );
    }
}
