<?php

namespace App\Exception\Domain;

use InvalidArgumentException;

final class InvalidIntegerValueException extends InvalidArgumentException
{
    public const MESSAGE = 'Value must be an integer';

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
