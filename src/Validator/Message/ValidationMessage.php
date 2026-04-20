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
    public const VALUE_TOO_SHORT = 'Value too short';
    public const MIN_COST_GREATER_THAN_MAX = 'min_cost must be <= max_cost';
    public const FIELD_REQUIRED = 'This field is required';
    public const INVALID_URL = 'Must be a valid URL';
    public const TOO_MANY_DECIMALS = 'Must have at most 2 decimals';
    public const NAME_ALREADY_EXISTS = 'A tool with this name already exists';
    public const CATEGORY_NOT_FOUND = 'Category does not exist';
    public const UNKNOWN_FIELD = 'Unknown field';
    public const MALFORMED_JSON = 'Malformed JSON body';
}
