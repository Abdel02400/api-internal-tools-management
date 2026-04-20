<?php

namespace App\Validator\Constraint;

use App\Validator\Message\ValidationMessage;
use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class ExistingCategory extends Constraint
{
    public string $message = ValidationMessage::CATEGORY_NOT_FOUND;
}
