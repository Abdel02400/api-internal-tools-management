<?php

namespace App\ApiResource\QueryParameter;

use ApiPlatform\Metadata\QueryParameter;

class EnumQueryParameter extends QueryParameter
{
    /**
     * @param list<string> $values
     */
    public function __construct(array $values)
    {
        parent::__construct(
            schema: ['type' => 'string', 'enum' => $values],
        );
    }
}
