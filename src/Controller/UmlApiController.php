<?php

namespace App\Controller;

use App\Core\Parser\ClassDiagram\Application\Service\UmlParserService;
use App\Core\Parser\ClassDiagram\Domain\Exception\ParserException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for UML parsing API endpoints
 */
#[Route('/api/uml')]
class UmlApiController extends AbstractController
{
    /**
     * @var UmlParserService The UML parser service
     */
    private UmlParserService $parserService;

    /**
     * Create a new UML API controller
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

            // Clean the array (remove duplicates)
            $diagram = $this->parserService->cleanDiagramArray($diagram);

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
                'error' => 'An unexpected error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
