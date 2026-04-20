<?php

namespace App\ApiResource\QueryParameter;

use ApiPlatform\Metadata\QueryParameter;

final class PositiveIntegerQueryParameter extends QueryParameter
{
    public function __construct(?int $maximum = null)
    {
        $schema = ['type' => 'integer', 'minimum' => 1];
        if ($maximum !== null) {
            $schema['maximum'] = $maximum;
        }

        parent::__construct(schema: $schema);
    }
}
