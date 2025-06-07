<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserStatistics;
use App\Repository\UserStatisticsRepository;
use App\Repository\ActionHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing comprehensive user statistics
 */
class UserStatisticsService
{
    public function __construct(
        private readonly UserStatisticsRepository $statisticsRepository,
        private readonly ActionHistoryRepository $historyRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Update user statistics when an action is performed
     *
     * @param User $user The user
     * @param string $actionType The action type (parse, convert, generate)
     * @param int $filesCount Number of files generated
     * @param int $linesOfCode Number of lines of code generated
     * @param string|null $programmingLanguage Programming language used
     */
    public function updateStatistics(
        User $user,
        string $actionType,
        int $filesCount,
        int $linesOfCode = 0,
        ?string $programmingLanguage = null
    ): void {
        try {
            $this->statisticsRepository->updateStatistics(
                $user,
                $actionType,
                $filesCount,
                $linesOfCode,
                $programmingLanguage
            );

            $this->logger->info('Updated user statistics', [
                'user_id' => $user->getId(),
                'action_type' => $actionType,
                'files_count' => $filesCount,
                'lines_of_code' => $linesOfCode,
                'programming_language' => $programmingLanguage
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update user statistics', [
                'user_id' => $user->getId(),
                'action_type' => $actionType,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get comprehensive dashboard statistics for a user
     *
     * @param User $user The user
     * @return array Comprehensive statistics
     */
    public function getDashboardStatistics(User $user): array
    {
        $basicStats = $this->statisticsRepository->getComprehensiveStatistics($user);
        $languageStats = $this->statisticsRepository->getLanguageStatistics($user);
        
        // Get recent activity trends from history (last 30 days)
        $dailyStats = $this->historyRepository->getDailyActivityByUser($user, 30);

        return [
            'basic' => $basicStats['breakdown'],
            'languages' => $languageStats,
            'daily_activity' => $dailyStats,
            'total_lines_of_code' => $basicStats['lines_of_code'],
            'total_files' => $basicStats['files_generated'],
            'last_activity' => $this->historyRepository->getLastActivityByUser($user)
        ];
    }

    /**
     * Get activity trends for charts (hybrid approach)
     *
     * @param User $user The user
     * @param int $days Number of days to look back
     * @return array Time-series data for charts
     */
    public function getActivityTrends(User $user, int $days = 30): array
    {
        // Use ActionHistory for recent trends (within the record limit)
        return $this->historyRepository->getDailyActivityByUser($user, $days);
    }

    /**
     * Get language usage statistics
     *
     * @param User $user The user
     * @return array Language usage breakdown
     */
    public function getLanguageStatistics(User $user): array
    {
        return $this->statisticsRepository->getLanguageStatistics($user);
    }

    /**
     * Get overall statistics summary
     *
     * @param User $user The user
     * @return array Summary statistics
     */
    public function getSummaryStatistics(User $user): array
    {
        return $this->statisticsRepository->getComprehensiveStatistics($user);
    }

    /**
     * Migrate existing action history to user statistics
     * This should be called once for existing users to populate their statistics
     *
     * @param User $user The user
     * @return UserStatistics The migrated statistics
     */
    public function migrateFromActionHistory(User $user): UserStatistics
    {
        $this->logger->info('Starting migration of action history to user statistics', [
            'user_id' => $user->getId()
        ]);

        try {
            $statistics = $this->statisticsRepository->migrateFromActionHistory($user);

            $this->logger->info('Successfully migrated action history to user statistics', [
                'user_id' => $user->getId(),
                'total_actions' => $statistics->getTotalActions(),
                'total_files' => $statistics->getTotalFilesGenerated(),
                'total_lines' => $statistics->getTotalLinesOfCode()
            ]);

            return $statistics;
        } catch (\Exception $e) {
            $this->logger->error('Failed to migrate action history to user statistics', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Migrate all users' action history to user statistics
     * This is a batch operation for initial setup
     *
     * @return array Migration results
     */
    public function migrateAllUsers(): array
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $users = $userRepository->findAll();
        
        $results = [
            'total_users' => count($users),
            'migrated' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($users as $user) {
            try {
                $this->migrateFromActionHistory($user);
                $results['migrated']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'user_id' => $user->getId(),
                    'error' => $e->getMessage()
                ];
                
                // Continue with other users even if one fails
                $this->logger->warning('Failed to migrate user statistics', [
                    'user_id' => $user->getId(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logger->info('Completed batch migration of user statistics', $results);

        return $results;
    }

    /**
     * Reset statistics for a user (mainly for testing)
     *
     * @param User $user The user
     * @return UserStatistics Fresh statistics
     */
    public function resetStatistics(User $user): UserStatistics
    {
        $this->logger->info('Resetting user statistics', ['user_id' => $user->getId()]);
        
        return $this->statisticsRepository->resetStatistics($user);
    }

    /**
     * Ensure statistics exist for a user (create if not exists)
     *
     * @param User $user The user
     * @return UserStatistics The user statistics
     */
    public function ensureStatisticsExist(User $user): UserStatistics
    {
        return $this->statisticsRepository->findOrCreateForUser($user);
    }
} 
