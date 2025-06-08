<?php

namespace App\Service;

use App\Repository\SystemLogRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class LogCleanupService
{
    public function __construct(
        private SystemLogRepository $systemLogRepository,
        private LoggerInterface $logger,
        #[Autowire('%env(default:default_log_retention:int:LOG_RETENTION_DAYS)%')] private int $defaultRetentionDays = 30
    ) {
    }

    public function cleanupOldLogs(?int $retentionDays = null): array
    {
        $retentionDays = $retentionDays ?? $this->defaultRetentionDays;
        
        try {
            $deletedCount = $this->systemLogRepository->cleanOldLogs($retentionDays);
            
            $this->logger->info('Automatic log cleanup completed', [
                'deleted_logs' => $deletedCount,
                'retention_days' => $retentionDays,
                'cleanup_date' => new \DateTime()
            ]);

            return [
                'success' => true,
                'deleted_count' => $deletedCount,
                'retention_days' => $retentionDays,
                'message' => sprintf('Successfully cleaned %d old log entries (older than %d days)', $deletedCount, $retentionDays)
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Log cleanup failed', [
                'error' => $e->getMessage(),
                'retention_days' => $retentionDays,
                'cleanup_date' => new \DateTime()
            ]);

            return [
                'success' => false,
                'deleted_count' => 0,
                'retention_days' => $retentionDays,
                'message' => sprintf('Log cleanup failed: %s', $e->getMessage()),
                'error' => $e->getMessage()
            ];
        }
    }

    public function getLogStatistics(): array
    {
        try {
            $totalLogs = $this->systemLogRepository->count([]);
            $statistics = $this->systemLogRepository->getLogStatistics();
            $errorLogs = $this->systemLogRepository->findErrorLogs(1);
            
            // Calculate logs older than retention period
            $cutoffDate = new \DateTime();
            $cutoffDate->modify("-{$this->defaultRetentionDays} days");
            
            $oldLogs = $this->systemLogRepository->createQueryBuilder('sl')
                ->select('COUNT(sl.id)')
                ->where('sl.createdAt < :cutoff')
                ->setParameter('cutoff', $cutoffDate)
                ->getQuery()
                ->getSingleScalarResult();

            return [
                'total_logs' => $totalLogs,
                'old_logs_count' => $oldLogs,
                'retention_days' => $this->defaultRetentionDays,
                'statistics_by_level' => $statistics,
                'has_errors' => count($errorLogs) > 0,
                'cutoff_date' => $cutoffDate
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get log statistics', [
                'error' => $e->getMessage()
            ]);

            return [
                'total_logs' => 0,
                'old_logs_count' => 0,
                'retention_days' => $this->defaultRetentionDays,
                'statistics_by_level' => [],
                'has_errors' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function shouldRunCleanup(): bool
    {
        // Check if cleanup should run based on log count or age
        $stats = $this->getLogStatistics();
        
        // Run cleanup if we have more than 1000 old logs
        if ($stats['old_logs_count'] > 1000) {
            return true;
        }
        
        // Run cleanup if total logs exceed 10000
        if ($stats['total_logs'] > 10000) {
            return true;
        }
        
        return false;
    }
} 
