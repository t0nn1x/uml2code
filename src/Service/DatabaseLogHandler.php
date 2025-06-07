<?php

namespace App\Service;

use App\Entity\SystemLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;

class DatabaseLogHandler extends AbstractProcessingHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private ?Security $security = null,
        int $level = \Monolog\Logger::DEBUG,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        try {
            $systemLog = new SystemLog();
            $systemLog->setLevel(strtolower($record->level->name));
            $systemLog->setChannel($record->channel);
            $systemLog->setMessage($record->message);
            
            // Set context if available
            if (!empty($record->context)) {
                $systemLog->setContext($record->context);
            }
            
            // Set extra data if available
            if (!empty($record->extra)) {
                $systemLog->setExtra($record->extra);
            }
            
            // Get request information if available
            $request = $this->requestStack->getCurrentRequest();
            if ($request) {
                $systemLog->setIpAddress($request->getClientIp());
                $systemLog->setUserAgent($request->headers->get('User-Agent'));
                $systemLog->setRequestUri($request->getRequestUri());
            }
            
            // Get current user if available
            if ($this->security && $this->security->getUser() instanceof User) {
                $systemLog->setUser($this->security->getUser());
            }
            
            $this->entityManager->persist($systemLog);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            // Prevent infinite loop if logging fails
            // Could optionally log to file or syslog here
        }
    }
} 
