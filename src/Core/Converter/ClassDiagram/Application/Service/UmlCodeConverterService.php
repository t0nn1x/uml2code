<?php

namespace App\Core\Converter\ClassDiagram\Application\Service;

use App\Core\Converter\ClassDiagram\Domain\Exception\ConverterException;
use App\Core\Parser\ClassDiagram\Application\Service\UmlParserService;
use App\Core\Parser\ClassDiagram\Domain\Exception\ParserException;
use App\Core\Generator\ClassDiagram\Application\Service\CodeGeneratorService;
use App\Core\Generator\ClassDiagram\Domain\Exception\GeneratorException;

/**
 * Service to directly convert UML to code
 */
class UmlCodeConverterService
{
    /**
     * @var UmlParserService The UML parser service
     */
    private UmlParserService $parserService;
    
    /**
     * @var CodeGeneratorService The code generator service
     */
    private CodeGeneratorService $generatorService;
    
    /**
     * Create a new UML code converter service
     *
     * @param UmlParserService $parserService The UML parser service
     * @param CodeGeneratorService $generatorService The code generator service
     */
    public function __construct(UmlParserService $parserService, CodeGeneratorService $generatorService)
    {
        $this->parserService = $parserService;
        $this->generatorService = $generatorService;
    }
    
    /**
     * Convert UML directly to code
     *
     * @param string $umlContent The UML content to convert
     * @param string $language The target language (e.g., "PHP")
     * @param string $version The language version (e.g., "7.4")
     * @return array The generated code files
     * @throws ConverterException If conversion fails
     */
    public function convertUmlToCode(string $umlContent, string $language, string $version): array
    {
        try {
            // Step 1: Parse UML to array
            $diagram = $this->parserService->parseUmlToArray($umlContent);
            
            // Step 2: Clean the diagram array
            $diagram = $this->parserService->cleanDiagramArray($diagram);
            
            // Step 3: Generate code
            $generatedFiles = $this->generatorService->generateCode($diagram, $language, $version);
            
            // Ensure the result is a numerically indexed array
            return array_values($generatedFiles);
        } catch (ParserException $e) {
            throw new ConverterException('Parser error: ' . $e->getMessage(), $e->getContext(), 0, $e);
        } catch (GeneratorException $e) {
            throw new ConverterException('Generator error: ' . $e->getMessage(), null, 0, $e);
        } catch (\Exception $e) {
            throw new ConverterException('Unexpected error during conversion: ' . $e->getMessage(), null, 0, $e);
        }
    }
    
    /**
     * Get supported languages and versions
     *
     * @return array
     */
    public function getSupportedLanguages(): array
    {
        return $this->generatorService->getSupportedLanguages();
    }
    
    /**
     * Check if UML content is valid
     *
     * @param string $umlContent The UML content to validate
     * @return bool True if the UML is valid
     */
    public function validateUml(string $umlContent): bool
    {
        return $this->parserService->validateSyntax($umlContent);
    }
} 
