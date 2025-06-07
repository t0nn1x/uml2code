<?php

namespace App\EventListener;

use App\Service\LogCleanupService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsEventListener(event: KernelEvents::TERMINATE, method: 'onKernelTerminate', priority: -100)]
class AutoLogCleanupListener
{
    private const CLEANUP_CACHE_KEY = 'log_cleanup_last_run';
    private const CLEANUP_INTERVAL_HOURS = 24; // Run cleanup max once per day

    public function __construct(
        private LogCleanupService $logCleanupService,
        private LoggerInterface $logger,
        private KernelInterface $kernel,
        #[Autowire('%env(default:default_auto_cleanup:bool:AUTO_LOG_CLEANUP_ENABLED)%')] private bool $autoCleanupEnabled = true
    ) {
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        // Only run on main request and if auto cleanup is enabled
        if (!$event->isMainRequest() || !$this->autoCleanupEnabled) {
            return;
        }

        // Only run for admin routes or specific high-usage routes
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        
        if (!$this->shouldCheckForCleanup($route)) {
            return;
        }

        // Check if we've run cleanup recently
        if (!$this->shouldRunCleanupNow()) {
            return;
        }

        // Run cleanup asynchronously to avoid blocking the response
        $this->runCleanupAsync();
    }

    private function shouldCheckForCleanup(?string $route): bool
    {
        if (!$route) {
            return false;
        }

        // Run cleanup check on admin routes
        if (str_starts_with($route, 'admin')) {
            return true;
        }

        // Run cleanup check on high-usage application routes
        $highUsageRoutes = [
            'dashboard',
            'converter_convert',
            'parser_parse', 
            'generator_generate'
        ];

        return in_array($route, $highUsageRoutes);
    }

    private function shouldRunCleanupNow(): bool
    {
        try {
            $cache = new FilesystemAdapter();
            $cachedItem = $cache->getItem(self::CLEANUP_CACHE_KEY);
            
            if (!$cachedItem->isHit()) {
                return true;
            }
            
            $lastRun = $cachedItem->get();
            $now = new \DateTime();
            $interval = $now->diff($lastRun);
            
            // Run if it's been more than CLEANUP_INTERVAL_HOURS hours
            return $interval->h >= self::CLEANUP_INTERVAL_HOURS || $interval->days > 0;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to check cleanup cache', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function runCleanupAsync(): void
    {
        try {
            // Check if cleanup should run based on thresholds
            if (!$this->logCleanupService->shouldRunCleanup()) {
                $this->updateLastRunCache();
                return;
            }

            $this->logger->info('Starting automatic log cleanup');

            // Run cleanup
            $result = $this->logCleanupService->cleanupOldLogs();
            
            if ($result['success']) {
                $this->logger->info('Automatic log cleanup completed successfully', [
                    'deleted_count' => $result['deleted_count'],
                    'retention_days' => $result['retention_days']
                ]);
            } else {
                $this->logger->error('Automatic log cleanup failed', [
                    'error' => $result['message']
                ]);
            }

            // Update cache regardless of result to prevent too frequent attempts
            $this->updateLastRunCache();
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to run automatic log cleanup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function updateLastRunCache(): void
    {
        try {
            $cache = new FilesystemAdapter();
            $cachedItem = $cache->getItem(self::CLEANUP_CACHE_KEY);
            $cachedItem->set(new \DateTime());
            $cachedItem->expiresAfter(self::CLEANUP_INTERVAL_HOURS * 3600); // Expire after interval
            $cache->save($cachedItem);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update cleanup cache', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function forceCleanupNow(): array
    {
        // Method to manually trigger cleanup (can be called from admin panel)
        $result = $this->logCleanupService->cleanupOldLogs();
        $this->updateLastRunCache();
        return $result;
    }

    public function getLastCleanupInfo(): array
    {
        try {
            $cache = new FilesystemAdapter();
            $cachedItem = $cache->getItem(self::CLEANUP_CACHE_KEY);
            
            $stats = $this->logCleanupService->getLogStatistics();
            
            return [
                'last_run' => $cachedItem->isHit() ? $cachedItem->get() : null,
                'next_scheduled' => $cachedItem->isHit() 
                    ? $cachedItem->get()->add(new \DateInterval('PT' . self::CLEANUP_INTERVAL_HOURS . 'H'))
                    : new \DateTime(),
                'auto_cleanup_enabled' => $this->autoCleanupEnabled,
                'cleanup_interval_hours' => self::CLEANUP_INTERVAL_HOURS,
                'should_run_now' => $this->logCleanupService->shouldRunCleanup(),
                'statistics' => $stats
            ];
            
        } catch (\Exception $e) {
            return [
                'last_run' => null,
                'next_scheduled' => null,
                'auto_cleanup_enabled' => $this->autoCleanupEnabled,
                'cleanup_interval_hours' => self::CLEANUP_INTERVAL_HOURS,
                'should_run_now' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 
