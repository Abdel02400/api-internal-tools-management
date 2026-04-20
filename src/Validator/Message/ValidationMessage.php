<?php

namespace App\Validator\Message;

final class ValidationMessage
{
    public const INVALID_CHOICE = 'Invalid value';
    public const MUST_BE_NUMBER = 'Must be a number';
    public const MUST_BE_INTEGER = 'Must be an integer';
    public const MUST_BE_POSITIVE_OR_ZERO = 'Must be >= 0';
    public const MUST_BE_POSITIVE = 'Must be > 0';
    public const VALUE_TOO_LONG = 'Value too long';
    public const MIN_COST_GREATER_THAN_MAX = 'min_cost must be <= max_cost';
}
