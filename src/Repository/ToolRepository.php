<?php

namespace App\Repository;

use App\Dto\Tool\Query\ListToolsQuery;
use App\Entity\Tool;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')
            ->addSelect('c')
            ->orderBy('t.id', 'ASC');

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

        /** @var list<Tool> */
        return $qb->getQuery()->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
