<?php

namespace App\Core\Parser\ClassDiagram\Presentation\Controller;

use App\Core\Parser\ClassDiagram\Application\Service\UmlParserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Web Controller for UML parser pages
 */
#[Route('/parser')]
class WebController extends AbstractController
{
    /**
     * @var UmlParserService The UML parser service
     */
    private UmlParserService $parserService;
    
    /**
     * Create a new UML parser controller
     * 
     * @param UmlParserService $parserService
     */
    public function __construct(UmlParserService $parserService)
    {
        $this->parserService = $parserService;
    }
    
    /**
     * Show the UML parser testing page
     */
    #[Route('/', name: 'app_uml_parser')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        return $this->render('uml_parser/index.html.twig', [
            'page_title' => 'UML Parser'
        ]);
    }
}
