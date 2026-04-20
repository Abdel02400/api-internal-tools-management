<?php

namespace App\Mapper\Analytics;

use App\Dto\Analytics\VendorSummary\Output\VendorSummaryCollectionOutput;
use App\Dto\Analytics\VendorSummary\Output\VendorSummaryInsights;
use App\Dto\Analytics\VendorSummary\Output\VendorSummaryOutput;
use App\Helper\NumberFormatter;
use App\Helper\ScalarCast;
use App\Helper\VendorEfficiencyClassifier;
use App\Http\ApiMessage;

final class VendorSummaryMapper
{
    /**
     * @param list<array<string, mixed>> $rows
     */
    public function toCollection(array $rows): VendorSummaryCollectionOutput
    {
        if (count($rows) === 0) {
            return new VendorSummaryCollectionOutput(
                data: [],
                vendorInsights: new VendorSummaryInsights(),
                message: ApiMessage::NO_ANALYTICS_DATA,
            );
        }

        $data = [];
        $singleToolVendors = 0;

        foreach ($rows as $row) {
            $toolsCount = ScalarCast::toInt($row['tools_count'] ?? null);
            $avgRaw = $row['average_cost_per_user'] ?? null;
            $avg = $avgRaw !== null ? ScalarCast::toFloat($avgRaw) : null;

            $data[] = new VendorSummaryOutput(
                vendor: ScalarCast::toString($row['vendor'] ?? null),
                toolsCount: $toolsCount,
                totalMonthlyCost: NumberFormatter::money(ScalarCast::toFloat($row['total_monthly_cost'] ?? null)),
                totalUsers: ScalarCast::toInt($row['total_users'] ?? null),
                departments: ScalarCast::toString($row['departments'] ?? null),
                averageCostPerUser: $avg !== null ? NumberFormatter::money($avg) : null,
                vendorEfficiency: VendorEfficiencyClassifier::classify($avg),
            );

            if ($toolsCount === 1) {
                $singleToolVendors++;
            }
        }

        return new VendorSummaryCollectionOutput(
            data: $data,
            vendorInsights: new VendorSummaryInsights(
                singleToolVendors: $singleToolVendors,
                mostExpensiveVendor: $this->findMostExpensive($rows),
                mostEfficientVendor: $this->findMostEfficient($rows),
            ),
        );
    }

    /**
     * Vendor avec le plus haut total_monthly_cost. Tie-break alphabétique ASC.
     *
     * @param list<array<string, mixed>> $rows
     */
    private function findMostExpensive(array $rows): string
    {
        usort($rows, static function (array $a, array $b): int {
            $costCmp = ScalarCast::toFloat($b['total_monthly_cost'] ?? null) <=> ScalarCast::toFloat($a['total_monthly_cost'] ?? null);
            if ($costCmp !== 0) {
                return $costCmp;
            }
            return strcmp(ScalarCast::toString($a['vendor'] ?? null), ScalarCast::toString($b['vendor'] ?? null));
        });

        return ScalarCast::toString($rows[0]['vendor'] ?? null);
    }

    /**
     * Vendor avec le plus bas average_cost_per_user non-null. Tie-break alphabétique ASC.
     * Vendors sans utilisateurs actifs (avg null) sont excluss du calcul.
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
            return strcmp(ScalarCast::toString($a['vendor'] ?? null), ScalarCast::toString($b['vendor'] ?? null));
        });

        return ScalarCast::toString($eligible[0]['vendor'] ?? null);
    }
}
