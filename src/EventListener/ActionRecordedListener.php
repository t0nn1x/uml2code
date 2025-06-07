<?php

namespace App\EventListener;

use App\Event\ActionRecordedEvent;
use App\Service\UserStatisticsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Listener for ActionRecordedEvent to update user statistics
 */
#[AsEventListener(event: ActionRecordedEvent::class)]
class ActionRecordedListener
{
    public function __construct(
        private readonly UserStatisticsService $statisticsService,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(ActionRecordedEvent $event): void
    {
        try {
            $this->statisticsService->updateStatistics(
                $event->getUser(),
                $event->getActionType(),
                $event->getFileCount(),
                $event->getLinesOfCode(),
                $event->getProgrammingLanguage()
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to update user statistics via event listener', [
                'user_id' => $event->getUser()->getId(),
                'action_type' => $event->getActionType(),
                'error' => $e->getMessage()
            ]);
            
            // Don't rethrow to avoid breaking the main action recording flow
        }
    }
} 
