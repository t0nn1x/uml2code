<?php

namespace App\Repository;

use App\Entity\ActionHistory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActionHistory>
 *
 * @method ActionHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActionHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActionHistory[]    findAll()
 * @method ActionHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActionHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActionHistory::class);
    }

    /**
     * Save an action history entry
     */
    public function save(ActionHistory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove an action history entry
     */
    public function remove(ActionHistory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find the latest action history entries for a user
     *
     * @param User $user The user
     * @param string|null $actionType Filter by action type (optional)
     * @param int $limit Maximum number of entries to return
     * @return ActionHistory[]
     */
    public function findLatestByUser(User $user, ?string $actionType = null, int $limit = 30): array
    {
        $qb = $this->createQueryBuilder('ah')
            ->where('ah.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ah.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($actionType !== null) {
            $qb->andWhere('ah.actionType = :actionType')
                ->setParameter('actionType', $actionType);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Delete old entries keeping only the latest $keepCount entries per user and action type
     *
     * @param User $user The user
     * @param string $actionType The action type
     * @param int $keepCount Number of entries to keep (default 30)
     * @return int Number of deleted entries
     */
    public function deleteOldEntries(User $user, string $actionType, int $keepCount = 30): int
    {
        // First, get the IDs of entries to keep
        $keepIds = $this->createQueryBuilder('ah')
            ->select('ah.id')
            ->where('ah.user = :user')
            ->andWhere('ah.actionType = :actionType')
            ->setParameter('user', $user)
            ->setParameter('actionType', $actionType)
            ->orderBy('ah.createdAt', 'DESC')
            ->setMaxResults($keepCount)
            ->getQuery()
            ->getResult();

        if (empty($keepIds)) {
            return 0;
        }

        // Extract just the IDs
        $keepIds = array_map(fn($row) => $row['id'], $keepIds);

        // Delete entries not in the keep list
        $qb = $this->createQueryBuilder('ah')
            ->delete()
            ->where('ah.user = :user')
            ->andWhere('ah.actionType = :actionType')
            ->andWhere('ah.id NOT IN (:keepIds)')
            ->setParameter('user', $user)
            ->setParameter('actionType', $actionType)
            ->setParameter('keepIds', $keepIds);

        return $qb->getQuery()->execute();
    }

    /**
     * Get action statistics for a user
     *
     * @param User $user The user
     * @return array Statistics by action type
     */
    public function getStatsByUser(User $user): array
    {
        $results = $this->createQueryBuilder('ah')
            ->select('ah.actionType, COUNT(ah.id) as count')
            ->where('ah.user = :user')
            ->setParameter('user', $user)
            ->groupBy('ah.actionType')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($results as $row) {
            $stats[$row['actionType']] = (int) $row['count'];
        }

        // Ensure all action types are present
        foreach (ActionHistory::VALID_ACTIONS as $action) {
            if (!isset($stats[$action])) {
                $stats[$action] = 0;
            }
        }

        return $stats;
    }

    /**
     * Get language usage statistics for a user
     *
     * @param User $user The user
     * @return array Language usage breakdown
     */
    public function getLanguageStatsByUser(User $user): array
    {
        $results = $this->createQueryBuilder('ah')
            ->select('ah.programmingLanguage, COUNT(ah.id) as count, SUM(ah.totalLinesOfCode) as totalLines')
            ->where('ah.user = :user')
            ->andWhere('ah.programmingLanguage IS NOT NULL')
            ->setParameter('user', $user)
            ->groupBy('ah.programmingLanguage')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($results as $row) {
            $stats[] = [
                'language' => $row['programmingLanguage'],
                'count' => (int) $row['count'],
                'totalLines' => (int) ($row['totalLines'] ?? 0)
            ];
        }

        return $stats;
    }

    /**
     * Get daily activity statistics for a user
     *
     * @param User $user The user
     * @param int $days Number of days to look back
     * @return array Daily activity breakdown
     */
    public function getDailyActivityByUser(User $user, int $days = 30): array
    {
        $startDate = new \DateTime();
        $startDate->modify("-{$days} days");

        // Get results using raw SQL for PostgreSQL compatibility
        $sql = "
            SELECT DATE(ah.created_at) as date, ah.action_type, COUNT(ah.id) as count
            FROM action_history ah 
            WHERE ah.user_id = :user_id 
            AND ah.created_at >= :start_date
            GROUP BY DATE(ah.created_at), ah.action_type
            ORDER BY DATE(ah.created_at) ASC
        ";
        
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('user_id', $user->getId());
        $stmt->bindValue('start_date', $startDate->format('Y-m-d H:i:s'));
        $result = $stmt->executeQuery();
        $results = $result->fetchAllAssociative();

        // Transform to time-series format
        $stats = [];
        foreach ($results as $row) {
            $date = $row['date'];
            if (!isset($stats[$date])) {
                $stats[$date] = [
                    'date' => $date,
                    'convert' => 0,
                    'parse' => 0,
                    'generate' => 0,
                    'total' => 0
                ];
            }
            $stats[$date][$row['action_type']] = (int) $row['count'];
            $stats[$date]['total'] += (int) $row['count'];
        }

        return array_values($stats);
    }

    /**
     * Get total lines of code generated by a user
     *
     * @param User $user The user
     * @return int Total lines of code
     */
    public function getTotalLinesOfCodeByUser(User $user): int
    {
        $result = $this->createQueryBuilder('ah')
            ->select('SUM(ah.totalLinesOfCode) as totalLines')
            ->where('ah.user = :user')
            ->andWhere('ah.totalLinesOfCode IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    /**
     * Get total files generated by a user
     *
     * @param User $user The user
     * @return int Total files
     */
    public function getTotalFilesByUser(User $user): int
    {
        $results = $this->createQueryBuilder('ah')
            ->select('ah.files')
            ->where('ah.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $totalFiles = 0;
        foreach ($results as $row) {
            $files = $row['files'];
            if (is_array($files)) {
                $totalFiles += count($files);
            }
        }

        return $totalFiles;
    }

    /**
     * Get last activity timestamp for a user
     *
     * @param User $user The user
     * @return ?\DateTimeImmutable Last activity date
     */
    public function getLastActivityByUser(User $user): ?\DateTimeImmutable
    {
        $result = $this->createQueryBuilder('ah')
            ->select('ah.createdAt')
            ->where('ah.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ah.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result ? $result['createdAt'] : null;
    }

    /**
     * Get weekly usage statistics for a user
     *
     * @param User $user The user
     * @param int $weeks Number of weeks to look back
     * @return array Weekly statistics
     */
    public function getWeeklyStatsByUser(User $user, int $weeks = 12): array
    {
        $startDate = new \DateTime();
        $startDate->modify("-{$weeks} weeks");

        // Get results using raw SQL for PostgreSQL compatibility
        $sql = "
            SELECT EXTRACT(WEEK FROM ah.created_at) as week, ah.action_type, COUNT(ah.id) as count
            FROM action_history ah 
            WHERE ah.user_id = :user_id 
            AND ah.created_at >= :start_date
            GROUP BY EXTRACT(WEEK FROM ah.created_at), ah.action_type
            ORDER BY EXTRACT(WEEK FROM ah.created_at) ASC
        ";
        
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('user_id', $user->getId());
        $stmt->bindValue('start_date', $startDate->format('Y-m-d H:i:s'));
        $result = $stmt->executeQuery();
        $results = $result->fetchAllAssociative();

        $stats = [];
        foreach ($results as $row) {
            $week = $row['week'];
            if (!isset($stats[$week])) {
                $stats[$week] = [
                    'week' => $week,
                    'convert' => 0,
                    'parse' => 0,
                    'generate' => 0,
                    'total' => 0
                ];
            }
            $stats[$week][$row['action_type']] = (int) $row['count'];
            $stats[$week]['total'] += (int) $row['count'];
        }

        return array_values($stats);
    }
}
