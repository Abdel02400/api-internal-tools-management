<?php

namespace App\Validator\Constraint;

use App\Repository\ToolRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class UniqueToolNameValidator extends ConstraintValidator
{
    public function __construct(private readonly ToolRepository $toolRepository)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueToolName) {
            throw new UnexpectedTypeException($constraint, UniqueToolName::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if ($this->toolRepository->findOneBy(['name' => $value]) !== null) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
