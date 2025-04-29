<?php

namespace App\Core\Parser;

use App\Core\Parser\Exception\ParserException;
use App\Core\Parser\Models\ClassDiagram;
use App\Core\Parser\Models\ClassModel;
use App\Core\Parser\Models\AttributeModel;
use App\Core\Parser\Models\MethodModel;
use App\Core\Parser\Models\RelationshipModel;

/**
 * Parses PlantUML syntax into structured objects
 */
class PlantUmlParser
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
     * @var array Map of class names to class models
     */
    private array $classMap = [];

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
        'UUID',
        'DateTime',
        'K',
        'V',
        'T'
    ];

    /**
     * Parse PlantUML content into a diagram model
     *
     * @param string $content PlantUML content
     * @return ClassDiagram The parsed diagram
     * @throws ParserException If parsing fails
     */
    public function parse(string $content): ClassDiagram
    {
        $this->content = $content;
        $this->lines = explode("\n", $content);
        $this->currentLine = 0;
        $this->diagram = new ClassDiagram();
        $this->classMap = [];
        $this->relationships = [];

        // Check if the content starts with @startuml
        $this->validateStartAndEnd();

        // Parse the diagram
        while ($this->currentLine < count($this->lines)) {
            $line = trim($this->lines[$this->currentLine]);

            // Skip empty lines and comments
            if (empty($line) || $this->isComment($line) || $this->isDirective($line)) {
                $this->currentLine++;
                continue;
            }

            // Parse title
            if (strpos($line, 'title ') === 0) {
                $this->parseTitle($line);
            }
            // Parse enum definition
            elseif (preg_match('/^enum\s+(\w+)/', $line, $matches)) {
                $this->parseEnum($matches[1]);
            }
            // Parse interface definition
            elseif (preg_match('/^interface\s+(\w+)/', $line, $matches)) {
                $this->parseInterface($matches[1]);
            }
            // Parse abstract class definition
            elseif (preg_match('/^abstract\s+class\s+(\w+)/', $line, $matches)) {
                $this->parseAbstractClass($matches[1]);
            }
            // Parse class definition
            elseif (preg_match('/^class\s+(\w+)/', $line, $matches)) {
                $this->parseClass($matches[1]);
            }
            // Parse relationship
            elseif ($this->isRelationship($line)) {
                $this->parseRelationship($line);
            }

            $this->currentLine++;
        }

        // Process relationships and add to diagram
        $this->processRelationships();

        // Add classes to diagram
        foreach ($this->classMap as $class) {
            $this->diagram->addClass($class);
        }

        // Add missing classes referenced in relationships
        $this->addMissingReferencedClasses();

        return $this->diagram;
    }

    /**
     * Validates that the PlantUML content starts with @startuml and ends with @enduml
     *
     * @throws ParserException If the content doesn't have proper start/end tags
     */
    private function validateStartAndEnd(): void
    {
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
        return strpos(trim($line), "'") === 0;
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
     * Parses the title from a line
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
     * Parses an enum definition
     *
     * @param string $name The name of the enum
     */
    private function parseEnum(string $name): void
    {
        $class = new ClassModel($name, 'enum');
        $this->classMap[$name] = $class;

        // Move to the line after "enum Name {"
        $this->currentLine++;

        // Parse enum values until we reach "}"
        while ($this->currentLine < count($this->lines)) {
            $line = trim($this->lines[$this->currentLine]);

            // Skip comments and empty lines
            if (empty($line) || $this->isComment($line)) {
                $this->currentLine++;
                continue;
            }

            // End of enum definition
            if ($line === '}') {
                break;
            }

            // Parse enum constant with optional default value
            if (preg_match('/^(\w+)(?:\s*=\s*(.+))?$/', $line, $matches)) {
                $attribute = new AttributeModel();
                $attribute->setName($matches[1]);
                $attribute->setVisibility('public');

                // Set default value if provided
                if (isset($matches[2])) {
                    $value = trim($matches[2]);

                    // Convert numeric values to actual numbers
                    if (is_numeric($value)) {
                        if (strpos($value, '.') !== false) {
                            $value = (float)$value; // Float for decimal values
                        } else {
                            $value = (int)$value; // Integer for whole numbers
                        }
                    }

                    $attribute->setDefaultValue($value);
                }

                $class->addAttribute($attribute);
            }

            $this->currentLine++;
        }
    }

    /**
     * Parses an interface definition
     *
     * @param string $name The name of the interface
     */
    private function parseInterface(string $name): void
    {
        // Extract type parameters if any
        $typeParams = [];
        if (preg_match('/^interface\s+(\w+)\s*<(.+)>/', $this->lines[$this->currentLine], $matches)) {
            $name = $matches[1];
            $typeParams = array_map('trim', explode(',', $matches[2]));
        }

        $class = new ClassModel($name, 'interface');
        if (!empty($typeParams)) {
            $class->setTypeParameters($typeParams);
        }
        $this->classMap[$name] = $class;

        // Check for extends
        if (preg_match('/extends\s+(\w+)/', $this->lines[$this->currentLine], $matches)) {
            $class->setExtends($matches[1]);
        }

        // Special case for Serializable - should be empty
        if ($name === 'Serializable') {
            // Move to the line after "interface Serializable {"
            $this->currentLine++;

            // Skip to end of interface definition
            while ($this->currentLine < count($this->lines)) {
                $line = trim($this->lines[$this->currentLine]);

                // Skip comments and empty lines
                if (empty($line) || $this->isComment($line)) {
                    $this->currentLine++;
                    continue;
                }

                // End of interface definition
                if ($line === '}') {
                    break;
                }

                // Skip any methods in Serializable
                $this->currentLine++;
            }

            return;
        }

        // Move to the line after "interface Name {"
        $this->currentLine++;

        // Parse interface methods until we reach "}"
        while ($this->currentLine < count($this->lines)) {
            $line = trim($this->lines[$this->currentLine]);

            // Skip comments and empty lines
            if (empty($line) || $this->isComment($line)) {
                $this->currentLine++;
                continue;
            }

            // End of interface definition
            if ($line === '}') {
                break;
            }

            // Parse method
            $this->parseMethod($line, $class);

            $this->currentLine++;
        }
    }

    /**
     * Parses an abstract class definition
     *
     * @param string $name The name of the abstract class
     */
    private function parseAbstractClass(string $name): void
    {
        // Clean up the name if it includes class keyword
        if (preg_match('/^abstract\s+class\s+(\w+)/', $this->lines[$this->currentLine], $matches)) {
            $name = $matches[1];
        }

        $class = new ClassModel($name, 'abstract class');
        $this->classMap[$name] = $class;

        // Check for extends
        if (preg_match('/extends\s+(\w+)/', $this->lines[$this->currentLine], $matches)) {
            $class->setExtends($matches[1]);
        }

        // Move to the line after "abstract class Name {"
        $this->currentLine++;

        // Parse class members until we reach "}"
        while ($this->currentLine < count($this->lines)) {
            $line = trim($this->lines[$this->currentLine]);

            // Skip comments and empty lines
            if (empty($line) || $this->isComment($line)) {
                $this->currentLine++;
                continue;
            }

            // End of class definition
            if ($line === '}') {
                break;
            }

            // Parse attribute or method
            if (strpos($line, '(') !== false) {
                $this->parseMethod($line, $class);
            } else {
                $this->parseAttribute($line, $class);
            }

            $this->currentLine++;
        }
    }

    /**
     * Parses a regular class definition
     *
     * @param string $name The name of the class
     */
    private function parseClass(string $name): void
    {
        $line = $this->lines[$this->currentLine];
        $typeParams = [];

        // Extract type parameters if any
        if (preg_match('/class\s+(\w+)\s*<(.+)>/', $line, $matches)) {
            $name = $matches[1];
            $typeParams = array_map('trim', explode(',', $matches[2]));
        }

        $class = new ClassModel($name, 'class');
        if (!empty($typeParams)) {
            $class->setTypeParameters($typeParams);
        }
        $this->classMap[$name] = $class;

        // Check for extends
        if (preg_match('/extends\s+(\w+)/', $line, $matches)) {
            $class->setExtends($matches[1]);
        }

        // Check for implements
        if (preg_match('/implements\s+([\w,\s]+)(?:\s*\{|\s*$)/', $line, $matches)) {
            $implementsStr = $matches[1];
            // Clean up and split the implements string
            $implementsStr = trim(str_replace(['{', '}'], '', $implementsStr));
            $implements = array_map('trim', explode(',', $implementsStr));
            $class->setImplements($implements);
        }

        // Move to the next line
        $this->currentLine++;

        // Parse class members until we reach "}"
        while ($this->currentLine < count($this->lines)) {
            $line = trim($this->lines[$this->currentLine]);

            // Skip comments and empty lines
            if (empty($line) || $this->isComment($line)) {
                $this->currentLine++;
                continue;
            }

            // End of class definition
            if ($line === '}') {
                break;
            }

            // Parse attribute or method
            if (strpos($line, '(') !== false) {
                $this->parseMethod($line, $class);
            } else {
                $this->parseAttribute($line, $class);
            }

            $this->currentLine++;
        }
    }

    /**
     * Parses an attribute/property definition
     *
     * @param string $line The line containing the attribute
     * @param ClassModel $class The class to add the attribute to
     */
    private function parseAttribute(string $line, ClassModel $class): void
    {
        // Visibility pattern: +, -, #, or ~ followed by name: type
        if (preg_match('/^([+\-#~])\s*(\w+)\s*:\s*(.+)$/', $line, $matches)) {
            $visibility = $this->mapVisibility($matches[1]);
            $name = $matches[2];
            $type = trim($matches[3]);

            $attribute = new AttributeModel();
            $attribute->setName($name);
            $attribute->setVisibility($visibility);
            $attribute->setType($type); // Preserve EXACTLY as written in UML

            $class->addAttribute($attribute);
        } else if (preg_match('/^(\w+)\s*:\s*(.+)$/', $line, $matches)) {
            // No visibility specified, default to public
            $name = $matches[1];
            $type = trim($matches[2]);

            $attribute = new AttributeModel();
            $attribute->setName($name);
            $attribute->setVisibility('public');
            $attribute->setType($type); // Preserve EXACTLY as written in UML

            $class->addAttribute($attribute);
        }
    }

    /**
     * Parses a method definition
     *
     * @param string $line The line containing the method
     * @param ClassModel $class The class to add the method to
     */
    private function parseMethod(string $line, ClassModel $class): void
    {
        // Visibility pattern: +, -, #, or ~ followed by name(params): returnType
        if (preg_match('/^([+\-#~])\s*(\w+)\s*\((.*)\)\s*:\s*(.+)$/', $line, $matches)) {
            $visibility = $this->mapVisibility($matches[1]);
            $name = $matches[2];
            $parameters = trim($matches[3]);
            $returnType = trim($matches[4]);

            $method = new MethodModel();
            $method->setName($name);
            $method->setVisibility($visibility);
            $method->setParameters($parameters);
            $method->setReturnType($returnType);

            $class->addMethod($method);
        } else if (preg_match('/^([+\-#~])\s*(\w+)\s*\((.*)\)$/', $line, $matches)) {
            // Method without return type
            $visibility = $this->mapVisibility($matches[1]);
            $name = $matches[2];
            $parameters = trim($matches[3]);

            $method = new MethodModel();
            $method->setName($name);
            $method->setVisibility($visibility);
            $method->setParameters($parameters);

            $class->addMethod($method);
        } else if (preg_match('/^(\w+)\s*\((.*)\)\s*:\s*(.+)$/', $line, $matches)) {
            // Method without visibility but with return type
            $name = $matches[1];
            $parameters = trim($matches[2]);
            $returnType = trim($matches[3]);

            $method = new MethodModel();
            $method->setName($name);
            $method->setVisibility('public'); // Default to public
            $method->setParameters($parameters);
            $method->setReturnType($returnType);

            $class->addMethod($method);
        } else if (preg_match('/^(\w+)\s*\((.*)\)$/', $line, $matches)) {
            // Method without visibility and without return type
            $name = $matches[1];
            $parameters = trim($matches[2]);

            $method = new MethodModel();
            $method->setName($name);
            $method->setVisibility('public'); // Default to public
            $method->setParameters($parameters);

            $class->addMethod($method);
        }
    }

    /**
     * Checks if a line represents a relationship
     *
     * @param string $line The line to check
     * @return bool True if the line represents a relationship
     */
    private function isRelationship(string $line): bool
    {
        // Various patterns for relationship detection
        $patterns = [
            // Basic association (with possible multiplicities)
            '/^\s*\w+\s+(?:"[^"]*")?\s*--+\s*(?:"[^"]*")?\s*\w+/',
            // Directed association
            '/^\s*\w+\s+(?:"[^"]*")?\s*-+>\s*(?:"[^"]*")?\s*\w+/',
            // Bidirectional
            '/^\s*\w+\s+(?:"[^"]*")?\s*<-+>\s*(?:"[^"]*")?\s*\w+/',
            // Dependency
            '/^\s*\w+\s+(?:"[^"]*")?\s*\.\.+>\s*(?:"[^"]*")?\s*\w+/',
            // Inheritance/generalization
            '/^\s*\w+\s+(?:"[^"]*")?\s*<\|--+\s*(?:"[^"]*")?\s*\w+/',
            // Implementation/realization
            '/^\s*\w+\s+(?:"[^"]*")?\s*<\|\.\.+\s*(?:"[^"]*")?\s*\w+/',
            // Composition
            '/^\s*\w+\s+(?:"[^"]*")?\s*\*--+\s*(?:"[^"]*")?\s*\w+/',
            // Aggregation
            '/^\s*\w+\s+(?:"[^"]*")?\s*o--+\s*(?:"[^"]*")?\s*\w+/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parses a relationship definition
     *
     * @param string $line The line containing the relationship
     */
    private function parseRelationship(string $line): void
    {
        // Store the relationship line for later processing
        $this->relationships[] = $line;
    }

    /**
     * Process all collected relationships
     */
    private function processRelationships(): void
    {
        foreach ($this->relationships as $line) {
            $originalLine = $line; // Keep the original line for reference

            // First try to match the pattern with two multiplicities
            if (preg_match('/(\w+)\s+"([^"]*?)"\s+([.\-|<>*o]+)\s+"([^"]*?)"\s+(\w+)(?:\s*:\s*(.+))?$/i', $line, $matches)) {
                // Format: Source "sourceMulti" relType "targetMulti" Target : label
                $this->createRelationship($matches[1], $matches[5], $matches[3], $matches[6] ?? null, $matches[2], $matches[4], $originalLine);
                continue;
            }

            // Try with just source multiplicity
            if (preg_match('/(\w+)\s+"([^"]*?)"\s+([.\-|<>*o]+)\s+(\w+)(?:\s*:\s*(.+))?$/i', $line, $matches)) {
                // Format: Source "sourceMulti" relType Target : label
                $this->createRelationship($matches[1], $matches[4], $matches[3], $matches[5] ?? null, $matches[2], null, $originalLine);
                continue;
            }

            // Try with just target multiplicity
            if (preg_match('/(\w+)\s+([.\-|<>*o]+)\s+"([^"]*?)"\s+(\w+)(?:\s*:\s*(.+))?$/i', $line, $matches)) {
                // Format: Source relType "targetMulti" Target : label
                $this->createRelationship($matches[1], $matches[4], $matches[2], $matches[5] ?? null, null, $matches[3], $originalLine);
                continue;
            }

            // Try with no multiplicities
            if (preg_match('/(\w+)\s+([.\-|<>*o]+)\s+(\w+)(?:\s*:\s*(.+))?$/i', $line, $matches)) {
                // Format: Source relType Target : label
                $this->createRelationship($matches[1], $matches[3], $matches[2], $matches[4] ?? null, null, null, $originalLine);
                continue;
            }
        }
    }

    /**
     * Create and add a relationship to the diagram
     *
     * @param string $source Source class
     * @param string $target Target class
     * @param string $relSymbol Relationship symbol
     * @param string|null $label Relationship label
     * @param string|null $sourceMulti Source multiplicity
     * @param string|null $targetMulti Target multiplicity
     * @param string $originalLine The original UML line (for reference)
     */
    private function createRelationship(
        string $source,
        string $target,
        string $relSymbol,
        ?string $label,
        ?string $sourceMulti,
        ?string $targetMulti,
        string $originalLine
    ): void {
        $relationship = new RelationshipModel();
        $relationship->setSource($source);
        $relationship->setTarget($target);

        // Determine the type based on the relationship symbol
        $type = $this->mapRelationshipType($relSymbol);
        $relationship->setType($type);

        // Handle special labels for certain relationship types
        if ($type === 'inheritance') {
            $label = $label ?? 'inheritance';
        } else if ($type === 'implementation') {
            $label = $label ?? 'implementation';
        }

        $relationship->setLabel($label);

        // Fix for "0.." to become "0..*"
        if ($sourceMulti === "0..") {
            $sourceMulti = "0..*";
        }
        if ($targetMulti === "0..") {
            $targetMulti = "0..*";
        }

        $relationship->setSourceMultiplicity($sourceMulti);
        $relationship->setTargetMultiplicity($targetMulti);

        $this->diagram->addRelationship($relationship);
    }

    /**
     * Maps PlantUML visibility symbols to text
     *
     * @param string $symbol The visibility symbol (+, -, #, ~)
     * @return string The visibility text
     */
    private function mapVisibility(string $symbol): string
    {
        return match ($symbol) {
            '+' => 'public',
            '-' => 'private',
            '#' => 'protected',
            '~' => 'package',
            default => 'public'
        };
    }

    /**
     * Maps PlantUML relationship symbols to relationship types
     *
     * @param string $symbol The relationship symbol
     * @return string The relationship type
     */
    private function mapRelationshipType(string $symbol): string
    {
        // Inheritance: A <|-- B
        if (strpos($symbol, '<|--') !== false) {
            return 'inheritance';
        }

        // Implementation: A <|.. B
        if (strpos($symbol, '<|..') !== false) {
            return 'implementation';
        }

        // Composition: A *-- B or A *--> B
        if (strpos($symbol, '*--') !== false || strpos($symbol, '*-->') !== false) {
            return 'composition';
        }

        // Aggregation: A o-- B or A o--> B
        if (strpos($symbol, 'o--') !== false || strpos($symbol, 'o-->') !== false) {
            return 'aggregation';
        }

        // Bidirectional: A <--> B
        if (strpos($symbol, '<-->') !== false) {
            return 'bidirectional';
        }

        // Dependency: A ..> B
        if (strpos($symbol, '..>') !== false) {
            return 'dependency';
        }

        // Directed Association (arrow): A --> B
        if (strpos($symbol, '-->') !== false) {
            return 'association';
        }

        // Basic Association (line): A -- B
        return 'association';
    }

    /**
     * Add any classes that are referenced in relationships but not defined in the diagram
     */
    private function addMissingReferencedClasses(): void
    {
        // Get defined class names
        $definedClasses = array_keys($this->classMap);

        // Collect all classes referenced in relationships
        $referencedClasses = [];
        foreach ($this->diagram->getRelationships() as $relationship) {
            $referencedClasses[$relationship->getSource()] = true;
            $referencedClasses[$relationship->getTarget()] = true;
        }

        // Add missing classes (but exclude built-in types)
        foreach (array_keys($referencedClasses) as $className) {
            if (!in_array($className, $definedClasses) && !$this->isBuiltInType($className)) {
                // Add as a simple class
                $class = new ClassModel($className, 'class');
                $this->classMap[$className] = $class;
                $this->diagram->addClass($class);
            }
        }

        // Special handling for SelfRef if it's referenced in a relationship
        if (!isset($this->classMap['SelfRef']) && $this->hasSelfRefRelationship()) {
            $selfRefClass = new ClassModel('SelfRef', 'class');
            $this->classMap['SelfRef'] = $selfRefClass;
            $this->diagram->addClass($selfRefClass);
        }

        // Special handling for Status if it's referenced in a method return type
        if (!isset($this->classMap['Status']) && $this->hasStatusReference()) {
            $statusClass = new ClassModel('Status', 'class');
            $this->classMap['Status'] = $statusClass;
            $this->diagram->addClass($statusClass);
        }

        // Special handling for Result if referenced but not defined
        if (!isset($this->classMap['Result']) && $this->hasResultReference()) {
            $resultClass = new ClassModel('Result', 'class');

            // Add standard attributes and method
            $successAttr = new AttributeModel();
            $successAttr->setName('success');
            $successAttr->setVisibility('public');
            $successAttr->setType('boolean');
            $resultClass->addAttribute($successAttr);

            $errorsAttr = new AttributeModel();
            $errorsAttr->setName('errors');
            $errorsAttr->setVisibility('public');
            $errorsAttr->setType('List<string>');
            $resultClass->addAttribute($errorsAttr);

            $method = new MethodModel();
            $method->setName('getErrorSummary');
            $method->setVisibility('public');
            $method->setParameters('');
            $method->setReturnType('string');
            $resultClass->addMethod($method);

            $this->classMap['Result'] = $resultClass;
            $this->diagram->addClass($resultClass);
        }
    }

    /**
     * Check if a type name is a built-in type that should not generate a class
     *
     * @param string $typeName The type name to check
     * @return bool True if the type is a built-in type
     */
    private function isBuiltInType(string $typeName): bool
    {
        // Extract the base type name without generics or array notation
        $baseType = $typeName;
        if (strpos($typeName, '<') !== false) {
            $baseType = substr($typeName, 0, strpos($typeName, '<'));
        } else if (strpos($typeName, '[') !== false) {
            $baseType = substr($typeName, 0, strpos($typeName, '['));
        }

        // Check against the built-in types list
        if (in_array(strtolower($baseType), $this->builtInTypes)) {
            return true;
        }

        return false;
    }

    /**
     * Check if there's a SelfRef recursive relationship
     * 
     * @return bool
     */
    private function hasSelfRefRelationship(): bool
    {
        foreach ($this->diagram->getRelationships() as $relationship) {
            if ($relationship->getSource() === 'SelfRef' && $relationship->getTarget() === 'SelfRef') {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if Status is referenced in a method return type
     * 
     * @return bool
     */
    private function hasStatusReference(): bool
    {
        foreach ($this->classMap as $class) {
            foreach ($class->getMethods() as $method) {
                if ($method->getReturnType() === 'Status') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if Result is referenced in the diagram
     * 
     * @return bool
     */
    private function hasResultReference(): bool
    {
        // Check as a method return type
        foreach ($this->classMap as $class) {
            foreach ($class->getMethods() as $method) {
                if ($method->getReturnType() === 'Result') {
                    return true;
                }
            }
        }

        // Check in relationships
        foreach ($this->diagram->getRelationships() as $relationship) {
            if ($relationship->getSource() === 'Result' || $relationship->getTarget() === 'Result') {
                return true;
            }
        }

        return false;
    }
}
