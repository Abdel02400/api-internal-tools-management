<?php

namespace App\Validator\Tool;

use App\Dto\Tool\Query\ListToolsQuery;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class ListToolsQueryValidator
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    public function validate(ListToolsQuery $query): void
    {
        $violations = $this->validator->validate($query);

        if ($query->minCost !== null && $query->maxCost !== null
            && is_numeric($query->minCost) && is_numeric($query->maxCost)
            && (float) $query->minCost > (float) $query->maxCost
        ) {
            $violations->add(new ConstraintViolation(
                message: 'min_cost must be <= max_cost',
                messageTemplate: null,
                parameters: [],
                root: $query,
                propertyPath: 'min_cost',
                invalidValue: $query->minCost,
            ));
        }

        if (count($violations) > 0) {
            throw new ValidationFailedException($query, $violations);
        }
    }
}
