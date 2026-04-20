<?php

namespace App\Repository;

use App\Dto\Tool\Output\UsageMetricsOutput;
use App\Dto\Tool\Output\UsageWindowOutput;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

final readonly class UsageLogRepository
{
    public const LAST_N_DAYS = 30;

    public function __construct(private Connection $connection)
    {
    }

    public function getLast30DaysMetrics(int $toolId): UsageMetricsOutput
    {
        $since = (new DateTimeImmutable())->modify(sprintf('-%d days', self::LAST_N_DAYS));

        $sql = <<<SQL
            SELECT
                COUNT(*) AS total_sessions,
                COALESCE(AVG(usage_minutes), 0) AS avg_minutes
            FROM usage_logs
            WHERE tool_id = :tool_id
              AND session_date >= :since
            SQL;

        $row = $this->connection->fetchAssociative($sql, [
            'tool_id' => $toolId,
            'since' => $since->format('Y-m-d'),
        ]);

        if ($row === false) {
            return new UsageMetricsOutput(new UsageWindowOutput(totalSessions: 0, avgSessionMinutes: 0));
        }

        return new UsageMetricsOutput(
            new UsageWindowOutput(
                totalSessions: $this->toInt($row['total_sessions']),
                avgSessionMinutes: $this->toInt($row['avg_minutes']),
            ),
        );
    }

    private function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
