<?php

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Tool\Input\CreateToolInput;
use App\Dto\Tool\Output\ToolWriteOutput;
use App\Entity\Tool;
use App\Exception\Domain\InvalidToolStateException;
use App\Mapper\ToolMapper;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @implements ProcessorInterface<CreateToolInput, ToolWriteOutput>
 */
final readonly class ToolPersistProcessor implements ProcessorInterface
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private EntityManagerInterface $entityManager,
        private ToolMapper $mapper,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ToolWriteOutput
    {
        $category = $this->categoryRepository->find($data->categoryId)
            ?? throw InvalidToolStateException::categoryRace($data->categoryId);

        $tool = new Tool(
            name: $data->name,
            category: $category,
            monthlyCost: number_format($data->monthlyCost, Tool::MONTHLY_COST_SCALE, '.', ''),
            ownerDepartment: $data->ownerDepartment,
        );
        $tool->setDescription($data->description);
        $tool->setVendor($data->vendor);
        $tool->setWebsiteUrl($data->websiteUrl);

        $this->entityManager->persist($tool);
        $this->entityManager->flush();

        return $this->mapper->toWriteOutput($tool);
    }
}
