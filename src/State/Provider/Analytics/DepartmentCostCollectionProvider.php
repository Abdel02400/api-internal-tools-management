<?php

namespace App\State\Provider\Analytics;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Analytics\DepartmentCost\Output\DepartmentCostCollectionOutput;
use App\Factory\Analytics\DepartmentCostsQueryFactory;
use App\Mapper\Analytics\DepartmentCostMapper;
use App\Repository\Analytics\DepartmentCostRepository;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @implements ProviderInterface<DepartmentCostCollectionOutput>
 */
final readonly class DepartmentCostCollectionProvider implements ProviderInterface
{
    public function __construct(
        private DepartmentCostsQueryFactory $queryFactory,
        private ValidatorInterface $validator,
        private DepartmentCostRepository $repository,
        private DepartmentCostMapper $mapper,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): DepartmentCostCollectionOutput
    {
        $query = $this->queryFactory->create();

        $violations = $this->validator->validate($query);
        if (count($violations) > 0) {
            throw new ValidationFailedException($query, $violations);
        }

        $rows = $this->repository->aggregate($query);

        return $this->mapper->toCollection($rows);
    }
}
