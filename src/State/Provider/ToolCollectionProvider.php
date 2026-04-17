<?php

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Tool\Output\ToolCollectionOutput;
use App\Dto\Tool\Output\ToolOutput;
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

        $tools = $this->toolRepository->search($query);
        $total = $this->toolRepository->countAll();
        $hasFilters = $query->hasFilters();

        $data = array_map(
            fn (Tool $tool): ToolOutput => $this->mapper->toOutput($tool),
            $tools,
        );

        return new ToolCollectionOutput(
            data: $data,
            total: $total,
            filtered: $hasFilters ? count($tools) : null,
            filtersApplied: $hasFilters ? $query->toFilterArray() : null,
            message: $this->resolveMessage(
                total: $total,
                resultCount: count($tools),
                hasFilters: $hasFilters,
            ),
        );
    }

    private function resolveMessage(int $total, int $resultCount, bool $hasFilters): ?string
    {
        if ($total === 0) {
            return ApiMessage::noResourceAvailable(Tool::TABLE_NAME);
        }

        if ($hasFilters && $resultCount === 0) {
            return ApiMessage::noMatch(Tool::TABLE_NAME);
        }

        return null;
    }
}
