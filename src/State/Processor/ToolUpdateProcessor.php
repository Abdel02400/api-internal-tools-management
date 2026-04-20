<?php

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Tool\Input\UpdateToolInput;
use App\Dto\Tool\Output\ToolWriteOutput;
use App\Entity\Tool;
use App\Exception\Domain\InvalidToolStateException;
use App\Exception\Http\ToolNotFoundException;
use App\Factory\Tool\ToolIdFactory;
use App\Mapper\ToolMapper;
use App\Repository\CategoryRepository;
use App\Repository\ToolRepository;
use App\Validator\ViolationFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * @implements ProcessorInterface<UpdateToolInput, ToolWriteOutput>
 */
final readonly class ToolUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        private ToolIdFactory $toolIdFactory,
        private ToolRepository $toolRepository,
        private CategoryRepository $categoryRepository,
        private EntityManagerInterface $entityManager,
        private ToolMapper $mapper,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ToolWriteOutput
    {
        $id = $this->toolIdFactory->create($uriVariables);
        $tool = $this->toolRepository->find($id) ?? throw ToolNotFoundException::withId($id);

        $this->assertNameAvailable($data, $tool);
        $this->applyChanges($data, $tool);

        $this->entityManager->flush();

        return $this->mapper->toWriteOutput($tool);
    }

    private function assertNameAvailable(UpdateToolInput $data, Tool $tool): void
    {
        if ($data->name === null || $data->name === $tool->getName()) {
            return;
        }

        $existing = $this->toolRepository->findOneBy(['name' => $data->name]);
        if ($existing === null || $existing->getId() === $tool->getId()) {
            return;
        }

        $violations = new ConstraintViolationList();
        $violations->add(ViolationFactory::nameAlreadyExists('name', $data->name));
        throw new ValidationFailedException($data, $violations);
    }

    private function applyChanges(UpdateToolInput $data, Tool $tool): void
    {
        if ($data->name !== null) {
            $tool->setName($data->name);
        }

        if ($data->monthlyCost !== null) {
            $tool->setMonthlyCost(number_format($data->monthlyCost, Tool::MONTHLY_COST_SCALE, '.', ''));
        }

        if ($data->ownerDepartment !== null) {
            $tool->setOwnerDepartment($data->ownerDepartment);
        }

        if ($data->status !== null) {
            $tool->setStatus($data->status);
        }

        if ($data->vendor !== null) {
            $tool->setVendor($data->vendor);
        }

        if ($data->description !== null) {
            $tool->setDescription($data->description);
        }

        if ($data->websiteUrl !== null) {
            $tool->setWebsiteUrl($data->websiteUrl);
        }

        if ($data->categoryId !== null) {
            $category = $this->categoryRepository->find($data->categoryId)
                ?? throw InvalidToolStateException::categoryRace($data->categoryId);
            $tool->setCategory($category);
        }
    }
}
