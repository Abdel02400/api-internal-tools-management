<?php

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Tool\ToolResource;
use App\Dto\Tool\Output\ToolCollectionOutput;
use App\Dto\Tool\Output\ToolOutput;
use App\Dto\Tool\Query\ListToolsQuery;
use App\Entity\Tool;
use App\Factory\Tool\ListToolsQueryFactory;
use App\Http\ApiMessage;
use App\Mapper\ToolMapper;
use App\Repository\ToolRepository;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @implements ProviderInterface<ToolCollectionOutput>
 */
final readonly class ToolCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ToolRepository $toolRepository,
        private ToolMapper $mapper,
        private ListToolsQueryFactory $queryFactory,
        private ValidatorInterface $validator,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ToolCollectionOutput
    {
        $query = $this->queryFactory->create();

        $violations = $this->validator->validate($query);
        if (count($violations) > 0) {
            throw new ValidationFailedException($query, $violations);
        }

        $total = $this->toolRepository->countAll();
        $filteredCount = $query->hasFilters() ? $this->toolRepository->countMatching($query) : $total;

        $tools = $this->toolRepository->search($query);

        $data = array_map(
            fn (Tool $tool): ToolOutput => $this->mapper->toOutput($tool),
            $tools,
        );

        $paginationApplied = $this->buildPaginationApplied($query, $filteredCount);

        return new ToolCollectionOutput(
            data: $data,
            total: $total,
            filtered: $query->hasFilters() ? $filteredCount : null,
            filtersApplied: $query->hasFilters() ? $query->toFilterArray() : null,
            paginationApplied: $paginationApplied,
            sortApplied: $this->buildSortApplied($query),
            message: $this->resolveMessage(
                total: $total,
                resultCount: count($tools),
                hasFilters: $query->hasFilters(),
                paginationApplied: $paginationApplied,
                page: $query->effectivePage(),
            ),
        );
    }

    /**
     * @return array{page: int, limit: int, total_pages: int}|null
     */
    private function buildPaginationApplied(ListToolsQuery $query, int $filteredCount): ?array
    {
        if (!$query->hasPagination()) {
            return null;
        }

        $limit = $query->effectiveLimit();
        $totalPages = $filteredCount > 0 ? (int) ceil($filteredCount / $limit) : 0;

        return [
            'page' => $query->effectivePage(),
            'limit' => $limit,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * @return array{sort_by: string|null, order: string}|null
     */
    private function buildSortApplied(ListToolsQuery $query): ?array
    {
        if (!$query->hasSort()) {
            return null;
        }

        return [
            ToolResource::PARAM_SORT_BY => $query->sortBy?->value,
            ToolResource::PARAM_ORDER => $query->effectiveOrder()->value,
        ];
    }

    /**
     * @param array{page: int, limit: int, total_pages: int}|null $paginationApplied
     */
    private function resolveMessage(
        int $total,
        int $resultCount,
        bool $hasFilters,
        ?array $paginationApplied,
        int $page,
    ): ?string {
        if ($total === 0) {
            return ApiMessage::noResourceAvailable(Tool::TABLE_NAME);
        }

        if ($paginationApplied !== null && $page > $paginationApplied['total_pages']) {
            return ApiMessage::pageOutOfRange($paginationApplied['total_pages']);
        }

        if ($hasFilters && $resultCount === 0) {
            return ApiMessage::noMatch(Tool::TABLE_NAME);
        }

        return null;
    }
}
