<?php

namespace App\Core\Converter\ClassDiagram\Presentation\Controller;

use App\Core\Converter\ClassDiagram\Application\Service\UmlCodeConverterService;
use App\Core\Converter\ClassDiagram\Domain\Exception\ConverterException;
use App\Service\ActionHistoryService;
use App\Entity\ActionHistory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * API Controller for UML to Code conversion
 */
#[Route('/api/converter')]
class ApiController extends AbstractController
{
    /**
     * @var UmlCodeConverterService The UML code converter service
     */
    private UmlCodeConverterService $converterService;

    /**
     * @var ActionHistoryService The action history service
     */
    private ActionHistoryService $historyService;

    /**
     * Create a new converter API controller
     *
     * @param UmlCodeConverterService $converterService The UML code converter service
     * @param ActionHistoryService $historyService The action history service
     */
    public function __construct(
        UmlCodeConverterService $converterService,
        ActionHistoryService $historyService
    ) {
        $this->converterService = $converterService;
        $this->historyService = $historyService;
    }

    /**
     * Convert UML to code directly
     */
    #[Route('/convert', name: 'api_converter_convert', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function convert(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['uml']) || empty($data['uml'])) {
            return $this->json([
                'success' => false,
                'error' => 'UML content is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['language']) || empty($data['language'])) {
            return $this->json([
                'success' => false,
                'error' => 'Target language is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $umlContent = $data['uml'];
        $language = $data['language'];
        $version = $data['version'] ?? '';

        try {
            // Validate UML syntax first
            if (!$this->converterService->validateUml($umlContent)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Invalid UML syntax'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Convert UML to code
            $generatedFiles = $this->converterService->convertUmlToCode($umlContent, $language, $version);

            // Record in history
            $user = $this->getUser();
            if ($user) {
                $this->historyService->record(
                    $user,
                    ActionHistory::ACTION_CONVERT,
                    $generatedFiles,
                    'ClassDiagram'
                );
            }

            return $this->json([
                'success' => true,
                'files' => array_values($generatedFiles) // Ensure array is indexed numerically
            ]);
        } catch (ConverterException $e) {
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
     * Get supported languages and versions
     */
    #[Route('/languages', name: 'api_converter_languages', methods: ['GET'])]
    public function getSupportedLanguages(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'languages' => $this->converterService->getSupportedLanguages()
        ]);
    }

    /**
     * Generate code from UML content - this is a legacy endpoint for backward compatibility
     */
    #[Route('/from-uml', name: 'api_converter_from_uml', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generateFromUml(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['uml']) || empty($data['uml'])) {
            return $this->json([
                'success' => false,
                'error' => 'UML content is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['language']) || empty($data['language'])) {
            return $this->json([
                'success' => false,
                'error' => 'Target language is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $umlContent = $data['uml'];
        $language = $data['language'];
        $version = $data['version'] ?? '';

        try {
            // Validate UML syntax first
            if (!$this->converterService->validateUml($umlContent)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Invalid UML syntax'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Convert UML to code
            $generatedFiles = $this->converterService->convertUmlToCode($umlContent, $language, $version);

            // Record in history
            $user = $this->getUser();
            if ($user) {
                $this->historyService->record(
                    $user,
                    ActionHistory::ACTION_CONVERT,
                    $generatedFiles,
                    'ClassDiagram'
                );
            }

            return $this->json([
                'success' => true,
                'files' => array_values($generatedFiles)
            ]);
        } catch (ConverterException $e) {
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
}
