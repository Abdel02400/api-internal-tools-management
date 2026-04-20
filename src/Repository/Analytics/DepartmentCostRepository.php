<?php

namespace App\Repository\Analytics;

use App\Dto\Analytics\DepartmentCost\Query\DepartmentCostsQuery;
use App\Enum\DepartmentCostSortBy;
use App\Enum\SortOrder;
use App\Enum\ToolStatus;
use Doctrine\DBAL\Connection;

final readonly class DepartmentCostRepository
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function aggregate(DepartmentCostsQuery $query): array
    {
        $column = $this->sortColumn($query->effectiveSortBy());
        $direction = $query->effectiveOrder() === SortOrder::Asc ? 'ASC' : 'DESC';

        $sql = <<<SQL
            SELECT
                t.owner_department AS department,
                COUNT(t.id) AS tools_count,
                SUM(t.active_users_count) AS total_users,
                SUM(t.monthly_cost) AS total_cost,
                SUM(t.monthly_cost) / COUNT(t.id) AS average_cost_per_tool
            FROM tools t
            WHERE t.status = :status
            GROUP BY t.owner_department
            ORDER BY {$column} {$direction}
            SQL;

        /** @var list<array<string, mixed>> */
        return $this->connection->fetchAllAssociative($sql, [
            'status' => ToolStatus::Active->value,
        ]);
    }

    private function sortColumn(DepartmentCostSortBy $sortBy): string
    {
        return match ($sortBy) {
            DepartmentCostSortBy::TotalCost => 'total_cost',
            DepartmentCostSortBy::ToolsCount => 'tools_count',
            DepartmentCostSortBy::TotalUsers => 'total_users',
            DepartmentCostSortBy::AverageCostPerTool => 'average_cost_per_tool',
            // `owner_department` est une colonne ENUM MySQL — un ORDER BY natif utilise
            // l'ordre de déclaration des cases, pas alphabétique. On cast en CHAR pour
            // obtenir un tri alphabétique stable côté client.
            DepartmentCostSortBy::Department => 'CAST(department AS CHAR)',
        };
    }
}
