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
            $diagram = $this->parserService->parseUml($umlContent);

            // Convert diagram object to array structure for JSON response
            $result = $this->diagramToArray($diagram);

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

    /**
     * Convert a diagram object to an array representation
     *
     * @param mixed $diagram
     * @return array
     */
    private function diagramToArray($diagram): array
    {
        if (method_exists($diagram, 'getTitle')) {
            $result = [
                'title' => $diagram->getTitle(),
                'type' => get_class($diagram),
            ];

            // Handle class diagram
            if (method_exists($diagram, 'getClasses')) {
                $classes = [];
                foreach ($diagram->getClasses() as $class) {
                    $classes[] = [
                        'name' => $class->getName(),
                        'type' => $class->getType(),
                        'attributes' => $class->getAttributes(),
                        'methods' => $class->getMethods(),
                        'extends' => $class->getExtends(),
                        'implements' => $class->getImplements(),
                    ];
                }
                $result['classes'] = $classes;

                $relationships = [];
                foreach ($diagram->getRelationships() as $relationship) {
                    $relationships[] = [
                        'source' => $relationship->getSource(),
                        'target' => $relationship->getTarget(),
                        'type' => $relationship->getType(),
                        'label' => $relationship->getLabel(),
                    ];
                }
                $result['relationships'] = $relationships;
            }

            return $result;
        }

        // Fallback for unknown diagram types
        return [
            'type' => get_class($diagram),
        ];
    }
}
