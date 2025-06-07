<?php

namespace App\Controller;

use App\Service\ActionHistoryService;
use App\Service\UserStatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly ActionHistoryService $historyService,
        private readonly UserStatisticsService $statisticsService
    ) {}

    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Get dashboard summary statistics
     */
    #[Route('/api/dashboard/summary', name: 'api_dashboard_summary', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function summary(): JsonResponse
    {
        try {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            // Get comprehensive statistics from the new service
            $stats = $this->statisticsService->getSummaryStatistics($user);

            return $this->json([
                'success' => true,
                'stats' => [
                    'diagrams_processed' => $stats['diagrams_processed'],
                    'files_generated' => $stats['files_generated'],
                    'lines_of_code' => $stats['lines_of_code'],
                    'total_actions' => $stats['total_actions'],
                    'last_login' => $user->getLastLoginAt()?->format('c'),
                    'member_since' => $user->getCreatedAt()->format('c'),
                    'breakdown' => $stats['breakdown']
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to load statistics: ' . $e->getMessage(),
                'error' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Get recent activity for dashboard
     */
    #[Route('/api/dashboard/activity', name: 'api_dashboard_activity', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function activity(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $limit = (int) $request->query->get('limit', 10);
        
        $history = $this->historyService->getHistory($user, null, $limit);

        $formattedHistory = array_map(function ($entry) {
            return [
                'id' => $entry->getId(),
                'actionType' => $entry->getActionType(),
                'diagramType' => $entry->getDiagramType(),
                'programmingLanguage' => $entry->getProgrammingLanguage(),
                'createdAt' => $entry->getCreatedAt()->format('c'),
                'fileCount' => $entry->getFileCount(),
                'totalLinesOfCode' => $entry->getTotalLinesOfCode(),
                'diagramName' => $entry->getDiagramName()
            ];
        }, $history);

        return $this->json([
            'success' => true,
            'activity' => $formattedHistory
        ]);
    }

    /**
     * Get activity trends for charts
     */
    #[Route('/api/dashboard/trends', name: 'api_dashboard_trends', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function trends(Request $request): JsonResponse
    {
        try {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $days = (int) $request->query->get('days', 30);
            
            $trends = $this->statisticsService->getActivityTrends($user, $days);

            return $this->json([
                'success' => true,
                'trends' => $trends
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to load trends: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get language usage statistics
     */
    #[Route('/api/dashboard/languages', name: 'api_dashboard_languages', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function languages(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $languages = $this->statisticsService->getLanguageStatistics($user);

        return $this->json([
            'success' => true,
            'languages' => $languages
        ]);
    }
}
