<?php

namespace App\ValueObject\Number;

use App\Exception\Domain\InvalidIntegerValueException;

final class NullableInt
{
    public static function from(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = filter_var($value, FILTER_VALIDATE_INT);
        if ($int === false) {
            throw new InvalidIntegerValueException();
        }

        return $int;
    }
}
