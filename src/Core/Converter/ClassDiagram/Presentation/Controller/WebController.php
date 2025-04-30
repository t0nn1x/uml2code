<?php

namespace App\Core\Converter\ClassDiagram\Presentation\Controller;

use App\Core\Converter\ClassDiagram\Application\Service\UmlCodeConverterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Web Controller for UML to Code direct conversion page
 */
#[Route('/converter')]
class WebController extends AbstractController
{
    /**
     * @var UmlCodeConverterService The UML code converter service
     */
    private UmlCodeConverterService $converterService;
    
    /**
     * Create a new converter controller
     * 
     * @param UmlCodeConverterService $converterService
     */
    public function __construct(UmlCodeConverterService $converterService)
    {
        $this->converterService = $converterService;
    }
    
    /**
     * Show the UML to Code direct conversion page
     */
    #[Route('/', name: 'app_converter')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        // Get supported languages for pre-loading in the UI
        $supportedLanguages = $this->converterService->getSupportedLanguages();
        
        return $this->render('converter/index.html.twig', [
            'page_title' => 'UML to Code Converter',
            'supported_languages' => $supportedLanguages
        ]);
    }
} 
