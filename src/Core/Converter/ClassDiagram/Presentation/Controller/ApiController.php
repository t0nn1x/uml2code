<?php

namespace App\Core\Converter\ClassDiagram\Presentation\Controller;

use App\Core\Converter\ClassDiagram\Application\Service\UmlCodeConverterService;
use App\Core\Converter\ClassDiagram\Domain\Exception\ConverterException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API Controller for UML to Code conversion
 */
#[Route('/converter')]
class ApiController extends AbstractController
{
    /**
     * @var UmlCodeConverterService The UML code converter service
     */
    private UmlCodeConverterService $converterService;
    
    /**
     * Create a new converter API controller
     *
     * @param UmlCodeConverterService $converterService The UML code converter service
     */
    public function __construct(UmlCodeConverterService $converterService)
    {
        $this->converterService = $converterService;
    }
    
    /**
     * Convert UML to code directly
     */
    #[Route('/convert', name: 'api_converter_convert', methods: ['POST'])]
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
