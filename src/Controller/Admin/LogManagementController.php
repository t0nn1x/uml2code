<?php

namespace App\Controller\Admin;

use App\EventListener\AutoLogCleanupListener;
use App\Service\LogCleanupService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/logs', name: 'admin_logs_')]
#[IsGranted('ROLE_ADMIN')]
class LogManagementController extends AbstractController
{
    public function __construct(
        private LogCleanupService $logCleanupService,
        private AutoLogCleanupListener $autoLogCleanupListener
    ) {
    }

    #[Route('/management', name: 'management')]
    public function management(): Response
    {
        $cleanupInfo = $this->autoLogCleanupListener->getLastCleanupInfo();
        $stats = $this->logCleanupService->getLogStatistics();

        return $this->render('admin/log_management.html.twig', [
            'cleanup_info' => $cleanupInfo,
            'statistics' => $stats,
        ]);
    }

    #[Route('/cleanup', name: 'cleanup', methods: ['POST'])]
    public function manualCleanup(Request $request): JsonResponse
    {
        $retentionDays = $request->request->getInt('retention_days');
        
        if ($retentionDays <= 0) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Retention days must be a positive number'
            ], 400);
        }

        try {
            $result = $this->logCleanupService->cleanupOldLogs($retentionDays);
            
            return new JsonResponse([
                'success' => $result['success'],
                'message' => $result['message'],
                'deleted_count' => $result['deleted_count'],
                'retention_days' => $result['retention_days']
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cleanup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/force-cleanup', name: 'force_cleanup', methods: ['POST'])]
    public function forceCleanup(): JsonResponse
    {
        try {
            $result = $this->autoLogCleanupListener->forceCleanupNow();
            
            return new JsonResponse([
                'success' => $result['success'],
                'message' => $result['message'],
                'deleted_count' => $result['deleted_count'],
                'retention_days' => $result['retention_days']
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Force cleanup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/statistics', name: 'statistics')]
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->logCleanupService->getLogStatistics();
            $cleanupInfo = $this->autoLogCleanupListener->getLastCleanupInfo();
            
            return new JsonResponse([
                'success' => true,
                'statistics' => $stats,
                'cleanup_info' => $cleanupInfo
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }
} 
