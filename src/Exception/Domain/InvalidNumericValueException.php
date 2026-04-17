<?php

namespace App\Exception\Domain;

use InvalidArgumentException;

final class InvalidNumericValueException extends InvalidArgumentException
{
    public const MESSAGE = 'Value must be numeric';

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
