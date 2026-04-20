<?php

namespace App\Helper;

use App\Enum\WarningLevel;

final class WarningLevelClassifier
{
    private const LOW_UPPER = 20.0;
    private const MEDIUM_UPPER = 50.0;

    private function __construct()
    {
    }

    /**
     * Classifie un outil par niveau d'alerte selon son cost_per_user.
     *
     * - low    : cost_per_user < 20€
     * - medium : 20€ ≤ cost_per_user ≤ 50€
     * - high   : cost_per_user > 50€  OU  cost_per_user non calculable (0 users)
     */
    public static function classify(?float $costPerUser): WarningLevel
    {
        if ($costPerUser === null) {
            return WarningLevel::High;
        }

        return match (true) {
            $costPerUser < self::LOW_UPPER => WarningLevel::Low,
            $costPerUser <= self::MEDIUM_UPPER => WarningLevel::Medium,
            default => WarningLevel::High,
        };
    }
}
