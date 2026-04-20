<?php

namespace App\Mapper\Analytics;

use App\Dto\Analytics\ToolsByCategory\Output\ToolsByCategoryCollectionOutput;
use App\Dto\Analytics\ToolsByCategory\Output\ToolsByCategoryInsights;
use App\Dto\Analytics\ToolsByCategory\Output\ToolsByCategoryOutput;
use App\Helper\NumberFormatter;
use App\Helper\ScalarCast;
use App\Http\ApiMessage;

final class ToolsByCategoryMapper
{
    /**
     * @param list<array<string, mixed>> $rows
     */
    public function toCollection(array $rows): ToolsByCategoryCollectionOutput
    {
        if (count($rows) === 0) {
            return new ToolsByCategoryCollectionOutput(
                data: [],
                insights: new ToolsByCategoryInsights(),
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

        return new ToolsByCategoryCollectionOutput(
            data: $data,
            insights: new ToolsByCategoryInsights(
                mostExpensiveCategory: $this->findMostExpensive($rows),
                mostEfficientCategory: $this->findMostEfficient($rows),
            ),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function toRow(array $row, float $totalCompanyCost): ToolsByCategoryOutput
    {
        $totalCost = ScalarCast::toFloat($row['total_cost'] ?? null);
        $avgCostPerUser = $row['average_cost_per_user'] ?? null;

        return new ToolsByCategoryOutput(
            categoryName: ScalarCast::toString($row['category_name'] ?? null),
            toolsCount: ScalarCast::toInt($row['tools_count'] ?? null),
            totalCost: NumberFormatter::money($totalCost),
            totalUsers: ScalarCast::toInt($row['total_users'] ?? null),
            percentageOfBudget: $totalCompanyCost > 0.0
                ? NumberFormatter::percent(($totalCost / $totalCompanyCost) * 100)
                : 0.0,
            averageCostPerUser: $avgCostPerUser !== null
                ? NumberFormatter::money(ScalarCast::toFloat($avgCostPerUser))
                : null,
        );
    }

    /**
     * Catégorie avec le plus haut total_cost. Tie-break alphabétique ASC.
     *
     * @param list<array<string, mixed>> $rows
     */
    private function findMostExpensive(array $rows): string
    {
        usort($rows, static function (array $a, array $b): int {
            $costCmp = ScalarCast::toFloat($b['total_cost'] ?? null) <=> ScalarCast::toFloat($a['total_cost'] ?? null);
            if ($costCmp !== 0) {
                return $costCmp;
            }
            return strcmp(ScalarCast::toString($a['category_name'] ?? null), ScalarCast::toString($b['category_name'] ?? null));
        });

        return ScalarCast::toString($rows[0]['category_name'] ?? null);
    }

    /**
     * Catégorie avec le plus bas average_cost_per_user. Tie-break alphabétique ASC.
     * Catégories sans utilisateurs (avg null) sont excluses du calcul.
     *
     * @param list<array<string, mixed>> $rows
     */
    private function findMostEfficient(array $rows): ?string
    {
        $eligible = array_values(array_filter(
            $rows,
            static fn (array $r): bool => ($r['average_cost_per_user'] ?? null) !== null,
        ));

        if (count($eligible) === 0) {
            return null;
        }

        usort($eligible, static function (array $a, array $b): int {
            $avgCmp = ScalarCast::toFloat($a['average_cost_per_user']) <=> ScalarCast::toFloat($b['average_cost_per_user']);
            if ($avgCmp !== 0) {
                return $avgCmp;
            }
            return strcmp(ScalarCast::toString($a['category_name'] ?? null), ScalarCast::toString($b['category_name'] ?? null));
        });

        return ScalarCast::toString($eligible[0]['category_name'] ?? null);
    }
}
