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
}
