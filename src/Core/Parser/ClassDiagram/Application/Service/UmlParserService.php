<?php

namespace App\Core\Parser\ClassDiagram\Application\Service;

use App\Core\Parser\ClassDiagram\Domain\Exception\ParserException;
use App\Core\Parser\ClassDiagram\Domain\Model\ClassDiagram;
use App\Core\Parser\ClassDiagram\Infrastructure\Parser\PlantUmlParser;

/**
 * Service for parsing UML diagrams
 */
class UmlParserService
{
    /**
     * @var ClassDiagramParserInterface The class diagram parser
     */
    private ClassDiagramParserInterface $parser;

    /**
     * Create a new UML parser service
     *
     * @param ClassDiagramParserInterface|null $parser The parser to use
     */
    public function __construct(?ClassDiagramParserInterface $parser = null)
    {
        // Default to PlantUML parser if none provided
        $this->parser = $parser ?? new PlantUmlParser();
    }

    /**
     * Parse UML content into a class diagram model
     *
     * @param string $content The UML content to parse
     * @return ClassDiagram The parsed class diagram
     * @throws ParserException If parsing fails
     */
    public function parseUml(string $content): ClassDiagram
    {
        return $this->parser->parse($content);
    }

    /**
     * Convert a class diagram model to an array representation
     *
     * @param ClassDiagram $diagram The class diagram model
     * @return array The array representation
     */
    public function diagramToArray(ClassDiagram $diagram): array
    {
        return $diagram->toArray();
    }

    /**
     * Parse UML content and convert to an array representation
     *
     * @param string $content The UML content to parse
     * @return array The array representation
     * @throws ParserException If parsing fails
     */
    public function parseUmlToArray(string $content): array
    {
        $diagram = $this->parseUml($content);
        return $this->diagramToArray($diagram);
    }

    /**
     * Validate UML syntax
     *
     * @param string $content The UML content to validate
     * @return bool True if the syntax is valid
     */
    public function validateSyntax(string $content): bool
    {
        return $this->parser->validate($content);
    }

    /**
     * Extract metadata from UML content
     *
     * @param string $content The UML content to analyze
     * @return array Metadata about the content
     */
    public function extractMetadata(string $content): array
    {
        return $this->parser->extractMetadata($content);
    }

    /**
     * Clean a diagram array by removing duplicates and fixing inconsistencies
     * This also ensures that generic types are properly preserved
     *
     * @param array $diagram The diagram array to clean
     * @return array The cleaned diagram array
     */
    public function cleanDiagramArray(array $diagram): array
    {
        // Process classes - remove duplicates
        if (isset($diagram['classes']) && is_array($diagram['classes'])) {
            $uniqueClasses = [];
            $seenClasses = [];

            foreach ($diagram['classes'] as $class) {
                if (!isset($class['name'])) {
                    continue;
                }

                $name = $class['name'];

                if (!isset($seenClasses[$name])) {
                    $seenClasses[$name] = true;

                    // Process attributes to ensure generic types are properly represented
                    if (isset($class['attributes']) && is_array($class['attributes'])) {
                        foreach ($class['attributes'] as $index => $attribute) {
                            if (isset($attribute['typeArguments']) && !empty($attribute['typeArguments'])) {
                                // For attributes with type arguments, make sure the type string includes the generic parameters
                                if (isset($attribute['type'])) {
                                    $baseType = $attribute['type'];
                                    $typeArgs = implode(', ', $attribute['typeArguments']);
                                    $class['attributes'][$index]['type'] = "$baseType<$typeArgs>";
                                }
                            }
                        }
                    }

                    $uniqueClasses[] = $class;
                }
            }

            $diagram['classes'] = $uniqueClasses;
        }

        // Process relationships - remove duplicates
        if (isset($diagram['relationships']) && is_array($diagram['relationships'])) {
            $uniqueRelationships = [];
            $seenRelationships = [];

            foreach ($diagram['relationships'] as $relationship) {
                if (!isset($relationship['source']) || !isset($relationship['target']) || !isset($relationship['type'])) {
                    continue;
                }

                $key = $relationship['source'] . '-' . $relationship['type'] . '-' . $relationship['target'];

                if (!isset($seenRelationships[$key])) {
                    $seenRelationships[$key] = true;
                    $uniqueRelationships[] = $relationship;
                }
            }

            $diagram['relationships'] = $uniqueRelationships;
        }

        return $diagram;
    }

    /**
     * Process generics in the diagram model before conversion to array
     * 
     * @param ClassDiagram $diagram The class diagram model
     * @return ClassDiagram The processed class diagram
     */
    private function processGenerics(ClassDiagram $diagram): ClassDiagram
    {
        // This method could be used to process generic types further if needed
        return $diagram;
    }
}
