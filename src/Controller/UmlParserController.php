<?php

namespace App\Controller;

use App\Core\Parser\Exception\ParserException;
use App\Service\UmlParserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for UML parsing operations
 */
#[Route('/api/uml')]
class UmlParserController extends AbstractController
{
    /**
     * @var UmlParserService
     */
    private $parserService;

    /**
     * @param UmlParserService $parserService
     */
    public function __construct(UmlParserService $parserService)
    {
        $this->parserService = $parserService;
    }

    /**
     * Parse UML content
     */
    #[Route('/parse', name: 'app_uml_parse', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function parse(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['uml']) || empty($data['uml'])) {
            return $this->json(['error' => 'UML content is required'], Response::HTTP_BAD_REQUEST);
        }

        $umlContent = $data['uml'];

        try {
            // Parse the UML diagram
            $diagram = $this->parserService->parseUml($umlContent);

            // Convert to array
            $result = $this->parserService->diagramToArray($diagram);

            // Clean the array (remove duplicates)
            $result = $this->parserService->cleanDiagramArray($result);

            return $this->json([
                'success' => true,
                'diagram' => $result
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
    #[Route('/validate', name: 'app_uml_validate', methods: ['POST'])]
    public function validate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['uml']) || empty($data['uml'])) {
            return $this->json(['error' => 'UML content is required'], Response::HTTP_BAD_REQUEST);
        }

        $umlContent = $data['uml'];
        $isValid = $this->parserService->validateSyntax($umlContent);

        return $this->json([
            'valid' => $isValid
        ]);
    }

    /**
     * Extract metadata from UML
     */
    #[Route('/metadata', name: 'app_uml_metadata', methods: ['POST'])]
    public function metadata(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['uml']) || empty($data['uml'])) {
            return $this->json(['error' => 'UML content is required'], Response::HTTP_BAD_REQUEST);
        }

        $umlContent = $data['uml'];
        $metadata = $this->parserService->extractMetadata($umlContent);

        return $this->json([
            'metadata' => $metadata
        ]);
    }
}
