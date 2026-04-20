<?php

namespace App\Mapper\Analytics;

use App\Dto\Analytics\LowUsageTool\Output\LowUsageToolOutput;
use App\Dto\Analytics\LowUsageTool\Output\LowUsageToolsCollectionOutput;
use App\Dto\Analytics\LowUsageTool\Output\LowUsageToolsSavingsAnalysis;
use App\Enum\Department;
use App\Enum\WarningLevel;
use App\Helper\NumberFormatter;
use App\Helper\ScalarCast;
use App\Helper\WarningLevelClassifier;
use App\Http\ApiMessage;

final class LowUsageToolMapper
{
    private const MONTHS_PER_YEAR = 12;

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function toCollection(array $rows): LowUsageToolsCollectionOutput
    {
        if (count($rows) === 0) {
            return new LowUsageToolsCollectionOutput(
                data: [],
                savingsAnalysis: new LowUsageToolsSavingsAnalysis(
                    totalUnderutilizedTools: 0,
                    potentialMonthlySavings: 0.0,
                    potentialAnnualSavings: 0.0,
                ),
                message: ApiMessage::NO_ANALYTICS_DATA,
            );
        }

        $data = [];
        $monthlySavings = 0.0;

        foreach ($rows as $row) {
            $costPerUser = $this->computeCostPerUser($row);
            $warningLevel = WarningLevelClassifier::classify($costPerUser);

            $data[] = new LowUsageToolOutput(
                id: ScalarCast::toInt($row['id'] ?? null),
                name: ScalarCast::toString($row['name'] ?? null),
                monthlyCost: NumberFormatter::money(ScalarCast::toFloat($row['monthly_cost'] ?? null)),
                activeUsersCount: ScalarCast::toInt($row['active_users_count'] ?? null),
                costPerUser: $costPerUser !== null ? NumberFormatter::money($costPerUser) : null,
                department: Department::from(ScalarCast::toString($row['department'] ?? null)),
                vendor: isset($row['vendor']) && is_string($row['vendor']) ? $row['vendor'] : null,
                warningLevel: $warningLevel,
                potentialAction: $warningLevel->recommendedAction(),
            );

            if ($warningLevel !== WarningLevel::Low) {
                $monthlySavings += ScalarCast::toFloat($row['monthly_cost'] ?? null);
            }
        }

        return new LowUsageToolsCollectionOutput(
            data: $data,
            savingsAnalysis: new LowUsageToolsSavingsAnalysis(
                totalUnderutilizedTools: count($rows),
                potentialMonthlySavings: NumberFormatter::money($monthlySavings),
                potentialAnnualSavings: NumberFormatter::money($monthlySavings * self::MONTHS_PER_YEAR),
            ),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function computeCostPerUser(array $row): ?float
    {
        $users = ScalarCast::toInt($row['active_users_count'] ?? null);
        if ($users === 0) {
            return null;
        }
        return ScalarCast::toFloat($row['monthly_cost'] ?? null) / $users;
    }
}
