<?php

namespace App\Mapper\Analytics;

use App\Dto\Analytics\DepartmentCost\Output\DepartmentCostCollectionOutput;
use App\Dto\Analytics\DepartmentCost\Output\DepartmentCostOutput;
use App\Dto\Analytics\DepartmentCost\Output\DepartmentCostSummary;
use App\Helper\NumberFormatter;
use App\Helper\ScalarCast;
use App\Http\ApiMessage;

final class DepartmentCostMapper
{
    /**
     * @param list<array<string, mixed>> $rows
     */
    public function toCollection(array $rows): DepartmentCostCollectionOutput
    {
        if (count($rows) === 0) {
            return new DepartmentCostCollectionOutput(
                data: [],
                summary: new DepartmentCostSummary(totalCompanyCost: 0.0),
                message: ApiMessage::NO_ANALYTICS_DATA,
            );
        }

        $totalCompanyCost = 0.0;
        foreach ($rows as $row) {
            $totalCompanyCost += ScalarCast::toFloat($row['total_cost'] ?? null);
        }

        $data = [];
        foreach ($rows as $row) {
            $data[] = $this->toRow($row, $totalCompanyCost);
        }

        return new DepartmentCostCollectionOutput(
            data: $data,
            summary: new DepartmentCostSummary(
                totalCompanyCost: NumberFormatter::money($totalCompanyCost),
                departmentsCount: count($rows),
                mostExpensiveDepartment: $this->findMostExpensiveDepartment($rows),
            ),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function toRow(array $row, float $totalCompanyCost): DepartmentCostOutput
    {
        $totalCost = ScalarCast::toFloat($row['total_cost'] ?? null);

        return new DepartmentCostOutput(
            department: ScalarCast::toString($row['department'] ?? null),
            totalCost: NumberFormatter::money($totalCost),
            toolsCount: ScalarCast::toInt($row['tools_count'] ?? null),
            totalUsers: ScalarCast::toInt($row['total_users'] ?? null),
            averageCostPerTool: NumberFormatter::money(ScalarCast::toFloat($row['average_cost_per_tool'] ?? null)),
            costPercentage: NumberFormatter::percent(($totalCost / $totalCompanyCost) * 100),
        );
    }

    /**
     * Département avec le plus haut total_cost. En cas d'égalité : ordre alphabétique ASC.
     *
     * @param list<array<string, mixed>> $rows
     */
    private function findMostExpensiveDepartment(array $rows): string
    {
        usort($rows, static function (array $a, array $b): int {
            $costCmp = ScalarCast::toFloat($b['total_cost'] ?? null) <=> ScalarCast::toFloat($a['total_cost'] ?? null);
            if ($costCmp !== 0) {
                return $costCmp;
            }
            return strcmp(ScalarCast::toString($a['department'] ?? null), ScalarCast::toString($b['department'] ?? null));
        });

        return ScalarCast::toString($rows[0]['department'] ?? null);
    }
}
