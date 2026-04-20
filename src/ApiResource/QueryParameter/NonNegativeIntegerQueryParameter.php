<?php

namespace App\ApiResource\QueryParameter;

use ApiPlatform\Metadata\QueryParameter;

/**
 * Query parameter réutilisable pour les integers >= 0 (ex: `max_users=0` signifie
 * "uniquement les outils à 0 user" — contexte low-usage-tools).
 */
final class NonNegativeIntegerQueryParameter extends QueryParameter
{
    public function __construct(?int $maximum = null)
    {
        $schema = ['type' => 'integer', 'minimum' => 0];
        if ($maximum !== null) {
            $schema['maximum'] = $maximum;
        }
        parent::__construct(schema: $schema);
    }
}
