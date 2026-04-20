<?php

namespace App\State\Provider\Analytics;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Analytics\ToolsByCategory\Output\ToolsByCategoryCollectionOutput;
use App\Mapper\Analytics\ToolsByCategoryMapper;
use App\Repository\Analytics\ToolsByCategoryRepository;

/**
 * @implements ProviderInterface<ToolsByCategoryCollectionOutput>
 */
final readonly class ToolsByCategoryCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ToolsByCategoryRepository $repository,
        private ToolsByCategoryMapper $mapper,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ToolsByCategoryCollectionOutput
    {
        return $this->mapper->toCollection($this->repository->aggregate());
    }
}
