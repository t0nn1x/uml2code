<?php

namespace App\Core\Parser\ClassDiagram\Infrastructure\Parser;

use App\Core\Parser\ClassDiagram\Application\Service\ClassDiagramParserInterface;
use App\Core\Parser\ClassDiagram\Domain\Exception\ParserException;
use App\Core\Parser\ClassDiagram\Domain\Model\Attribute;
use App\Core\Parser\ClassDiagram\Domain\Model\ClassDiagram;
use App\Core\Parser\ClassDiagram\Domain\Model\ClassElement;
use App\Core\Parser\ClassDiagram\Domain\Model\Method;
use App\Core\Parser\ClassDiagram\Domain\Model\Relationship;
use App\Core\Parser\ClassDiagram\Domain\ValueObject\Parameter;
use App\Core\Parser\ClassDiagram\Domain\ValueObject\RelationshipType;
use App\Core\Parser\ClassDiagram\Domain\ValueObject\Type;
use App\Core\Parser\ClassDiagram\Domain\ValueObject\Visibility;

/**
 * Parser for PlantUML class diagrams
 */
class PlantUmlParser implements ClassDiagramParserInterface
{
    /**
     * @var string The PlantUML content to parse
     */
    private string $content;

    /**
     * @var array Lines of PlantUML content
     */
    private array $lines = [];

    /**
     * @var int Current line being processed
     */
    private int $currentLine = 0;

    /**
     * @var ClassDiagram The diagram model being built
     */
    private ClassDiagram $diagram;

    /**
     * @var array List of relationship definitions
     */
    private array $relationships = [];

    /**
     * @var array List of built-in types that should not be treated as classes
     */
    private array $builtInTypes = [
        'string',
        'int',
        'integer',
        'float',
        'double',
        'bool',
        'boolean',
        'void',
        'array',
        'object',
        'resource',
        'null',
        'mixed',
        'callable',
        'iterable',
        'byte',
        'short',
        'long',
        'char',
        'uuid',
        'datetime',
        'date',
        'time',
        'k',
        'v',
        't'
    ];

    /**
     * Parse UML content into a class diagram model
     *
     * @param string $content The UML content to parse
     * @return ClassDiagram The parsed class diagram
     * @throws ParserException If parsing fails
     */
    public function parse(string $content): ClassDiagram
    {
        $this->initialize($content);
        $this->validateStartAndEnd();

        // Parse the diagram content
        while ($this->currentLine < count($this->lines)) {
            $line = trim($this->lines[$this->currentLine]);

            // Skip empty lines and comments
            if (empty($line) || $this->isComment($line) || $this->isDirective($line)) {
                $this->currentLine++;
                continue;
            }

            if (strpos($line, 'title ') === 0) {
                $this->parseTitle($line);
            } elseif (preg_match('/^(class|abstract\s+class|interface|enum)\s+(\w+)/', $line, $matches)) {
                $this->parseClassElement($matches[1], $matches[2]);
            } elseif ($this->isRelationship($line)) {
                $this->relationships[] = $line;
            }

            $this->currentLine++;
        }

        // Process relationships
        $this->processRelationships();

        return $this->diagram;
    }

    /**
     * Validate UML syntax without fully parsing it
     *
     * @param string $content The UML content to validate
     * @return bool True if the syntax is valid
     */
    public function validate(string $content): bool
    {
        $lines = explode("\n", $content);

        // Check for @startuml and @enduml
        $hasStart = false;
        $hasEnd = false;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '@startuml') {
                $hasStart = true;
            } elseif ($line === '@enduml') {
                $hasEnd = true;
            }
        }

        return $hasStart && $hasEnd;
    }

    /**
     * Extract basic metadata from UML content
     *
     * @param string $content The UML content to analyze
     * @return array Metadata about the content
     */
    public function extractMetadata(string $content): array
    {
        $metadata = [
            'classes' => 0,
            'interfaces' => 0,
            'enums' => 0,
            'relationships' => 0,
            'title' => null
        ];

        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            if (strpos($line, 'title ') === 0) {
                // Extract title, handling quoted titles
                if (preg_match('/^title\s+"([^"]+)"/', $line, $matches)) {
                    $metadata['title'] = $matches[1];
                } elseif (preg_match('/^title\s+(.+)$/', $line, $matches)) {
                    $metadata['title'] = $matches[1];
                }
            } elseif (preg_match('/^class\s+\w+/', $line)) {
                $metadata['classes']++;
            } elseif (preg_match('/^abstract\s+class\s+\w+/', $line)) {
                $metadata['classes']++; // Count abstract classes as classes
            } elseif (preg_match('/^interface\s+\w+/', $line)) {
                $metadata['interfaces']++;
            } elseif (preg_match('/^enum\s+\w+/', $line)) {
                $metadata['enums']++;
            } elseif ($this->isRelationshipLine($line)) {
                $metadata['relationships']++;
            }
        }

        $metadata['elements'] = $metadata['classes'] + $metadata['interfaces'] + $metadata['enums'];

        return $metadata;
    }

    /**
     * Initialize the parser with content
     *
     * @param string $content The UML content to parse
     */
    private function initialize(string $content): void
    {
        $this->content = $content;
        $this->lines = explode("\n", $content);
        $this->currentLine = 0;
        $this->diagram = new ClassDiagram();
        $this->relationships = [];
    }

    /**
     * Validates that the PlantUML content starts with @startuml and ends with @enduml
     *
     * @throws ParserException If the content doesn't have proper start/end tags
     */
    private function validateStartAndEnd(): void
    {
        if (count($this->lines) < 2) {
            throw new ParserException('PlantUML content is too short', ['line' => 1]);
        }

        $firstLine = trim($this->lines[0]);
        $lastLine = trim($this->lines[count($this->lines) - 1]);

        if ($firstLine !== '@startuml') {
            throw new ParserException('PlantUML content must start with @startuml', ['line' => 1]);
        }

        if ($lastLine !== '@enduml') {
            throw new ParserException('PlantUML content must end with @enduml', ['line' => count($this->lines)]);
        }

        // Skip the @startuml line
        $this->currentLine++;
    }

    /**
     * Checks if a line is a comment
     *
     * @param string $line The line to check
     * @return bool True if the line is a comment
     */
    private function isComment(string $line): bool
    {
        return strpos(trim($line), "'") === 0 || strpos(trim($line), "//") === 0;
    }

    /**
     * Checks if a line is a PlantUML directive
     *
     * @param string $line The line to check
     * @return bool True if the line is a directive
     */
    private function isDirective(string $line): bool
    {
        return strpos(trim($line), '!') === 0;
    }

    /**
     * Parse the title from a line
     *
     * @param string $line The line containing the title
     */
    private function parseTitle(string $line): void
    {
        // Extract title, handling quoted titles
        if (preg_match('/^title\s+"([^"]+)"/', $line, $matches)) {
            $this->diagram->setTitle($matches[1]);
        } elseif (preg_match('/^title\s+(.+)$/', $line, $matches)) {
            $this->diagram->setTitle($matches[1]);
        }
    }

    /**
     * Parse a class, interface, or enum definition
     *
     * @param string $type The type (class, abstract class, interface, enum)
     * @param string $name The name of the element
     */
    private function parseClassElement(string $type, string $name): void
    {
        $elementType = match ($type) {
            'abstract class' => 'abstract',
            'class' => 'class',
            'interface' => 'interface',
            'enum' => 'enum',
            default => 'class'
        };

        $classElement = new ClassElement($name, $elementType);

        // Check for stereotypes
        $line = $this->lines[$this->currentLine];
        if (preg_match('/<<(.+)>>/', $line, $matches)) {
            $stereotypes = explode(',', $matches[1]);
            $classElement->setStereotypes(array_map('trim', $stereotypes));
        }

        // Check for extends/implements
        if (preg_match('/extends\s+(\w+)/', $line, $matches)) {
            $classElement->setExtends($matches[1]);
        }

        if (preg_match('/implements\s+([^{]+)/', $line, $matches)) {
            $implementsStr = trim($matches[1]);
            $implements = array_map('trim', explode(',', $implementsStr));
            $classElement->setImplements($implements);
        }

        // Check for generic type parameters
        if (preg_match('/<([^>]+)>/', $line, $matches)) {
            $typeParams = array_map('trim', explode(',', $matches[1]));
            $classElement->setTypeParameters($typeParams);
        }

        // Check if there's a class body
        if (strpos($line, '{') !== false) {
            $this->currentLine++; // Move to the next line
            $this->parseClassBody($classElement);
        }

        // Add the class to the diagram
        $this->diagram->addClass($classElement);
    }

    /**
     * Parse a class body (attributes and methods)
     *
     * @param ClassElement $classElement The class element to add members to
     */
    private function parseClassBody(ClassElement $classElement): void
    {
        while ($this->currentLine < count($this->lines)) {
            $line = trim($this->lines[$this->currentLine]);

            // Skip comments and empty lines
            if (empty($line) || $this->isComment($line)) {
                $this->currentLine++;
                continue;
            }

            // End of class body
            if ($line === '}') {
                return;
            }

            // Detect if it's a method or an attribute
            if (strpos($line, '(') !== false && strpos($line, ')') !== false) {
                $this->parseMethod($line, $classElement);
            } else {
                // Check if this is an enum with default value
                if ($classElement->getType() === 'enum' && strpos($line, '=') !== false) {
                    $this->parseEnumWithValue($line, $classElement);
                } else {
                    $this->parseAttribute($line, $classElement);
                }
            }

            $this->currentLine++;
        }
    }

    /**
     * Parse an enum value with default value
     *
     * @param string $line The line containing the enum value
     * @param ClassElement $classElement The enum to add the value to
     */
    private function parseEnumWithValue(string $line, ClassElement $classElement): void
    {
        // Pattern: ENUM_VALUE = value
        if (preg_match('/^(\w+)\s*=\s*(.+)$/', $line, $matches)) {
            $name = $matches[1];
            $value = trim($matches[2]);

            $attribute = Attribute::fromParsed($name, 'public');
            $attribute->setDefaultValue($value);

            $classElement->addAttribute($attribute);
        }
    }

    /**
     * Parse an attribute definition
     *
     * @param string $line The line containing the attribute
     * @param ClassElement $classElement The class to add the attribute to
     */
    private function parseAttribute(string $line, ClassElement $classElement): void
    {
        // Skip section separator lines
        if (preg_match('/^[-_.=]{2,}$/', $line)) {
            return;
        }

        // Visibility pattern: +, -, #, or ~ followed by name: type (with potential generics)
        if (preg_match('/^([+\-#~])\s*(\w+)(?:\s*:\s*(.+))?$/', $line, $matches)) {
            $visibility = $this->mapVisibilitySymbol($matches[1]);
            $name = $matches[2];
            $type = isset($matches[3]) ? trim($matches[3]) : null;

            $attribute = Attribute::fromParsed($name, $visibility, $type);

            // Check for static modifier
            if (strpos($line, '{static}') !== false) {
                $attribute->setStatic(true);
            }

            $classElement->addAttribute($attribute);
        }
        // No visibility specified
        elseif (preg_match('/^(\w+)(?:\s*:\s*(.+))?$/', $line, $matches)) {
            $name = $matches[1];
            $type = isset($matches[2]) ? trim($matches[2]) : null;

            $attribute = Attribute::fromParsed($name, 'public', $type);

            // Check for static modifier
            if (strpos($line, '{static}') !== false) {
                $attribute->setStatic(true);
            }

            $classElement->addAttribute($attribute);
        }
    }

    /**
     * Parse a method definition
     *
     * @param string $line The line containing the method
     * @param ClassElement $classElement The class to add the method to
     */
    private function parseMethod(string $line, ClassElement $classElement): void
    {
        // Visibility pattern: +, -, #, or ~ followed by name(params): returnType
        if (preg_match('/^([+\-#~])\s*(\w+)\s*\((.*)\)(?:\s*:\s*(.+))?$/', $line, $matches)) {
            $visibility = $this->mapVisibilitySymbol($matches[1]);
            $name = $matches[2];
            $paramsStr = trim($matches[3]);
            $returnType = isset($matches[4]) ? trim($matches[4]) : null;

            $method = Method::fromParsed($name, $visibility, $returnType);

            // Parse parameters with improved generic handling
            $this->parseParameters($paramsStr, $method);

            // Check for static/abstract modifiers
            if (strpos($line, '{static}') !== false) {
                $method->setStatic(true);
            }

            if (strpos($line, '{abstract}') !== false) {
                $method->setAbstract(true);
            }

            $classElement->addMethod($method);
        }
        // No visibility specified
        elseif (preg_match('/^(\w+)\s*\((.*)\)(?:\s*:\s*(.+))?$/', $line, $matches)) {
            $name = $matches[1];
            $paramsStr = trim($matches[2]);
            $returnType = isset($matches[3]) ? trim($matches[3]) : null;

            $method = Method::fromParsed($name, 'public', $returnType);

            // Parse parameters with improved generic handling
            $this->parseParameters($paramsStr, $method);

            // Check for static/abstract modifiers
            if (strpos($line, '{static}') !== false) {
                $method->setStatic(true);
            }

            if (strpos($line, '{abstract}') !== false) {
                $method->setAbstract(true);
            }

            $classElement->addMethod($method);
        }
    }

    /**
     * Parse method parameters
     *
     * @param string $paramsStr The parameters string
     * @param Method $method The method to add parameters to
     */
    private function parseParameters(string $paramsStr, Method $method): void
    {
        if (empty($paramsStr)) {
            return;
        }

        $params = $this->splitParameters($paramsStr);

        foreach ($params as $paramStr) {
            if (preg_match('/^(\w+)(?:\s*:\s*(.+))?$/', $paramStr, $matches)) {
                $name = $matches[1];
                $type = isset($matches[2]) ? trim($matches[2]) : null;

                $parameter = Parameter::fromParsed($name, $type);
                $method->addParameter($parameter);
            }
        }
    }

    /**
     * Split parameters string into individual parameter strings
     * This handles nested generics correctly
     *
     * @param string $paramsStr The parameters string
     * @return array The individual parameter strings
     */
    private function splitParameters(string $paramsStr): array
    {
        $result = [];
        $current = '';
        $depth = 0;

        for ($i = 0; $i < strlen($paramsStr); $i++) {
            $char = $paramsStr[$i];

            if ($char === '<') {
                $depth++;
                $current .= $char;
            } elseif ($char === '>') {
                $depth--;
                $current .= $char;
            } elseif ($char === ',' && $depth === 0) {
                $result[] = $current;
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $result[] = $current;
        }

        return array_map('trim', $result);
    }

    /**
     * Check if a line represents a relationship
     *
     * @param string $line The line to check
     * @return bool True if the line is a relationship
     */
    private function isRelationship(string $line): bool
    {
        return $this->isRelationshipLine($line);
    }

    /**
     * Process relationships
     */
    private function processRelationships(): void
    {
        foreach ($this->relationships as $line) {
            $this->parseRelationship($line);
        }
    }

    /**
     * Parse a relationship line
     *
     * @param string $line The relationship line
     */
    private function parseRelationship(string $line): void
    {
        if (strpos($line, '<-->') !== false) {
            $this->parseBidirectionalRelationship($line);
            return;
        }

        // Try to match different relationship patterns

        // Pattern 1: A "srcMult" -- "tgtMult" B : label
        if (preg_match('/^(\w+)\s+"([^"]*?)"\s+([.\-|<>*o]{2,})\s+"([^"]*?)"\s+(\w+)(?:\s*:\s*(.+))?$/i', $line, $matches)) {
            $source = $matches[1];
            $target = $matches[5];
            $notation = $matches[3];
            $label = $matches[6] ?? null;
            $sourceMult = $matches[2];
            $targetMult = $matches[4];

            $this->createRelationship($source, $target, $notation, $label, $sourceMult, $targetMult);
            return;
        }

        // Pattern 2: A "srcMult" -- B : label
        if (preg_match('/^(\w+)\s+"([^"]*?)"\s+([.\-|<>*o]{2,})\s+(\w+)(?:\s*:\s*(.+))?$/i', $line, $matches)) {
            $source = $matches[1];
            $target = $matches[4];
            $notation = $matches[3];
            $label = $matches[5] ?? null;
            $sourceMult = $matches[2];

            $this->createRelationship($source, $target, $notation, $label, $sourceMult, null);
            return;
        }

        // Pattern 3: A -- "tgtMult" B : label
        if (preg_match('/^(\w+)\s+([.\-|<>*o]{2,})\s+"([^"]*?)"\s+(\w+)(?:\s*:\s*(.+))?$/i', $line, $matches)) {
            $source = $matches[1];
            $target = $matches[4];
            $notation = $matches[2];
            $label = $matches[5] ?? null;
            $targetMult = $matches[3];

            $this->createRelationship($source, $target, $notation, $label, null, $targetMult);
            return;
        }

        // Pattern 4: A -- B : label
        if (preg_match('/^(\w+)\s+([.\-|<>*o]{2,})\s+(\w+)(?:\s*:\s*(.+))?$/i', $line, $matches)) {
            $source = $matches[1];
            $target = $matches[3];
            $notation = $matches[2];
            $label = $matches[4] ?? null;

            $this->createRelationship($source, $target, $notation, $label, null, null);
            return;
        }
    }

    /**
     * Parse a bidirectional relationship
     *
     * @param string $line The bidirectional relationship line
     */
    private function parseBidirectionalRelationship(string $line): void
    {
        // Match bidirectional pattern: A <--> B : label
        if (preg_match('/^(\w+)\s+<-->\s+(\w+)(?:\s*:\s*(.+))?$/', $line, $matches)) {
            $source = $matches[1];
            $target = $matches[2];
            $label = isset($matches[3]) ? trim($matches[3]) : null;

            // Create two relationships (one in each direction)
            $forwardLabel = $label;
            $backwardLabel = $label ? "reverse_$label" : null;

            // Forward relationship
            $forwardRelationship = Relationship::fromParsed(
                $source,
                $target,
                'association'
            );

            if ($forwardLabel) {
                $forwardRelationship->setLabel($forwardLabel);
            }

            // Backward relationship
            $backwardRelationship = Relationship::fromParsed(
                $target,
                $source,
                'association'
            );

            if ($backwardLabel) {
                $backwardRelationship->setLabel($backwardLabel);
            }

            // Add both relationships to the diagram
            $this->diagram->addRelationship($forwardRelationship);
            $this->diagram->addRelationship($backwardRelationship);

            // Ensure both classes exist
            $this->ensureClassExists($source);
            $this->ensureClassExists($target);
        }
    }   

    /**
     * Create and add a relationship to the diagram
     *
     * @param string $source Source class
     * @param string $target Target class
     * @param string $notation Relationship notation
     * @param string|null $label Relationship label
     * @param string|null $sourceMultiplicity Source multiplicity
     * @param string|null $targetMultiplicity Target multiplicity
     */
    private function createRelationship(
        string $source,
        string $target,
        string $notation,
        ?string $label,
        ?string $sourceMultiplicity,
        ?string $targetMultiplicity
    ): void {
        // Create the relationship
        $relationship = Relationship::fromParsed(
            $source,
            $target,
            $this->mapRelationshipNotation($notation)
        );

        if ($label) {
            $relationship->setLabel(trim($label));
        }

        if ($sourceMultiplicity) {
            $relationship->setSourceMultiplicity($sourceMultiplicity);
        }

        if ($targetMultiplicity) {
            $relationship->setTargetMultiplicity($targetMultiplicity);
        }

        // Add the relationship to the diagram
        $this->diagram->addRelationship($relationship);

        // Ensure both classes exist in the diagram
        $this->ensureClassExists($source);
        $this->ensureClassExists($target);
    }

    /**
     * Ensure a class exists in the diagram
     *
     * @param string $className The class name
     */
    private function ensureClassExists(string $className): void
    {
        // Skip built-in types
        if (in_array(strtolower($className), $this->builtInTypes)) {
            return;
        }

        // Check if the class already exists
        if (!$this->diagram->hasClass($className)) {
            // Create a simple class
            $classElement = new ClassElement($className, 'class');
            $this->diagram->addClass($classElement);
        }
    }

    /**
     * Map PlantUML visibility symbols to strings
     *
     * @param string $symbol The symbol to map
     * @return string The visibility string
     */
    private function mapVisibilitySymbol(string $symbol): string
    {
        return match ($symbol) {
            '+' => 'public',
            '-' => 'private',
            '#' => 'protected',
            '~' => 'package',
            default => 'public',
        };
    }

    /**
     * Map PlantUML relationship notation to relationship type
     *
     * @param string $notation The notation to map
     * @return string The relationship type
     */
    private function mapRelationshipNotation(string $notation): string
    {
        // Inheritance: A <|-- B
        if (strpos($notation, '<|--') !== false || strpos($notation, '--|>') !== false) {
            return 'inheritance';
        }

        // Implementation: A <|.. B
        if (strpos($notation, '<|..') !== false || strpos($notation, '..|>') !== false) {
            return 'implementation';
        }

        // Composition: A *-- B
        if (strpos($notation, '*--') !== false || strpos($notation, '--*') !== false) {
            return 'composition';
        }

        // Aggregation: A o-- B
        if (strpos($notation, 'o--') !== false || strpos($notation, '--o') !== false) {
            return 'aggregation';
        }

        // Dependency: A ..> B
        if (strpos($notation, '..>') !== false || strpos($notation, '<..') !== false) {
            return 'dependency';
        }

        // Association (default)
        return 'association';
    }

    /**
     * Check if a line represents a relationship
     *
     * @param string $line The line to check
     * @return bool True if the line represents a relationship
     */
    private function isRelationshipLine(string $line): bool
    {
        // Match patterns for relationships
        $patterns = [
            // A -- B (basic association)
            '/\w+\s+--+\s+\w+/',
            // A --> B (directed association)
            '/\w+\s+--+>\s+\w+/',
            // A <|-- B (inheritance/generalization)
            '/\w+\s+<\|--+\s+\w+/',
            // A <|.. B (implementation/realization)
            '/\w+\s+<\|\.\.+\s+\w+/',
            // A *-- B (composition)
            '/\w+\s+\*--+\s+\w+/',
            // A o-- B (aggregation)
            '/\w+\s+o--+\s+\w+/',
            // A ..> B (dependency)
            '/\w+\s+\.\.+>\s+\w+/',
            // With multiplicities (e.g. A "1" -- "*" B)
            '/\w+\s+"[^"]*"\s+[.\-|<>*o]{2,}\s+"[^"]*"\s+\w+/',
            '/\w+\s+"[^"]*"\s+[.\-|<>*o]{2,}\s+\w+/',
            '/\w+\s+[.\-|<>*o]{2,}\s+"[^"]*"\s+\w+/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }
}
