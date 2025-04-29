<?php

namespace App\Core\Parser\ClassDiagram\Presentation\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Web Controller for UML parser web pages
 */
class WebController extends AbstractController
{
    /**
     * Show the UML parser testing page
     */
    #[Route('/uml-parser', name: 'app_uml_parser')]
    public function index(): Response
    {
        return $this->render('uml_parser/index.html.twig');
    }
}
