<?php

namespace App\Core\Generator\ClassDiagram\Presentation\Controller;

use App\Core\Generator\ClassDiagram\Application\Service\CodeGeneratorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Web Controller for code generator page
 */
#[Route('/generator')]
class WebController extends AbstractController
{
    /**
     * @var CodeGeneratorService The code generator service
     */
    private CodeGeneratorService $generatorService;
    
    /**
     * Create a new code generator controller
     * 
     * @param CodeGeneratorService $generatorService
     */
    public function __construct(CodeGeneratorService $generatorService)
    {
        $this->generatorService = $generatorService;
    }
    
    /**
     * Show the code generator page
     */
    #[Route('/', name: 'app_generator')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        // Get supported languages for pre-loading in the UI
        $supportedLanguages = $this->generatorService->getSupportedLanguages();
        
        return $this->render('uml2code/index.html.twig', [
            'page_title' => 'Code Generator',
            'supported_languages' => $supportedLanguages
        ]);
    }
} 
