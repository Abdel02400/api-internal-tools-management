<?php

namespace App\Repository\Analytics;

use App\Entity\Category;
use App\Entity\Tool;
use App\Enum\ToolStatus;
use Doctrine\DBAL\Connection;

final readonly class ToolsByCategoryRepository
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
                    c.name AS category_name,
                    COUNT(t.id) AS tools_count,
                    SUM(t.monthly_cost) AS total_cost,
                    SUM(t.active_users_count) AS total_users,
                    CASE
                        WHEN SUM(t.active_users_count) > 0
                        THEN SUM(t.monthly_cost) / SUM(t.active_users_count)
                        ELSE NULL
                    END AS average_cost_per_user
                FROM %s c
                INNER JOIN %s t ON t.category_id = c.id
                WHERE t.status = :status
                GROUP BY c.id, c.name
                ORDER BY total_cost DESC
                SQL,
            Category::TABLE_NAME,
            Tool::TABLE_NAME,
        );

        /** @var list<array<string, mixed>> */
        return $this->connection->fetchAllAssociative($sql, [
            'status' => ToolStatus::Active->value,
        ]);
    }
}
