<?php

namespace App\Core\Generator\ClassDiagram\Presentation\Controller;

use App\Core\Generator\ClassDiagram\Application\Service\CodeGeneratorService;
use App\Core\Generator\ClassDiagram\Domain\Exception\GeneratorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API Controller for code generation
 */
#[Route('/api/generator')]
class ApiController extends AbstractController
{
    /**
     * @var CodeGeneratorService The code generator service
     */
    private CodeGeneratorService $generatorService;
    
    /**
     * Create a new generator API controller
     *
     * @param CodeGeneratorService $generatorService The code generator service
     */
    public function __construct(CodeGeneratorService $generatorService)
    {
        $this->generatorService = $generatorService;
    }
    
    /**
     * Generate code from JSON diagram
     */
    #[Route('/generate', name: 'api_generator_generate', methods: ['POST'])]
    public function generate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['diagram']) || empty($data['diagram'])) {
            return $this->json([
                'success' => false,
                'error' => 'Diagram data is required'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        if (!isset($data['language']) || empty($data['language'])) {
            return $this->json([
                'success' => false,
                'error' => 'Target language is required'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $diagram = $data['diagram'];
        $language = $data['language'];
        $version = $data['version'] ?? '';
        
        try {
            // Generate code using the specified language and version
            $generatedFiles = $this->generatorService->generateCode($diagram, $language, $version);
            
            return $this->json([
                'success' => true,
                'files' => array_values($generatedFiles)
            ]);
        } catch (GeneratorException $e) {
            return $this->json([
                'success' => false,
                'error' => 'Generator Error: ' . $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'An unexpected error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Get supported languages for code generation
     */
    #[Route('/languages', name: 'api_generator_languages', methods: ['GET'])]
    public function getSupportedLanguages(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'languages' => $this->generatorService->getSupportedLanguages()
        ]);
    }
} 
