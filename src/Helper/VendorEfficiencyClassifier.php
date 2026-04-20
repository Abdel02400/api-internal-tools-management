<?php

namespace App\Helper;

use App\Enum\VendorEfficiency;

final class VendorEfficiencyClassifier
{
    private const EXCELLENT_UPPER = 5.0;
    private const GOOD_UPPER = 15.0;
    private const AVERAGE_UPPER = 25.0;

    private function __construct()
    {
    }

    /**
     * Classifie un vendor selon son average_cost_per_user (seuils absolus en €,
     * distincts de `EfficiencyClassifier` qui travaille en ratio vs moyenne compagnie).
     *
     * - excellent : < 5€/user
     * - good      : 5-15€/user
     * - average   : 15-25€/user
     * - poor      : > 25€/user  OU  average non calculable (0 user total)
     */
    public static function classify(?float $avgCostPerUser): VendorEfficiency
    {
        if ($avgCostPerUser === null) {
            return VendorEfficiency::Poor;
        }

        return match (true) {
            $avgCostPerUser < self::EXCELLENT_UPPER => VendorEfficiency::Excellent,
            $avgCostPerUser <= self::GOOD_UPPER => VendorEfficiency::Good,
            $avgCostPerUser <= self::AVERAGE_UPPER => VendorEfficiency::Average,
            default => VendorEfficiency::Poor,
        };
    }
}
