<?php

namespace App\State\Provider\Analytics;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Analytics\VendorSummary\Output\VendorSummaryCollectionOutput;
use App\Mapper\Analytics\VendorSummaryMapper;
use App\Repository\Analytics\VendorSummaryRepository;

/**
 * @implements ProviderInterface<VendorSummaryCollectionOutput>
 */
final readonly class VendorSummaryCollectionProvider implements ProviderInterface
{
    public function __construct(
        private VendorSummaryRepository $repository,
        private VendorSummaryMapper $mapper,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): VendorSummaryCollectionOutput
    {
        return $this->mapper->toCollection($this->repository->aggregate());
    }
}
