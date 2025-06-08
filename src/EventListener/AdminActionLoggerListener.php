<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::CONTROLLER, method: 'onKernelController')]
class AdminActionLoggerListener
{
    public function __construct(
        private LoggerInterface $logger,
        private Security $security
    ) {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        
        // Only log admin panel actions
        if (!$this->isAdminRequest($request)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user) {
            return;
        }

        $controller = $event->getController();
        $controllerName = $this->getControllerName($controller);
        $action = $request->attributes->get('_route');
        $method = $request->getMethod();

        $this->logger->info('Admin panel action', [
            'user' => $user->getUserIdentifier(),
            'controller' => $controllerName,
            'action' => $action,
            'method' => $method,
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'request_uri' => $request->getRequestUri(),
            'parameters' => $this->sanitizeParameters($request->request->all()),
            'query_parameters' => $request->query->all(),
        ]);
    }

    private function isAdminRequest(Request $request): bool
    {
        $path = $request->getPathInfo();
        return str_starts_with($path, '/en/admin') || str_starts_with($path, '/uk/admin');
    }

    private function getControllerName($controller): string
    {
        if (is_array($controller)) {
            return get_class($controller[0]) . '::' . $controller[1];
        }

        if (is_object($controller)) {
            return get_class($controller);
        }

        return (string) $controller;
    }

    private function sanitizeParameters(array $parameters): array
    {
        // Remove sensitive data from logging
        $sensitiveKeys = ['password', 'token', '_token', 'plainPassword'];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($parameters[$key])) {
                $parameters[$key] = '[REDACTED]';
            }
        }

        // Recursively sanitize nested arrays
        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                $parameters[$key] = $this->sanitizeParameters($value);
            }
        }

        return $parameters;
    }
} 
