<?php

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Tool\Output\ToolDetailOutput;
use App\Dto\Tool\Output\UsageMetricsOutput;
use App\Exception\Http\ToolNotFoundException;
use App\Mapper\ToolMapper;
use App\Repository\ToolRepository;

/**
 * @implements ProviderInterface<ToolDetailOutput>
 */
final readonly class ToolItemProvider implements ProviderInterface
{
    public function __construct(
        private ToolRepository $toolRepository,
        private ToolMapper $mapper,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ToolDetailOutput
    {
        $id = $uriVariables['id'] ?? null;

        if (!is_numeric($id)) {
            throw ToolNotFoundException::withId(0);
        }

        $id = (int) $id;
        $tool = $this->toolRepository->find($id);

        if ($tool === null) {
            throw ToolNotFoundException::withId($id);
        }

        $metrics = new UsageMetricsOutput(
            totalSessions: 0,
            avgSessionMinutes: 0,
        );

        return $this->mapper->toDetailOutput($tool, $metrics);
    }
}
