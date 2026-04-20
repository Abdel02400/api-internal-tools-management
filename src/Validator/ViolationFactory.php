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
        return self::build($field, $value, ValidationMessage::MUST_BE_NUMBER);
    }

    public static function integer(string $field, mixed $value): ConstraintViolation
    {
        return self::build($field, $value, ValidationMessage::MUST_BE_INTEGER);
    }

    public static function nameAlreadyExists(string $field, mixed $value): ConstraintViolation
    {
        return self::build($field, $value, ValidationMessage::NAME_ALREADY_EXISTS);
    }

    private static function build(string $field, mixed $value, string $message): ConstraintViolation
    {
        return new ConstraintViolation(
            message: $message,
            messageTemplate: null,
            parameters: [],
            // root != null pour éviter que `ApiExceptionSubscriber::violationMessage` ne
            // remplace le message par "Invalid value" (ce filtre vise uniquement les
            // violations issues de PartialDenormalizationException d'AP, qui ont root=null).
            root: $value ?? $field,
            propertyPath: $field,
            invalidValue: $value,
        );
    }
}
