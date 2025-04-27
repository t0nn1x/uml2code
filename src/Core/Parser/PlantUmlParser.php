<?php

namespace App\Core\Parser;

use App\Core\Parser\Models\ClassDiagram;
use App\Core\Parser\Models\ClassEntity;
use App\Core\Parser\Models\Relationship;
use App\Core\Parser\Exception\ParserException;

/**
 * PlantUML Parser
 * 
 * Parses PlantUML text and converts it to a structured format
 */
class PlantUmlParser implements ParserInterface
{
    /**
     * @var DiagramTypeDetector
     */
    private $typeDetector;

    public function __construct(DiagramTypeDetector $typeDetector)
    {
        $this->typeDetector = $typeDetector;
    }

    /**
     * Parse PlantUML text into a structured diagram model
     *
     * @param string $plantUmlText The PlantUML text to parse
     * @return mixed Returns a diagram model (ClassDiagram, SequenceDiagram, etc.)
     * @throws ParserException If parsing fails
     */
    public function parse(string $plantUmlText)
    {
        // Normalize line endings
        $plantUmlText = str_replace(["\r\n", "\r"], "\n", $plantUmlText);

        // Clean whitespace and comments
        $plantUmlText = $this->cleanInput($plantUmlText);

        // Detect diagram type
        $diagramType = $this->typeDetector->detectType($plantUmlText);

        // Parse according to diagram type
        switch ($diagramType) {
            case DiagramTypeDetector::TYPE_CLASS:
                return $this->parseClassDiagram($plantUmlText);
            case DiagramTypeDetector::TYPE_SEQUENCE:
                throw new ParserException("Sequence diagram parsing not yet implemented");
            case DiagramTypeDetector::TYPE_ACTIVITY:
                throw new ParserException("Activity diagram parsing not yet implemented");
            default:
                throw new ParserException("Unsupported diagram type: " . $diagramType);
        }
    }

    /**
     * Parse a class diagram
     *
     * @param string $plantUmlText
     * @return ClassDiagram
     */
    private function parseClassDiagram(string $plantUmlText): ClassDiagram
    {
        $diagram = new ClassDiagram();

        // Extract content between @startuml and @enduml
        preg_match('/\s*@startuml\s*(.+?)\s*@enduml\s*/s', $plantUmlText, $matches);
        if (empty($matches[1])) {
            throw new ParserException("Invalid PlantUML: missing @startuml/@enduml tags");
        }

        $content = $matches[1];

        // Extract diagram title if present
        if (preg_match('/title\s+(.+?)\s*\n/', $content, $titleMatches)) {
            $diagram->setTitle(trim($titleMatches[1]));
        }

        // Parse classes
        $this->parseClasses($content, $diagram);

        // Parse relationships
        $this->parseRelationships($content, $diagram);

        return $diagram;
    }

    /**
     * Parse class definitions from PlantUML text
     *
     * @param string $content PlantUML content
     * @param ClassDiagram $diagram The diagram to add entities to
     */
    private function parseClasses(string $content, ClassDiagram $diagram): void
    {
        // Match class definitions
        // Basic pattern: class ClassName { ... }
        $pattern = '/\b(class|interface|abstract\s+class|enum)\s+([A-Za-z0-9_]+)(?:\s+as\s+([A-Za-z0-9_]+))?(?:\s+([^{]*))?(?:\s*\{\s*(.*?)\s*\})?/s';

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $type = trim($match[1]);
            $name = $match[2];
            $alias = isset($match[3]) ? $match[3] : null;
            $extends = isset($match[4]) ? $this->parseExtendsPart($match[4]) : null;
            $body = isset($match[5]) ? $match[5] : '';

            $class = new ClassEntity();
            $class->setName($name);

            // Set type (class, interface, etc.)
            if ($type === 'interface') {
                $class->setInterface(true);
            } elseif ($type === 'abstract class') {
                $class->setAbstract(true);
            } elseif ($type === 'enum') {
                $class->setEnum(true);
            }

            // Parse class body (attributes and methods)
            if (!empty($body)) {
                $this->parseClassBody($body, $class);
            }

            $diagram->addClass($class);
        }
    }

    /**
     * Parse the extends/implements part of a class definition
     *
     * @param string $extendsPart
     * @return array [extends => string, implements => array]
     */
    private function parseExtendsPart(string $extendsPart): array
    {
        $result = [
            'extends' => null,
            'implements' => [],
        ];

        // Check for 'extends'
        if (preg_match('/extends\s+([A-Za-z0-9_]+)/', $extendsPart, $extendsMatch)) {
            $result['extends'] = $extendsMatch[1];
        }

        // Check for 'implements'
        if (preg_match('/implements\s+([^{]+)/', $extendsPart, $implementsMatch)) {
            $implementsList = explode(',', $implementsMatch[1]);
            $result['implements'] = array_map('trim', $implementsList);
        }

        return $result;
    }

    /**
     * Parse the body of a class definition
     *
     * @param string $body
     * @param ClassEntity $class
     */
    private function parseClassBody(string $body, ClassEntity $class): void
    {
        // Split into lines
        $lines = explode("\n", $body);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Detect if this is a method or attribute
            if (preg_match('/(.+)\(.*\)(\s*:\s*([A-Za-z0-9_<>]+))?/', $line, $methodMatch)) {
                // This is a method
                $methodName = trim($methodMatch[1]);
                $returnType = isset($methodMatch[3]) ? $methodMatch[3] : null;

                $visibility = $this->detectVisibility($methodName);
                $methodName = $this->stripVisibilityPrefix($methodName, $visibility);

                $class->addMethod([
                    'name' => $methodName,
                    'visibility' => $visibility,
                    'returnType' => $returnType
                ]);
            } else {
                // This is an attribute
                $attributeName = $line;
                $type = null;

                // Check for type
                if (preg_match('/(.+)\s*:\s*([A-Za-z0-9_<>]+)/', $line, $typeMatch)) {
                    $attributeName = trim($typeMatch[1]);
                    $type = $typeMatch[2];
                }

                $visibility = $this->detectVisibility($attributeName);
                $attributeName = $this->stripVisibilityPrefix($attributeName, $visibility);

                $class->addAttribute([
                    'name' => $attributeName,
                    'visibility' => $visibility,
                    'type' => $type
                ]);
            }
        }
    }

    /**
     * Parse relationship definitions
     *
     * @param string $content PlantUML content
     * @param ClassDiagram $diagram The diagram to add relationships to
     */
    private function parseRelationships(string $content, ClassDiagram $diagram): void
    {
        // Match relationship patterns
        // e.g., ClassA --> ClassB : association
        $pattern = '/([A-Za-z0-9_]+)\s+(--|<\|--|o--|<\|\.\.|\.\.|<\|--o|--o|<\.\.|\.\.>|\*--|\*\.\.|-\*|<--|\*-->|<\.\*|o\.\.|\.\.o|--\*|\.\.>|-->>|--|<-->>|<<-->>)\s+([A-Za-z0-9_]+)(?:\s*:\s*(.+))?/';

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $sourceClass = $match[1];
            $relationshipType = $match[2];
            $targetClass = $match[3];
            $label = isset($match[4]) ? trim($match[4]) : null;

            $relationship = new Relationship();
            $relationship->setSource($sourceClass);
            $relationship->setTarget($targetClass);
            $relationship->setType($this->mapRelationshipType($relationshipType));
            if ($label) {
                $relationship->setLabel($label);
            }

            $diagram->addRelationship($relationship);
        }
    }

    /**
     * Map PlantUML relationship syntax to relationship type
     *
     * @param string $syntax PlantUML relationship syntax
     * @return string Relationship type
     */
    private function mapRelationshipType(string $syntax): string
    {
        $map = [
            '--' => Relationship::TYPE_ASSOCIATION,
            '<|--' => Relationship::TYPE_INHERITANCE,
            '*--' => Relationship::TYPE_COMPOSITION,
            'o--' => Relationship::TYPE_AGGREGATION,
            '<--' => Relationship::TYPE_DEPENDENCY,
            '..' => Relationship::TYPE_ASSOCIATION,
            '<|..' => Relationship::TYPE_IMPLEMENTATION,
            '<|--o' => Relationship::TYPE_INHERITANCE,
            '--o' => Relationship::TYPE_ASSOCIATION,
            '<..' => Relationship::TYPE_DEPENDENCY,
            '..>' => Relationship::TYPE_DEPENDENCY,
            '*--' => Relationship::TYPE_COMPOSITION,
            '*..' => Relationship::TYPE_COMPOSITION,
            '-*' => Relationship::TYPE_COMPOSITION,
            '<--' => Relationship::TYPE_DEPENDENCY,
            '*-->' => Relationship::TYPE_COMPOSITION,
            '<.*' => Relationship::TYPE_DEPENDENCY,
            'o..' => Relationship::TYPE_AGGREGATION,
            '..o' => Relationship::TYPE_AGGREGATION,
            '--*' => Relationship::TYPE_COMPOSITION,
            '..>' => Relationship::TYPE_DEPENDENCY,
            '-->' => Relationship::TYPE_ASSOCIATION,
            '<-->>' => Relationship::TYPE_BIDIRECTIONAL,
            '<<-->>' => Relationship::TYPE_BIDIRECTIONAL,
        ];

        return $map[$syntax] ?? Relationship::TYPE_ASSOCIATION;
    }

    /**
     * Detect visibility from method or attribute name
     *
     * @param string $name Name with possible visibility prefix
     * @return string Visibility constant
     */
    private function detectVisibility(string $name): string
    {
        if (strpos($name, '+') === 0) {
            return ClassEntity::VISIBILITY_PUBLIC;
        } elseif (strpos($name, '-') === 0) {
            return ClassEntity::VISIBILITY_PRIVATE;
        } elseif (strpos($name, '#') === 0) {
            return ClassEntity::VISIBILITY_PROTECTED;
        } elseif (strpos($name, '~') === 0) {
            return ClassEntity::VISIBILITY_PACKAGE;
        }

        return ClassEntity::VISIBILITY_PUBLIC;
    }

    /**
     * Remove visibility prefix from name
     *
     * @param string $name Name with possible visibility prefix
     * @param string $detectedVisibility The detected visibility
     * @return string Name without visibility prefix
     */
    private function stripVisibilityPrefix(string $name, string $detectedVisibility): string
    {
        if ($detectedVisibility !== ClassEntity::VISIBILITY_PUBLIC) {
            return substr($name, 1);
        }

        return $name;
    }

    /**
     * Clean the input by removing comments and normalizing whitespace
     *
     * @param string $input
     * @return string
     */
    private function cleanInput(string $input): string
    {
        // Remove single-line comments
        $input = preg_replace('/' . preg_quote("'") . '.*$/m', '', $input);

        // Convert tabs to spaces
        $input = str_replace("\t", '    ', $input);

        return $input;
    }
}
