<?php

namespace App\Enum;

enum WarningLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public const VALUES = [
        self::Low->value,
        self::Medium->value,
        self::High->value,
    ];

    /**
     * Action métier recommandée pour chaque niveau, utilisée par l'endpoint
     * low-usage-tools dans la sortie `potential_action`.
     */
    public function recommendedAction(): string
    {
        return match ($this) {
            self::High => 'Consider canceling or downgrading',
            self::Medium => 'Review usage and consider optimization',
            self::Low => 'Monitor usage trends',
        };
    }
}
