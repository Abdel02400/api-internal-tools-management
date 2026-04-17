<?php

namespace App\Validator;

use App\Validator\Message\ValidationMessage;
use Symfony\Component\Validator\ConstraintViolation;

final class ViolationFactory
{
    private function __construct()
    {
    }

    public static function numeric(string $field, mixed $value): ConstraintViolation
    {
        return new ConstraintViolation(
            message: ValidationMessage::MUST_BE_NUMBER,
            messageTemplate: null,
            parameters: [],
            root: null,
            propertyPath: $field,
            invalidValue: $value,
        );
    }
}
