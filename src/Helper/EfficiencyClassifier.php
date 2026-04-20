<?php

namespace App\Helper;

use App\Enum\EfficiencyRating;

final class EfficiencyClassifier
{
    private const EXCELLENT_THRESHOLD = 0.5;
    private const GOOD_THRESHOLD = 0.8;
    private const AVERAGE_THRESHOLD = 1.2;

    private function __construct()
    {
    }

    /**
     * Classifie un outil selon le ratio cost_per_user / avg_cost_per_user_company.
     *
     * - excellent : ratio < 0.5  (< 50% de la moyenne)
     * - good      : 0.5 ≤ ratio < 0.8
     * - average   : 0.8 ≤ ratio ≤ 1.2
     * - low       : ratio > 1.2  OU cost_per_user non calculable (0 users)
     */
    public static function classify(?float $costPerUser, float $companyAverage): EfficiencyRating
    {
        if ($costPerUser === null) {
            return EfficiencyRating::Low;
        }

        if ($companyAverage <= 0.0) {
            return EfficiencyRating::Average;
        }

        $ratio = $costPerUser / $companyAverage;

        return match (true) {
            $ratio < self::EXCELLENT_THRESHOLD => EfficiencyRating::Excellent,
            $ratio < self::GOOD_THRESHOLD => EfficiencyRating::Good,
            $ratio <= self::AVERAGE_THRESHOLD => EfficiencyRating::Average,
            default => EfficiencyRating::Low,
        };
    }
}
