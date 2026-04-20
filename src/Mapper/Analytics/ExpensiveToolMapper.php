<?php

namespace App\Mapper\Analytics;

use App\Dto\Analytics\ExpensiveTool\Output\ExpensiveToolOutput;
use App\Dto\Analytics\ExpensiveTool\Output\ExpensiveToolsAnalysis;
use App\Dto\Analytics\ExpensiveTool\Output\ExpensiveToolsCollectionOutput;
use App\Dto\Analytics\ExpensiveTool\Query\ExpensiveToolsQuery;
use App\Enum\Department;
use App\Enum\EfficiencyRating;
use App\Helper\EfficiencyClassifier;
use App\Helper\NumberFormatter;
use App\Helper\ScalarCast;
use App\Http\ApiMessage;

final class ExpensiveToolMapper
{
    /**
     * @param list<array<string, mixed>> $allFilteredRows
     */
    public function toCollection(
        array $allFilteredRows,
        float $companyAverageCostPerUser,
        ExpensiveToolsQuery $query,
    ): ExpensiveToolsCollectionOutput {
        if (count($allFilteredRows) === 0) {
            return new ExpensiveToolsCollectionOutput(
                data: [],
                analysis: new ExpensiveToolsAnalysis(
                    totalToolsAnalyzed: 0,
                    avgCostPerUserCompany: NumberFormatter::money($companyAverageCostPerUser),
                    potentialSavingsIdentified: 0.0,
                ),
                message: $query->hasMinCostFilter()
                    ? ApiMessage::noMatch('tools')
                    : ApiMessage::NO_ANALYTICS_DATA,
            );
        }

        $rowsWithRating = array_map(
            fn (array $row): array => $row + [
                'cost_per_user' => $this->computeCostPerUser($row),
                'efficiency_rating' => EfficiencyClassifier::classify(
                    $this->computeCostPerUser($row),
                    $companyAverageCostPerUser,
                ),
            ],
            $allFilteredRows,
        );

        $potentialSavings = 0.0;
        foreach ($rowsWithRating as $row) {
            if ($row['efficiency_rating'] === EfficiencyRating::Low) {
                $potentialSavings += ScalarCast::toFloat($row['monthly_cost']);
            }
        }

        $topRows = array_slice($rowsWithRating, 0, $query->effectiveLimit());

        $data = [];
        foreach ($topRows as $row) {
            $data[] = $this->toRow($row);
        }

        return new ExpensiveToolsCollectionOutput(
            data: $data,
            analysis: new ExpensiveToolsAnalysis(
                totalToolsAnalyzed: count($allFilteredRows),
                avgCostPerUserCompany: NumberFormatter::money($companyAverageCostPerUser),
                potentialSavingsIdentified: NumberFormatter::money($potentialSavings),
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
        $cost = ScalarCast::toFloat($row['monthly_cost'] ?? null);
        return $cost / $users;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function toRow(array $row): ExpensiveToolOutput
    {
        $costPerUser = $row['cost_per_user'] ?? null;
        $rating = $row['efficiency_rating'] ?? EfficiencyRating::Average;

        return new ExpensiveToolOutput(
            id: ScalarCast::toInt($row['id'] ?? null),
            name: ScalarCast::toString($row['name'] ?? null),
            monthlyCost: NumberFormatter::money(ScalarCast::toFloat($row['monthly_cost'] ?? null)),
            activeUsersCount: ScalarCast::toInt($row['active_users_count'] ?? null),
            costPerUser: is_float($costPerUser) ? NumberFormatter::money($costPerUser) : null,
            department: Department::from(ScalarCast::toString($row['department'] ?? null)),
            vendor: isset($row['vendor']) && is_string($row['vendor']) ? $row['vendor'] : null,
            efficiencyRating: $rating instanceof EfficiencyRating ? $rating : EfficiencyRating::Average,
        );
    }
}
