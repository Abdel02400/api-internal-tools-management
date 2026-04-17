<?php

namespace App\ApiResource\QueryParameter;

use ApiPlatform\Metadata\QueryParameter;

final class StringQueryParameter extends QueryParameter
{
    public function __construct()
    {
        parent::__construct(
            schema: ['type' => 'string'],
        );
    }
}
