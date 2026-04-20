<?php

namespace App\State\Provider\Analytics;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Analytics\LowUsageTool\Output\LowUsageToolsCollectionOutput;
use App\Factory\Analytics\LowUsageToolsQueryFactory;
use App\Mapper\Analytics\LowUsageToolMapper;
use App\Repository\Analytics\LowUsageToolRepository;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @implements ProviderInterface<LowUsageToolsCollectionOutput>
 */
final readonly class LowUsageToolCollectionProvider implements ProviderInterface
{
    public function __construct(
        private LowUsageToolsQueryFactory $queryFactory,
        private ValidatorInterface $validator,
        private LowUsageToolRepository $repository,
        private LowUsageToolMapper $mapper,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): LowUsageToolsCollectionOutput
    {
        $query = $this->queryFactory->create();

        $violations = $this->validator->validate($query);
        if (count($violations) > 0) {
            throw new ValidationFailedException($query, $violations);
        }

        return $this->mapper->toCollection($this->repository->findUnderutilized($query));
    }
}
