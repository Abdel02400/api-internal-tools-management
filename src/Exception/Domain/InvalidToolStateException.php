<?php

namespace App\Exception\Domain;

use LogicException;

final class InvalidToolStateException extends LogicException
{
    private const NOT_PERSISTED_MESSAGE = 'Tool is not persisted';
    private const MISSING_FIELD_TEMPLATE = 'Tool has no %s';
    private const UNEXPECTED_DATA_TEMPLATE = 'Expected %s, got %s';
    private const CATEGORY_RACE_TEMPLATE = 'Category %d not found after validation';

    public static function notPersisted(): self
    {
        return new self(self::NOT_PERSISTED_MESSAGE);
    }

    public static function missingField(string $property): self
    {
        return new self(sprintf(self::MISSING_FIELD_TEMPLATE, $property));
    }

    public static function unexpectedDataType(string $expected, string $actual): self
    {
        return new self(sprintf(self::UNEXPECTED_DATA_TEMPLATE, $expected, $actual));
    }

    public static function categoryRace(int $categoryId): self
    {
        return new self(sprintf(self::CATEGORY_RACE_TEMPLATE, $categoryId));
    }
}
