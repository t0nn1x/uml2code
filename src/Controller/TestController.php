<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class TestController extends AbstractController
{
    #[Route('/test', name: 'app_test')]
    public function index(): Response
    {
        return $this->render('test/index.html.twig', [
            'controller_name' => 'TestController',
        ]);
    }

    /**
     * Test routes for error pages - remove in production
     */
    #[Route('/test/error/404', name: 'test_error_404')]
    public function test404(): Response
    {
        throw new NotFoundHttpException('This is a test 404 error');
    }

    #[Route('/test/error/403', name: 'test_error_403')]
    public function test403(): Response
    {
        throw new AccessDeniedHttpException('This is a test 403 error');
    }

    #[Route('/test/error/500', name: 'test_error_500')]
    public function test500(): Response
    {
        throw new \Exception('This is a test 500 error');
    }

    #[Route('/test/error/maintenance', name: 'test_maintenance')]
    public function testMaintenance(): Response
    {
        return $this->render('bundles/TwigBundle/Exception/maintenance.html.twig', [
            'estimated_duration' => '10 minutes',
        ], new Response('', 503));
    }

    #[Route('/increment', name: 'app_increment', methods: ['POST'])]
    public function increment(): Response
    {
        // Simulate a delay to see HTMX in action
        sleep(1);
        
        // Get the counter value from the request and increment it
        $counter = (int) $_POST['counter'] + 1;
        
        return $this->render('test/_counter.html.twig', [
            'counter' => $counter,
        ]);
    }
}
