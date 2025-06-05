<?php

namespace App\Service;

use App\Entity\ActionHistory;
use App\Entity\User;
use App\Repository\ActionHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing action history
 */
class ActionHistoryService
{
    private const MAX_ENTRIES_PER_ACTION = 30;

    public function __construct(
        private readonly ActionHistoryRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Record a new action in history
     *
     * @param User $user The user performing the action
     * @param string $actionType The type of action (convert, parse, generate)
     * @param array $files Array of files with 'filename' and 'content' keys
     * @param string $diagramType The type of diagram (default: ClassDiagram)
     * @return ActionHistory The created history entry
     */
    public function record(User $user, string $actionType, array $files, string $diagramType = 'ClassDiagram'): ActionHistory
    {
        // Create new history entry
        $history = new ActionHistory();
        $history->setUser($user);
        $history->setActionType($actionType);
        $history->setDiagramType($diagramType);
        $history->setFiles($files);

        // Save the entry
        $this->repository->save($history, true);

        // Clean up old entries (keep only the latest 30)
        $deletedCount = $this->repository->deleteOldEntries($user, $actionType, self::MAX_ENTRIES_PER_ACTION);

        if ($deletedCount > 0) {
            $this->logger->info('Deleted old action history entries', [
                'user_id' => $user->getId(),
                'action_type' => $actionType,
                'deleted_count' => $deletedCount
            ]);
        }

        return $history;
    }

    /**
     * Get action history for a user
     *
     * @param User $user The user
     * @param string|null $actionType Filter by action type (optional)
     * @param int $limit Maximum number of entries to return
     * @return ActionHistory[]
     */
    public function getHistory(User $user, ?string $actionType = null, int $limit = 30): array
    {
        return $this->repository->findLatestByUser($user, $actionType, $limit);
    }

    /**
     * Get statistics for a user
     *
     * @param User $user The user
     * @return array Statistics by action type
     */
    public function getStatistics(User $user): array
    {
        return $this->repository->getStatsByUser($user);
    }

    /**
     * Delete all history for a user
     *
     * @param User $user The user
     * @return int Number of deleted entries
     */
    public function deleteAllForUser(User $user): int
    {
        $entries = $this->repository->findBy(['user' => $user]);
        $count = count($entries);

        foreach ($entries as $entry) {
            $this->repository->remove($entry);
        }

        $this->entityManager->flush();

        return $count;
    }

    /**
     * Get a single history entry by ID
     *
     * @param int $id The history entry ID
     * @param User $user The user (for security check)
     * @return ActionHistory|null
     */
    public function getEntry(int $id, User $user): ?ActionHistory
    {
        $entry = $this->repository->find($id);

        // Security check: ensure the entry belongs to the user
        if ($entry && $entry->getUser() === $user) {
            return $entry;
        }

        return null;
    }
}
