<?php

namespace App\Core\Parser\ClassDiagram\Presentation\Controller;

use App\Core\Parser\ClassDiagram\Application\Service\UmlParserService;
use App\Core\Parser\ClassDiagram\Domain\Exception\ParserException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API Controller for UML parsing operations
 */
#[Route('/api/uml')]
class ApiController extends AbstractController
{
    /**
     * @var UmlParserService The UML parser service
     */
    private UmlParserService $parserService;

    /**
     * Create a new API controller
     *
     * @param UmlParserService $parserService The UML parser service
     */
    public function __construct(UmlParserService $parserService)
    {
        $this->parserService = $parserService;
    }

    /**
     * Parse UML content into JSON
     */
    #[Route('/parse', name: 'api_uml_parse', methods: ['POST'])]
    public function parse(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['uml']) || empty($data['uml'])) {
            return $this->json([
                'success' => false,
                'error' => 'UML content is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $umlContent = $data['uml'];

        try {
            // Parse the UML diagram to array
            $diagram = $this->parserService->parseUmlToArray($umlContent);

            // Clean the array (remove duplicates and preserve generics)
            $diagram = $this->parserService->cleanDiagramArray($diagram);

            // Process generic types specifically
            $diagram = $this->processGenericTypes($diagram);

            return $this->json([
                'success' => true,
                'diagram' => $diagram
            ]);
        } catch (ParserException $e) {
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
     * Process generic types in the diagram array to ensure they are properly represented in the JSON output
     *
     * @param array $diagram The diagram array
     * @return array The processed diagram array
     */
    private function processGenericTypes(array $diagram): array
    {
        if (isset($diagram['classes']) && is_array($diagram['classes'])) {
            foreach ($diagram['classes'] as $classIndex => $class) {
                // Process attributes
                if (isset($class['attributes']) && is_array($class['attributes'])) {
                    foreach ($class['attributes'] as $attrIndex => $attribute) {
                        if (isset($attribute['type']) && strpos($attribute['type'], '<') !== false) {
                            // This is a generic type, parse it to extract type and arguments
                            $matches = [];
                            if (preg_match('/^(\w+)\s*<\s*(.+?)\s*>\s*$/', $attribute['type'], $matches)) {
                                $baseType = $matches[1];
                                $typeArgs = $matches[2];

                                // Store the complete type expression
                                $diagram['classes'][$classIndex]['attributes'][$attrIndex]['fullType'] = $attribute['type'];

                                // Process and store type arguments
                                $typeArgsList = explode(',', $typeArgs);
                                $cleanTypeArgs = array_map('trim', $typeArgsList);
                                $diagram['classes'][$classIndex]['attributes'][$attrIndex]['typeArguments'] = $cleanTypeArgs;
                            }
                        }
                    }
                }

                // Process method parameters and return types (similar logic could be applied)
            }
        }

        return $diagram;
    }

    /**
     * Validate UML syntax
     */
    #[Route('/validate', name: 'api_uml_validate', methods: ['POST'])]
    public function validate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['uml']) || empty($data['uml'])) {
            return $this->json([
                'success' => false,
                'error' => 'UML content is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $umlContent = $data['uml'];
        $isValid = $this->parserService->validateSyntax($umlContent);

        return $this->json([
            'success' => true,
            'valid' => $isValid
        ]);
    }

    /**
     * Extract metadata from UML content
     */
    #[Route('/metadata', name: 'api_uml_metadata', methods: ['POST'])]
    public function metadata(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['uml']) || empty($data['uml'])) {
            return $this->json([
                'success' => false,
                'error' => 'UML content is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $umlContent = $data['uml'];
        $metadata = $this->parserService->extractMetadata($umlContent);

        return $this->json([
            'success' => true,
            'metadata' => $metadata
        ]);
    }
}
