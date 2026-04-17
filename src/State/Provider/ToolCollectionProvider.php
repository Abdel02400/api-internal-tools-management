<?php

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Tool\ToolResource;
use App\Dto\Tool\Output\ToolCollectionOutput;
use App\Dto\Tool\Output\ToolOutput;
use App\Dto\Tool\Query\ListToolsQuery;
use App\Entity\Tool;
use App\Mapper\ToolMapper;
use App\Repository\ToolRepository;
use App\Validator\Tool\ListToolsQueryValidator;
use Symfony\Component\HttpFoundation\Request;

/**
 * @implements ProviderInterface<ToolCollectionOutput>
 */
final readonly class ToolCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ToolRepository $toolRepository,
        private ToolMapper $mapper,
        private ListToolsQueryValidator $queryValidator,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ToolCollectionOutput
    {
        $request = $context['request'] ?? null;
        $query = $this->buildQuery($request instanceof Request ? $request : null);
        $this->queryValidator->validate($query);

        $tools = $this->toolRepository->findBy([], ['id' => 'ASC']);

        $data = array_values(array_map(
            fn (Tool $tool): ToolOutput => $this->mapper->toOutput($tool),
            $tools,
        ));

        return new ToolCollectionOutput(
            data: $data,
            total: count($tools),
            filtered: count($tools),
            filtersApplied: [],
        );
    }

    private function buildQuery(?Request $request): ListToolsQuery
    {
        if ($request === null) {
            return new ListToolsQuery();
        }

        return new ListToolsQuery(
            department: $request->query->get(ToolResource::PARAM_DEPARTMENT),
            status: $request->query->get(ToolResource::PARAM_STATUS),
            minCost: $request->query->get(ToolResource::PARAM_MIN_COST),
            maxCost: $request->query->get(ToolResource::PARAM_MAX_COST),
            category: $request->query->get(ToolResource::PARAM_CATEGORY),
        );
    }
}
