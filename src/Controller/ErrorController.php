<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Psr\Log\LoggerInterface;

class ErrorController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function show(FlattenException $exception, DebugLoggerInterface $logger = null): Response
    {
        $statusCode = $exception->getStatusCode();
        $statusText = $exception->getStatusText();

        // Log the error for debugging (except for common 404s to avoid spam)
        if ($statusCode !== 404) {
            $this->logger->error('Error page displayed', [
                'status_code' => $statusCode,
                'status_text' => $statusText,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
        }

        // Choose the appropriate template based on status code
        $template = $this->getTemplateForStatusCode($statusCode);

        // Add additional context for the template
        $context = [
            'status_code' => $statusCode,
            'status_text' => $statusText,
            'exception' => $exception,
            'logger' => $logger,
            'error_id' => $this->generateErrorId(),
            'is_admin_area' => $this->isAdminArea(),
        ];

        return $this->render($template, $context);
    }

    private function getTemplateForStatusCode(int $statusCode): string
    {
        $specificTemplates = [
            401 => 'bundles/TwigBundle/Exception/error401.html.twig',
            403 => 'bundles/TwigBundle/Exception/error403.html.twig',
            404 => 'bundles/TwigBundle/Exception/error404.html.twig',
            500 => 'bundles/TwigBundle/Exception/error500.html.twig',
        ];

        return $specificTemplates[$statusCode] ?? 'bundles/TwigBundle/Exception/error.html.twig';
    }

    private function generateErrorId(): string
    {
        return sprintf('%05d-%s', random_int(10000, 99999), date('ymdHis'));
    }

    private function isAdminArea(): bool
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        return str_starts_with($requestUri, '/admin');
    }

    /**
     * Custom method to handle access denied specifically for admin areas
     */
    public function accessDenied(Request $request): Response
    {
        $this->logger->warning('Access denied to admin area', [
            'url' => $request->getRequestUri(),
            'user' => $this->getUser() ? $this->getUser()->getUserIdentifier() : 'anonymous',
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
        ]);

        return $this->render('bundles/TwigBundle/Exception/error403.html.twig', [
            'status_code' => 403,
            'status_text' => 'Access Denied',
            'is_admin_area' => true,
            'requested_url' => $request->getRequestUri(),
        ], new Response('', 403));
    }

    /**
     * Method to handle maintenance mode
     */
    public function maintenance(): Response
    {
        return $this->render('bundles/TwigBundle/Exception/maintenance.html.twig', [
            'estimated_duration' => '15 minutes', // You can make this configurable
        ], new Response('', 503));
    }
} 
