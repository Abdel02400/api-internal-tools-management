<?php

namespace App\Repository\Analytics;

use App\Dto\Analytics\LowUsageTool\Query\LowUsageToolsQuery;
use App\Enum\ToolStatus;
use Doctrine\DBAL\Connection;

final readonly class LowUsageToolRepository
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * Outils actifs avec `active_users_count <= max_users`. Les 0-users sont TOUJOURS
     * inclus (0 ≤ n'importe quel seuil ≥ 0). Tri : moins utilisé d'abord, puis le
     * plus coûteux en premier à utilisateurs égaux (candidats d'action prioritaires).
     *
     * @return list<array<string, mixed>>
     */
    public function findUnderutilized(LowUsageToolsQuery $query): array
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
              AND t.active_users_count <= :max_users
            ORDER BY t.active_users_count ASC, t.monthly_cost DESC
            SQL;

        /** @var list<array<string, mixed>> */
        return $this->connection->fetchAllAssociative($sql, [
            'status' => ToolStatus::Active->value,
            'max_users' => $query->effectiveMaxUsers(),
        ]);
    }
}
