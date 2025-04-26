<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/test', name: 'app_test')]
    public function index(): Response
    {
        return $this->render('test/index.html.twig', [
            'counter' => 0,
        ]);
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
