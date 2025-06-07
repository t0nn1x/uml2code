<?php

namespace App\Command;

use App\Repository\SystemLogRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clean-logs',
    description: 'Clean old system logs from the database',
)]
class CleanLogsCommand extends Command
{
    public function __construct(
        private SystemLogRepository $systemLogRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('days', InputArgument::OPTIONAL, 'Number of days to keep logs (default: 30)', 30)
            ->setHelp('This command allows you to clean old system logs from the database...');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getArgument('days');

        if ($days <= 0) {
            $io->error('Days must be a positive number');
            return Command::FAILURE;
        }

        $io->info(sprintf('Cleaning logs older than %d days...', $days));

        try {
            $deletedCount = $this->systemLogRepository->cleanOldLogs($days);
            
            if ($deletedCount > 0) {
                $io->success(sprintf('Successfully deleted %d old log entries.', $deletedCount));
            } else {
                $io->info('No old log entries found to delete.');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Error cleaning logs: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
} 
