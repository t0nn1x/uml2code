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
 * API Controller for code generation operations
 */
#[Route('/api/generator')]
class ApiController extends AbstractController
{
    /**
     * @var CodeGeneratorService The code generator service
     */
    private CodeGeneratorService $generatorService;

    /**
     * Create a new API controller
     *
     * @param CodeGeneratorService $generatorService The code generator service
     */
    public function __construct(CodeGeneratorService $generatorService)
    {
        $this->generatorService = $generatorService;
    }

    /**
     * Generate code from a UML class diagram
     */
    #[Route('/generate', name: 'api_generator_generate', methods: ['POST'])]
    public function generate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['diagram']) || !is_array($data['diagram'])) {
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
        $version = $data['version'] ?? '7.4'; // Default to PHP 7.4

        try {
            // Set a default namespace prefix based on the diagram title if available
            if (isset($diagram['title'])) {
                $namespacePrefix = 'App\\' . $this->formatNamespace($diagram['title']);
                $this->generatorService->setNamespacePrefix($namespacePrefix);
            }

            // Generate code from the diagram
            $files = $this->generatorService->generateCode($diagram, $language, $version);

            return $this->json([
                'success' => true,
                'files' => $files
            ]);
        } catch (GeneratorException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'An unexpected error occurred: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Transform a diagram title into a valid namespace component
     *
     * @param string $title The diagram title
     * @return string A valid namespace component
     */
    private function formatNamespace(string $title): string
    {
        // Remove non-alphanumeric characters and replace with underscores
        $namespace = preg_replace('/[^a-zA-Z0-9]/', '_', $title);
        
        // Ensure it starts with a letter
        if (preg_match('/^[0-9]/', $namespace)) {
            $namespace = 'N' . $namespace;
        }
        
        // Convert to PascalCase
        $namespace = implode('', array_map('ucfirst', explode('_', $namespace)));
        
        return $namespace;
    }

    /**
     * Get supported languages and versions
     */
    #[Route('/languages', name: 'api_generator_languages', methods: ['GET'])]
    public function languages(): JsonResponse
    {
        $supportedLanguages = $this->generatorService->getSupportedLanguages();

        return $this->json([
            'success' => true,
            'languages' => $supportedLanguages
        ]);
    }
} 
