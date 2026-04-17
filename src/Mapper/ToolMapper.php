<?php

namespace App\Mapper;

use App\Dto\Tool\Output\ToolDetailOutput;
use App\Dto\Tool\Output\ToolOutput;
use App\Dto\Tool\Output\UsageMetricsOutput;
use App\Entity\Tool;
use App\Exception\Domain\InvalidToolStateException;

final class ToolMapper
{
    public function toOutput(Tool $tool): ToolOutput
    {
        return new ToolOutput(
            id: $tool->getId() ?? throw InvalidToolStateException::notPersisted(),
            name: $tool->getName(),
            description: $tool->getDescription(),
            vendor: $tool->getVendor(),
            category: $tool->getCategory()->getName(),
            monthlyCost: (float) $tool->getMonthlyCost(),
            ownerDepartment: $tool->getOwnerDepartment(),
            status: $tool->getStatus(),
            websiteUrl: $tool->getWebsiteUrl(),
            activeUsersCount: $tool->getActiveUsersCount(),
            createdAt: $tool->getCreatedAt() ?? throw InvalidToolStateException::missingField('createdAt'),
        );
    }

    public function toDetailOutput(Tool $tool, UsageMetricsOutput $usageMetrics): ToolDetailOutput
    {
        $monthlyCost = (float) $tool->getMonthlyCost();

        return new ToolDetailOutput(
            id: $tool->getId() ?? throw InvalidToolStateException::notPersisted(),
            name: $tool->getName(),
            description: $tool->getDescription(),
            vendor: $tool->getVendor(),
            category: $tool->getCategory()->getName(),
            monthlyCost: $monthlyCost,
            ownerDepartment: $tool->getOwnerDepartment(),
            status: $tool->getStatus(),
            websiteUrl: $tool->getWebsiteUrl(),
            activeUsersCount: $tool->getActiveUsersCount(),
            totalMonthlyCost: $monthlyCost * $tool->getActiveUsersCount(),
            createdAt: $tool->getCreatedAt() ?? throw InvalidToolStateException::missingField('createdAt'),
            updatedAt: $tool->getUpdatedAt() ?? throw InvalidToolStateException::missingField('updatedAt'),
            usageMetrics: $usageMetrics,
        );
    }
}
