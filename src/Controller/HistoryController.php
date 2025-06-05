<?php

namespace App\Controller;

use App\Service\ActionHistoryService;
use App\Entity\ActionHistory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for action history endpoints
 */
#[Route('/api/history', priority: 10)]
#[IsGranted('ROLE_USER')]
class HistoryController extends AbstractController
{
    public function __construct(
        private readonly ActionHistoryService $historyService
    ) {}

    /**
     * Get general history (all actions)
     */
    #[Route('/general', name: 'api_history_general', methods: ['GET'])]
    public function general(): JsonResponse
    {
        $user = $this->getUser();
        $history = $this->historyService->getHistory($user);

        return $this->json([
            'success' => true,
            'history' => $this->formatHistory($history)
        ]);
    }

    /**
     * Get converter history
     */
    #[Route('/converter', name: 'api_history_converter', methods: ['GET'])]
    public function converter(): JsonResponse
    {
        $user = $this->getUser();
        $history = $this->historyService->getHistory($user, ActionHistory::ACTION_CONVERT);

        return $this->json([
            'success' => true,
            'history' => $this->formatHistory($history)
        ]);
    }

    /**
     * Get parser history
     */
    #[Route('/parser', name: 'api_history_parser', methods: ['GET'])]
    public function parser(): JsonResponse
    {
        $user = $this->getUser();
        $history = $this->historyService->getHistory($user, ActionHistory::ACTION_PARSE);

        return $this->json([
            'success' => true,
            'history' => $this->formatHistory($history)
        ]);
    }

    /**
     * Get generator history
     */
    #[Route('/generator', name: 'api_history_generator', methods: ['GET'])]
    public function generator(): JsonResponse
    {
        $user = $this->getUser();
        $history = $this->historyService->getHistory($user, ActionHistory::ACTION_GENERATE);

        return $this->json([
            'success' => true,
            'history' => $this->formatHistory($history)
        ]);
    }

    /**
     * Get a specific history entry
     */
    #[Route('/{id}', name: 'api_history_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $user = $this->getUser();
        $entry = $this->historyService->getEntry($id, $user);

        if (!$entry) {
            return $this->json([
                'success' => false,
                'error' => 'History entry not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'entry' => [
                'id' => $entry->getId(),
                'actionType' => $entry->getActionType(),
                'diagramType' => $entry->getDiagramType(),
                'createdAt' => $entry->getCreatedAt()->format('c'),
                'fileCount' => $entry->getFileCount(),
                'fileNames' => $entry->getFileNames(),
                'files' => $entry->getFiles()
            ]
        ]);
    }

    /**
     * Get user statistics
     */
    #[Route('/stats', name: 'api_history_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $user = $this->getUser();
        $stats = $this->historyService->getStatistics($user);

        return $this->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Format history entries for JSON response
     *
     * @param ActionHistory[] $history
     * @return array
     */
    private function formatHistory(array $history): array
    {
        return array_map(function (ActionHistory $entry) {
            return [
                'id' => $entry->getId(),
                'actionType' => $entry->getActionType(),
                'diagramType' => $entry->getDiagramType(),
                'createdAt' => $entry->getCreatedAt()->format('c'),
                'fileCount' => $entry->getFileCount(),
                'fileNames' => $entry->getFileNames()
            ];
        }, $history);
    }
}
