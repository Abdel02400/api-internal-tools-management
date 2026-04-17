<?php

namespace App\ValueObject\Number;

use App\Exception\Domain\InvalidNumericValueException;

final class NullableFloat
{
    public static function from(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            throw new InvalidNumericValueException();
        }

        return (float) $value;
    }
}
