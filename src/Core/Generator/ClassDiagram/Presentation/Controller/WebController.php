<?php

namespace App\Core\Generator\ClassDiagram\Presentation\Controller;

use App\Core\Generator\ClassDiagram\Application\Service\CodeGeneratorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Web Controller for code generation UI
 */
#[Route('/generator')]
class WebController extends AbstractController
{
    /**
     * @var CodeGeneratorService The code generator service
     */
    private CodeGeneratorService $generatorService;

    /**
     * Create a new Web controller
     *
     * @param CodeGeneratorService $generatorService The code generator service
     */
    public function __construct(CodeGeneratorService $generatorService)
    {
        $this->generatorService = $generatorService;
    }

    /**
     * Display the code generator UI
     */
    #[Route('/', name: 'generator_index')]
    public function index(): Response
    {
        $supportedLanguages = $this->generatorService->getSupportedLanguages();
        
        return $this->render('generator/index.html.twig', [
            'supported_languages' => $supportedLanguages
        ]);
    }
} 
