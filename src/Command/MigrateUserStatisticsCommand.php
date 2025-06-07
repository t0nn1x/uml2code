<?php

namespace App\Command;

use App\Service\UserStatisticsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-user-statistics',
    description: 'Migrate existing action history data to comprehensive user statistics',
)]
class MigrateUserStatisticsCommand extends Command
{
    public function __construct(
        private readonly UserStatisticsService $statisticsService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Migrate existing action history data to comprehensive user statistics')
            ->setHelp('This command migrates existing ActionHistory records to the new UserStatistics system for comprehensive dashboard statistics.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force migration even if user statistics already exist')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');

        $io->title('Migrating User Statistics');
        
        if ($force) {
            $io->note('Force mode enabled - existing statistics will be overwritten');
        }

        $io->text('Starting migration of all users\' action history to comprehensive statistics...');

        try {
            $results = $this->statisticsService->migrateAllUsers();
            
            $io->success('Migration completed successfully!');
            
            $io->table(
                ['Metric', 'Count'],
                [
                    ['Total Users', $results['total_users']],
                    ['Successfully Migrated', $results['migrated']],
                    ['Failed', $results['failed']]
                ]
            );

            if ($results['failed'] > 0) {
                $io->warning(sprintf('%d users failed to migrate:', $results['failed']));
                
                foreach ($results['errors'] as $error) {
                    $io->text(sprintf('User ID %d: %s', $error['user_id'], $error['error']));
                }
            }

            if ($results['migrated'] > 0) {
                $io->note([
                    'Your dashboard will now show comprehensive, accurate statistics!',
                    'Statistics include all-time totals that won\'t be affected by the 20-record history limit.',
                    'Language usage and other metrics are now persistent and cumulative.'
                ]);
            } else {
                $io->info('No users needed migration (statistics may already exist).');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error([
                'Migration failed with error:',
                $e->getMessage()
            ]);

            if ($output->isVerbose()) {
                $io->text($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
} 
