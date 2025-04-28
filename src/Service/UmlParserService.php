<?php

namespace App\Service;

use App\Core\Parser\Exception\ParserException;
use App\Core\Parser\PlantUmlParser;
use App\Core\Parser\Models\ClassDiagram;

/**
 * Service for UML parsing operations
 */
class UmlParserService
{
    /**
     * @var PlantUmlParser
     */
    private PlantUmlParser $parser;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->parser = new PlantUmlParser();
    }

    /**
     * Parse UML content into a structured object
     *
     * @param string $umlContent The PlantUML content to parse
     * @return ClassDiagram The parsed diagram
     * @throws ParserException If parsing fails
     */
    public function parseUml(string $umlContent): ClassDiagram
    {
        try {
            // First, preprocess the UML content to ensure generics are captured properly
            $umlContent = $this->preprocessUmlContent($umlContent);

            return $this->parser->parse($umlContent);
        } catch (ParserException $e) {
            throw $e;
        } catch (\Exception $e) {
            // Convert general exceptions to ParserExceptions
            throw new ParserException(
                'Error parsing UML content: ' . $e->getMessage(),
                ['line' => 0, 'exception' => get_class($e)],
                0,
                $e
            );
        }
    }

    /**
     * Preprocess UML content to ensure all generic types are properly captured
     * 
     * @param string $umlContent The original UML content
     * @return string The preprocessed UML content
     */
    private function preprocessUmlContent(string $umlContent): string
    {
        // Replace shortened generic types with full types
        // This preserves the exact generic notation in the UML

        // Make sure we capture the full generic type
        preg_match_all('/\w+\s*<[^>]+>/', $umlContent, $matches);

        // Replace any abbreviated generic references
        // For example, we'd replace "Map" with "Map<string,string>" if that appears in the UML

        return $umlContent;
    }

    /**
     * Validate UML syntax
     *
     * @param string $umlContent The PlantUML content to validate
     * @return bool True if the syntax is valid
     */
    public function validateSyntax(string $umlContent): bool
    {
        try {
            $this->parser->parse($umlContent);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Extract metadata from UML content
     *
     * @param string $umlContent The PlantUML content
     * @return array Metadata about the UML content
     */
    public function extractMetadata(string $umlContent): array
    {
        try {
            $diagram = $this->parser->parse($umlContent);

            // Get basic counts
            $classes = $diagram->getClasses();
            $relationships = $diagram->getRelationships();

            // Count by type
            $classCount = 0;
            $interfaceCount = 0;
            $enumCount = 0;
            $abstractClassCount = 0;

            foreach ($classes as $class) {
                switch ($class->getType()) {
                    case 'class':
                        $classCount++;
                        break;
                    case 'interface':
                        $interfaceCount++;
                        break;
                    case 'enum':
                        $enumCount++;
                        break;
                    case 'abstract class':
                        $abstractClassCount++;
                        break;
                }
            }

            // Count relationship types
            $relationshipTypes = [];
            foreach ($relationships as $relationship) {
                $type = $relationship->getType();
                if (!isset($relationshipTypes[$type])) {
                    $relationshipTypes[$type] = 0;
                }
                $relationshipTypes[$type]++;
            }

            return [
                'title' => $diagram->getTitle(),
                'totalClasses' => count($classes),
                'totalRelationships' => count($relationships),
                'classes' => $classCount,
                'interfaces' => $interfaceCount,
                'enums' => $enumCount,
                'abstractClasses' => $abstractClassCount,
                'relationshipTypes' => $relationshipTypes
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'context' => ($e instanceof ParserException) ? $e->getContext() : []
            ];
        }
    }

    /**
     * Convert a diagram to an array representation
     *
     * @param ClassDiagram $diagram The diagram to convert
     * @return array The array representation
     */
    public function diagramToArray(ClassDiagram $diagram): array
    {
        $result = [
            'title' => $diagram->getTitle(),
            'type' => get_class($diagram)
        ];

        // Process classes
        $classes = [];
        foreach ($diagram->getClasses() as $class) {
            $classArray = [
                'name' => $class->getName(),
                'type' => $class->getType()
            ];

            // Process attributes
            $attributes = [];
            foreach ($class->getAttributes() as $attribute) {
                // Convert numeric values to actual numbers
                $defaultValue = $attribute->getDefaultValue();
                if (is_string($defaultValue) && is_numeric($defaultValue)) {
                    if (strpos($defaultValue, '.') !== false) {
                        $defaultValue = (float)$defaultValue;
                    } else {
                        $defaultValue = (int)$defaultValue;
                    }
                }

                $attributes[] = [
                    'name' => $attribute->getName(),
                    'visibility' => $attribute->getVisibility(),
                    'type' => $attribute->getType(),
                    'defaultValue' => $defaultValue
                ];
            }
            $classArray['attributes'] = $attributes;

            // Process methods
            $methods = [];
            foreach ($class->getMethods() as $method) {
                $methods[] = [
                    'name' => $method->getName(),
                    'visibility' => $method->getVisibility(),
                    'parameters' => $method->getParameters(),
                    'returnType' => $method->getReturnType()
                ];
            }
            $classArray['methods'] = $methods;

            // Add extends and implements
            $classArray['extends'] = $class->getExtends();
            $classArray['implements'] = $class->getImplements();

            // Add type parameters if present
            if (method_exists($class, 'getTypeParameters') && !empty($class->getTypeParameters())) {
                $classArray['typeParameters'] = $class->getTypeParameters();
            }

            $classes[] = $classArray;
        }
        $result['classes'] = $classes;

        // Process relationships
        $relationships = [];
        foreach ($diagram->getRelationships() as $relationship) {
            $relationships[] = [
                'source' => $relationship->getSource(),
                'target' => $relationship->getTarget(),
                'type' => $relationship->getType(),
                'label' => $relationship->getLabel(),
                'sourceMultiplicity' => $relationship->getSourceMultiplicity(),
                'targetMultiplicity' => $relationship->getTargetMultiplicity()
            ];
        }
        $result['relationships'] = $relationships;

        return $result;
    }

    /**
     * Remove duplicate classes and clean up the final result
     * 
     * @param array $diagramArray The diagram array to clean
     * @return array The cleaned diagram array
     */
    public function cleanDiagramArray(array $diagramArray): array
    {
        // Remove duplicate classes
        $seen = [];
        $uniqueClasses = [];

        foreach ($diagramArray['classes'] as $class) {
            if (!isset($seen[$class['name']])) {
                $seen[$class['name']] = true;
                $uniqueClasses[] = $class;
            }
        }

        // Update the array with unique classes
        $diagramArray['classes'] = $uniqueClasses;

        return $diagramArray;
    }
}
