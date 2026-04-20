<?php

namespace App\State\Provider\Analytics;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Analytics\ExpensiveTool\Output\ExpensiveToolsCollectionOutput;
use App\Factory\Analytics\ExpensiveToolsQueryFactory;
use App\Mapper\Analytics\ExpensiveToolMapper;
use App\Repository\Analytics\ExpensiveToolRepository;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @implements ProviderInterface<ExpensiveToolsCollectionOutput>
 */
final readonly class ExpensiveToolCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ExpensiveToolsQueryFactory $queryFactory,
        private ValidatorInterface $validator,
        private ExpensiveToolRepository $repository,
        private ExpensiveToolMapper $mapper,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExpensiveToolsCollectionOutput
    {
        $query = $this->queryFactory->create();

        $violations = $this->validator->validate($query);
        if (count($violations) > 0) {
            throw new ValidationFailedException($query, $violations);
        }

        $rows = $this->repository->findAllFiltered($query);
        $companyAverage = $this->repository->computeCompanyAverageCostPerUser();

        return $this->mapper->toCollection($rows, $companyAverage, $query);
    }
}
