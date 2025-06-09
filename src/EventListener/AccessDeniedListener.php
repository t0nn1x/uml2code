<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;
use Symfony\Bundle\SecurityBundle\Security;
use Psr\Log\LoggerInterface;
use Twig\Environment;

#[AsEventListener(event: 'kernel.exception', priority: 2)]
class AccessDeniedListener
{
    public function __construct(
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
        private Security $security
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Only handle access denied exceptions for authenticated users
        // Let Symfony handle authentication redirects for unauthenticated users
        if (($exception instanceof AccessDeniedException || 
             $exception instanceof AccessDeniedHttpException) && 
            $this->security->getUser()) {
            
            $requestUri = $request->getRequestUri();
            $isAdminArea = str_starts_with($requestUri, '/admin');
            $isApiRequest = str_starts_with($requestUri, '/api') || 
                           $request->headers->get('Content-Type') === 'application/json' ||
                           $request->isXmlHttpRequest();

            // Log the access denied attempt
            $this->logger->warning('Access denied', [
                'url' => $requestUri,
                'user' => $this->security->getUser()?->getUserIdentifier() ?? 'anonymous',
                'ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                'is_admin_area' => $isAdminArea,
                'is_api_request' => $isApiRequest,
                'exception_class' => get_class($exception),
            ]);

            // Handle API requests differently
            if ($isApiRequest) {
                $response = new Response(
                    json_encode([
                        'error' => 'Access Denied',
                        'message' => 'You do not have permission to access this resource.',
                        'code' => 403
                    ]),
                    403,
                    ['Content-Type' => 'application/json']
                );
                $event->setResponse($response);
                return;
            }

            // For admin area access denied, show special error page
            if ($isAdminArea) {
                try {
                    $html = $this->twig->render('bundles/TwigBundle/Exception/error403.html.twig', [
                        'status_code' => 403,
                        'status_text' => 'Access Denied',
                        'is_admin_area' => true,
                        'requested_url' => $requestUri,
                        'exception' => $exception,
                    ]);
                    
                    $response = new Response($html, 403);
                    $event->setResponse($response);
                } catch (\Exception $e) {
                    // Fallback if template rendering fails
                    $this->logger->error('Error rendering 403 template', [
                        'error' => $e->getMessage(),
                        'original_exception' => get_class($exception)
                    ]);
                }
                return;
            }

            // This block is no longer needed since we only handle authenticated users above

            // For other access denied cases, show 403 page
            try {
                $html = $this->twig->render('bundles/TwigBundle/Exception/error403.html.twig', [
                    'status_code' => 403,
                    'status_text' => 'Access Denied',
                    'is_admin_area' => false,
                    'requested_url' => $requestUri,
                    'exception' => $exception,
                ]);
                
                $response = new Response($html, 403);
                $event->setResponse($response);
            } catch (\Exception $e) {
                // Fallback if template rendering fails
                $this->logger->error('Error rendering 403 template', [
                    'error' => $e->getMessage(),
                    'original_exception' => get_class($exception)
                ]);
            }
        }
    }
} 
