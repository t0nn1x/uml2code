<?php

namespace App\Command;

use App\Service\LogCleanupService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:auto-cleanup-logs',
    description: 'Automatically cleanup old logs based on configuration and thresholds',
)]
class AutoLogCleanupCommand extends Command
{
    public function __construct(
        private LogCleanupService $logCleanupService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force cleanup even if thresholds are not met')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be cleaned without actually deleting')
            ->addOption('retention-days', 'r', InputOption::VALUE_OPTIONAL, 'Override retention days')
            ->setHelp('This command automatically cleans up old logs based on configured thresholds...');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');
        $dryRun = $input->getOption('dry-run');
        $retentionDays = $input->getOption('retention-days');

        if ($retentionDays !== null) {
            $retentionDays = (int) $retentionDays;
            if ($retentionDays <= 0) {
                $io->error('Retention days must be a positive number');
                return Command::FAILURE;
            }
        }

        // Get current log statistics
        $stats = $this->logCleanupService->getLogStatistics();
        
        $io->title('Automatic Log Cleanup');
        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Logs', number_format($stats['total_logs'])],
                ['Old Logs (candidates for cleanup)', number_format($stats['old_logs_count'])],
                ['Retention Period', $stats['retention_days'] . ' days'],
                ['Cutoff Date', $stats['cutoff_date']->format('Y-m-d H:i:s')],
                ['Has Recent Errors', $stats['has_errors'] ? 'Yes' : 'No'],
            ]
        );

        if ($dryRun) {
            $io->info('DRY RUN MODE - No logs will actually be deleted');
            if ($stats['old_logs_count'] > 0) {
                $io->success(sprintf('Would delete %d old log entries', $stats['old_logs_count']));
            } else {
                $io->info('No old logs found for cleanup');
            }
            return Command::SUCCESS;
        }

        // Check if cleanup should run
        $shouldCleanup = $force || $this->logCleanupService->shouldRunCleanup();

        if (!$shouldCleanup) {
            $io->info('Cleanup thresholds not met. Use --force to override.');
            $io->note([
                'Cleanup runs automatically when:',
                '- More than 1,000 old logs exist',
                '- Total logs exceed 10,000',
                'Current: ' . number_format($stats['old_logs_count']) . ' old logs, ' . number_format($stats['total_logs']) . ' total logs'
            ]);
            return Command::SUCCESS;
        }

        if ($stats['old_logs_count'] == 0) {
            $io->success('No old logs found for cleanup');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Starting cleanup of logs older than %d days...', $retentionDays ?? $stats['retention_days']));

        // Perform cleanup
        $result = $this->logCleanupService->cleanupOldLogs($retentionDays);

        if ($result['success']) {
            $io->success($result['message']);
            
            if ($result['deleted_count'] > 0) {
                // Get updated statistics
                $newStats = $this->logCleanupService->getLogStatistics();
                $io->table(
                    ['Before/After', 'Total Logs', 'Old Logs'],
                    [
                        ['Before', number_format($stats['total_logs']), number_format($stats['old_logs_count'])],
                        ['After', number_format($newStats['total_logs']), number_format($newStats['old_logs_count'])],
                        ['Difference', '-' . number_format($result['deleted_count']), '-' . number_format($stats['old_logs_count'] - $newStats['old_logs_count'])],
                    ]
                );
            }
        } else {
            $io->error($result['message']);
            if (isset($result['error'])) {
                $io->note('Error details: ' . $result['error']);
            }
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
} 
