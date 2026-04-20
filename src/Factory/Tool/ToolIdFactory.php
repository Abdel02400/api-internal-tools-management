<?php

namespace App\Factory\Tool;

use App\ApiResource\Tool\ToolResource;
use App\Validator\ViolationFactory;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class ToolIdFactory
{
    /**
     * @param array<string, mixed> $uriVariables
     */
    public function create(array $uriVariables): int
    {
        $rawId = $uriVariables[ToolResource::ID_PARAM] ?? null;
        $id = filter_var($rawId, FILTER_VALIDATE_INT);

        if ($id === false) {
            $violations = new ConstraintViolationList();
            $violations->add(ViolationFactory::integer(ToolResource::ID_PARAM, $rawId));
            throw new ValidationFailedException($rawId, $violations);
        }

        return $id;
    }
}
