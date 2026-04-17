<?php

namespace App\Exception\Domain;

use LogicException;

final class InvalidToolStateException extends LogicException
{
    private const NOT_PERSISTED_MESSAGE = 'Tool is not persisted';
    private const MISSING_FIELD_TEMPLATE = 'Tool has no %s';

    public static function notPersisted(): self
    {
        return new self(self::NOT_PERSISTED_MESSAGE);
    }

    public static function missingField(string $property): self
    {
        return new self(sprintf(self::MISSING_FIELD_TEMPLATE, $property));
    }
}
