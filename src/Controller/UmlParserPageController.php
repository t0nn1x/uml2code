<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for UML parser web pages
 */
class UmlParserPageController extends AbstractController
{
    /**
     * Show the UML parser testing page
     */
    #[Route('/uml-parser', name: 'app_uml_parser')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        return $this->render('uml_parser/index.html.twig');
    }
}
