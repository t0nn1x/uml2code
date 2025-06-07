<?php

namespace App\Repository;

use App\Entity\UserStatistics;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserStatistics>
 *
 * @method UserStatistics|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserStatistics|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserStatistics[]    findAll()
 * @method UserStatistics[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserStatisticsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserStatistics::class);
    }

    /**
     * Save a user statistics entry
     */
    public function save(UserStatistics $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a user statistics entry
     */
    public function remove(UserStatistics $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find or create statistics for a user
     *
     * @param User $user The user
     * @return UserStatistics The user statistics (existing or new)
     */
    public function findOrCreateForUser(User $user): UserStatistics
    {
        $statistics = $this->findOneBy(['user' => $user]);

        if (!$statistics) {
            $statistics = new UserStatistics();
            $statistics->setUser($user);
            $this->save($statistics, true);
        }

        return $statistics;
    }

    /**
     * Get all-time statistics for a user
     *
     * @param User $user The user
     * @return array Comprehensive statistics
     */
    public function getComprehensiveStatistics(User $user): array
    {
        $statistics = $this->findOrCreateForUser($user);

        return [
            'diagrams_processed' => $statistics->getTotalParseActions(),
            'files_generated' => $statistics->getTotalFilesGenerated(),
            'lines_of_code' => $statistics->getTotalLinesOfCode(),
            'total_actions' => $statistics->getTotalActions(),
            'breakdown' => $statistics->getActionBreakdown(),
            'last_updated' => $statistics->getLastUpdated()?->format('c'),
            'member_since' => $statistics->getCreatedAt()?->format('c')
        ];
    }

    /**
     * Get language statistics for a user
     *
     * @param User $user The user
     * @return array Language usage breakdown
     */
    public function getLanguageStatistics(User $user): array
    {
        $statistics = $this->findOrCreateForUser($user);
        return $statistics->getFormattedLanguageStatistics();
    }

    /**
     * Update statistics when a new action is performed
     *
     * @param User $user The user
     * @param string $actionType The action type (parse, convert, generate)
     * @param int $filesCount Number of files generated
     * @param int $linesOfCode Number of lines of code generated
     * @param string|null $programmingLanguage Programming language used
     * @return UserStatistics The updated statistics
     */
    public function updateStatistics(
        User $user,
        string $actionType,
        int $filesCount,
        int $linesOfCode = 0,
        ?string $programmingLanguage = null
    ): UserStatistics {
        $statistics = $this->findOrCreateForUser($user);

        // Increment action counts
        match ($actionType) {
            'parse' => $statistics->incrementParseActions(),
            'convert' => $statistics->incrementConvertActions(),
            'generate' => $statistics->incrementGenerateActions(),
            default => throw new \InvalidArgumentException("Invalid action type: $actionType")
        };

        // Update file and line counts
        $statistics->incrementFilesGenerated($filesCount);
        $statistics->incrementLinesOfCode($linesOfCode);

        // Update language statistics if provided
        if ($programmingLanguage) {
            $statistics->incrementLanguageUsage($programmingLanguage, 1, $linesOfCode);
        }

        $this->save($statistics, true);

        return $statistics;
    }

    /**
     * Reset statistics for a user (mainly for testing)
     *
     * @param User $user The user
     * @return UserStatistics Fresh statistics
     */
    public function resetStatistics(User $user): UserStatistics
    {
        $statistics = $this->findOneBy(['user' => $user]);
        
        if ($statistics) {
            $this->remove($statistics, true);
        }

        $newStatistics = new UserStatistics();
        $newStatistics->setUser($user);
        $this->save($newStatistics, true);

        return $newStatistics;
    }

    /**
     * Get daily activity trends (hybrid approach using both ActionHistory and UserStatistics)
     * This method will delegate to ActionHistoryRepository for recent trends
     * but can be extended to use aggregated data for historical trends
     *
     * @param User $user The user
     * @param int $days Number of days to look back
     * @return array Daily activity breakdown
     */
    public function getActivityTrends(User $user, int $days = 30): array
    {
        // For now, we'll return empty array here as trends will come from ActionHistory
        // This can be extended in the future to store daily/weekly aggregates
        // for long-term trend analysis beyond the 20-record limit
        return [];
    }

    /**
     * Migrate existing ActionHistory data to UserStatistics
     * This method should be called once to initialize statistics from existing history
     *
     * @param User $user The user
     * @return UserStatistics The migrated statistics
     */
    public function migrateFromActionHistory(User $user): UserStatistics
    {
        $em = $this->getEntityManager();
        $actionHistoryRepo = $em->getRepository('App\Entity\ActionHistory');
        
        // Get all existing history for the user
        $allHistory = $actionHistoryRepo->findBy(['user' => $user]);
        
        $statistics = $this->findOrCreateForUser($user);
        
        // Reset to ensure clean migration
        $statistics->setTotalParseActions(0);
        $statistics->setTotalConvertActions(0);
        $statistics->setTotalGenerateActions(0);
        $statistics->setTotalFilesGenerated(0);
        $statistics->setTotalLinesOfCode(0);
        $statistics->setLanguageStatistics([]);
        
        foreach ($allHistory as $entry) {
            $actionType = $entry->getActionType();
            $filesCount = $entry->getFileCount();
            $linesOfCode = $entry->getTotalLinesOfCode() ?? 0;
            $language = $entry->getProgrammingLanguage();
            
            // Increment action counts
            match ($actionType) {
                'parse' => $statistics->incrementParseActions(),
                'convert' => $statistics->incrementConvertActions(),
                'generate' => $statistics->incrementGenerateActions(),
                default => null
            };
            
            // Update totals
            $statistics->incrementFilesGenerated($filesCount);
            $statistics->incrementLinesOfCode($linesOfCode);
            
            // Update language statistics
            if ($language) {
                $statistics->incrementLanguageUsage($language, 1, $linesOfCode);
            }
        }
        
        $this->save($statistics, true);
        
        return $statistics;
    }
} 
