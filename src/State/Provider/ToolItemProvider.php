<?php

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Tool\Output\ToolDetailOutput;
use App\Exception\Http\ToolNotFoundException;
use App\Factory\Tool\ToolIdFactory;
use App\Mapper\ToolMapper;
use App\Repository\ToolRepository;
use App\Repository\UsageLogRepository;

/**
 * @implements ProviderInterface<ToolDetailOutput>
 */
final readonly class ToolItemProvider implements ProviderInterface
{
    public function __construct(
        private ToolIdFactory $toolIdFactory,
        private ToolRepository $toolRepository,
        private UsageLogRepository $usageLogRepository,
        private ToolMapper $mapper,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ToolDetailOutput
    {
        $id = $this->toolIdFactory->create($uriVariables);
        $tool = $this->toolRepository->find($id) ?? throw ToolNotFoundException::withId($id);
        $metrics = $this->usageLogRepository->getLast30DaysMetrics($id);

        return $this->mapper->toDetailOutput($tool, $metrics);
    }
}
