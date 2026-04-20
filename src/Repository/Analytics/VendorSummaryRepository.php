<?php

namespace App\Repository\Analytics;

use App\Entity\Tool;
use App\Enum\ToolStatus;
use Doctrine\DBAL\Connection;

final readonly class VendorSummaryRepository
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function aggregate(): array
    {
        $sql = sprintf(
            <<<SQL
                SELECT
                    t.vendor AS vendor,
                    COUNT(t.id) AS tools_count,
                    SUM(t.monthly_cost) AS total_monthly_cost,
                    SUM(t.active_users_count) AS total_users,
                    GROUP_CONCAT(DISTINCT t.owner_department ORDER BY t.owner_department ASC SEPARATOR ',') AS departments,
                    CASE
                        WHEN SUM(t.active_users_count) > 0
                        THEN SUM(t.monthly_cost) / SUM(t.active_users_count)
                        ELSE NULL
                    END AS average_cost_per_user
                FROM %s t
                WHERE t.status = :status
                  AND t.vendor IS NOT NULL
                GROUP BY t.vendor
                ORDER BY total_monthly_cost DESC
                SQL,
            Tool::TABLE_NAME,
        );

        /** @var list<array<string, mixed>> */
        return $this->connection->fetchAllAssociative($sql, [
            'status' => ToolStatus::Active->value,
        ]);
    }
}
