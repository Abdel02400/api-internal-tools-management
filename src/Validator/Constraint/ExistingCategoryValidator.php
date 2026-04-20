<?php

namespace App\Validator\Constraint;

use App\Repository\CategoryRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class ExistingCategoryValidator extends ConstraintValidator
{
    public function __construct(private readonly CategoryRepository $categoryRepository)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ExistingCategory) {
            throw new UnexpectedTypeException($constraint, ExistingCategory::class);
        }

        if ($value === null) {
            return;
        }

        if (!is_int($value)) {
            throw new UnexpectedValueException($value, 'int');
        }

        if ($this->categoryRepository->find($value) === null) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
