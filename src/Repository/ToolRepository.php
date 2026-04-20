<?php

namespace App\Repository;

use App\Dto\Tool\Query\ListToolsQuery;
use App\Entity\Tool;
use App\Enum\SortBy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tool>
 */
final class ToolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tool::class);
    }

    /**
     * @return list<Tool>
     */
    public function search(ListToolsQuery $query): array
    {
        $qb = $this->buildFilteredQuery($query);

        $this->applySort($qb, $query);
        $this->applyPagination($qb, $query);

        /** @var list<Tool> */
        return $qb->getQuery()->getResult();
    }

    public function countMatching(ListToolsQuery $query): int
    {
        $qb = $this->buildFilteredQuery($query);

        return (int) $qb
            ->select('COUNT(DISTINCT t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function buildFilteredQuery(ListToolsQuery $query): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')
            ->addSelect('c');

        if ($query->department !== null) {
            $qb->andWhere('t.ownerDepartment = :department')
                ->setParameter('department', $query->department);
        }

        if ($query->status !== null) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $query->status);
        }

        if ($query->minCost !== null) {
            $qb->andWhere('t.monthlyCost >= :minCost')
                ->setParameter('minCost', $query->minCost);
        }

        if ($query->maxCost !== null) {
            $qb->andWhere('t.monthlyCost <= :maxCost')
                ->setParameter('maxCost', $query->maxCost);
        }

        if ($query->category !== null) {
            $qb->andWhere('c.name = :category')
                ->setParameter('category', $query->category);
        }

        return $qb;
    }

    private function applySort(QueryBuilder $qb, ListToolsQuery $query): void
    {
        $direction = $query->effectiveOrder()->value;

        $column = match ($query->sortBy) {
            SortBy::Cost => 't.monthlyCost',
            SortBy::Name => 't.name',
            SortBy::Date => 't.createdAt',
            null => 't.id',
        };

        $qb->orderBy($column, $direction);
    }

    private function applyPagination(QueryBuilder $qb, ListToolsQuery $query): void
    {
        if (!$query->hasPagination()) {
            return;
        }

        $page = $query->effectivePage();
        $limit = $query->effectiveLimit();

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);
    }
}
