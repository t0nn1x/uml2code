<?php

namespace App\Event;

use App\Entity\ActionHistory;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when an action is recorded in history
 */
class ActionRecordedEvent extends Event
{
    public function __construct(
        private readonly User $user,
        private readonly ActionHistory $actionHistory
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }

    public function getActionHistory(): ActionHistory
    {
        return $this->actionHistory;
    }

    public function getActionType(): string
    {
        return $this->actionHistory->getActionType();
    }

    public function getFileCount(): int
    {
        return $this->actionHistory->getFileCount();
    }

    public function getLinesOfCode(): int
    {
        return $this->actionHistory->getTotalLinesOfCode() ?? 0;
    }

    public function getProgrammingLanguage(): ?string
    {
        return $this->actionHistory->getProgrammingLanguage();
    }
} 
