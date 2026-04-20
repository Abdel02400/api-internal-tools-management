<?php

namespace App\Repository\Analytics;

use App\Dto\Analytics\ExpensiveTool\Query\ExpensiveToolsQuery;
use App\Enum\ToolStatus;
use Doctrine\DBAL\Connection;

final readonly class ExpensiveToolRepository
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * Charge tous les outils actifs filtrés, triés par monthly_cost desc.
     * Le `limit` est appliqué côté PHP par le mapper — la méthode retourne TOUS les outils
     * filtrés pour permettre les calculs d'analyse (potential_savings_identified doit
     * additionner tous les "low" de la pool analysée, pas juste le top N affiché).
     *
     * @return list<array<string, mixed>>
     */
    public function findAllFiltered(ExpensiveToolsQuery $query): array
    {
        $sql = <<<SQL
            SELECT
                t.id,
                t.name,
                t.monthly_cost,
                t.active_users_count,
                t.vendor,
                t.owner_department AS department
            FROM tools t
            WHERE t.status = :status
              AND (:min_cost IS NULL OR t.monthly_cost >= :min_cost)
            ORDER BY t.monthly_cost DESC
            SQL;

        /** @var list<array<string, mixed>> */
        return $this->connection->fetchAllAssociative($sql, [
            'status' => ToolStatus::Active->value,
            'min_cost' => $query->minCost,
        ]);
    }

    /**
     * Moyenne globale pondérée : SUM(monthly_cost) / SUM(active_users_count),
     * uniquement sur les outils actifs AVEC au moins 1 user (les 0-users sont exclus
     * pour ne pas fausser le dénominateur).
     */
    public function computeCompanyAverageCostPerUser(): float
    {
        $sql = <<<SQL
            SELECT
                SUM(t.monthly_cost) AS total_cost,
                SUM(t.active_users_count) AS total_users
            FROM tools t
            WHERE t.status = :status
              AND t.active_users_count > 0
            SQL;

        $row = $this->connection->fetchAssociative($sql, [
            'status' => ToolStatus::Active->value,
        ]);

        if ($row === false) {
            return 0.0;
        }

        $totalCost = is_numeric($row['total_cost']) ? (float) $row['total_cost'] : 0.0;
        $totalUsers = is_numeric($row['total_users']) ? (int) $row['total_users'] : 0;

        if ($totalUsers === 0) {
            return 0.0;
        }

        return $totalCost / $totalUsers;
    }
}
