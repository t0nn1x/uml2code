<?php

namespace App\Repository;

use App\Entity\SystemLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SystemLog>
 */
class SystemLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SystemLog::class);
    }

    /**
     * Find logs by level with optional limit
     */
    public function findByLevel(string $level, int $limit = 100): array
    {
        return $this->createQueryBuilder('sl')
            ->andWhere('sl.level = :level')
            ->setParameter('level', $level)
            ->orderBy('sl.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find error logs (warning and above)
     */
    public function findErrorLogs(int $limit = 100): array
    {
        return $this->createQueryBuilder('sl')
            ->andWhere('sl.level IN (:levels)')
            ->setParameter('levels', [
                SystemLog::LEVEL_WARNING,
                SystemLog::LEVEL_ERROR,
                SystemLog::LEVEL_CRITICAL,
                SystemLog::LEVEL_ALERT,
                SystemLog::LEVEL_EMERGENCY,
            ])
            ->orderBy('sl.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find logs by date range
     */
    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('sl')
            ->andWhere('sl.createdAt >= :from')
            ->andWhere('sl.createdAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('sl.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get log statistics by level
     */
    public function getLogStatistics(): array
    {
        return $this->createQueryBuilder('sl')
            ->select('sl.level, COUNT(sl.id) as count')
            ->groupBy('sl.level')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Clean old logs older than specified days
     */
    public function cleanOldLogs(int $days = 30): int
    {
        $date = new \DateTime();
        $date->modify("-{$days} days");

        return $this->createQueryBuilder('sl')
            ->delete()
            ->andWhere('sl.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
} 
