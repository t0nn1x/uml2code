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

        // Fallback for diagrams with relationships but no class/interface/enum keywords
        if ($diagramType === DiagramTypeDetector::TYPE_UNKNOWN) {
            // Check for common relationship patterns
            if (preg_match('/[A-Za-z0-9_]+\s+(?:--|->|<-|<--|-->|o--|<\|--|<-\.\.|\.\.-|\.\.>|--\*|\*--)\s+[A-Za-z0-9_]+/', $plantUmlText)) {
                $diagramType = DiagramTypeDetector::TYPE_CLASS;
            }
        }

        // Parse according to diagram type
        switch ($diagramType) {
            case DiagramTypeDetector::TYPE_CLASS:
                return $this->parseClassDiagram($plantUmlText);
            case DiagramTypeDetector::TYPE_SEQUENCE:
                throw new ParserException("Sequence diagram parsing not yet implemented");
            case DiagramTypeDetector::TYPE_ACTIVITY:
            case DiagramTypeDetector::TYPE_USECASE:
            case DiagramTypeDetector::TYPE_COMPONENT:
            case DiagramTypeDetector::TYPE_STATE:
            case DiagramTypeDetector::TYPE_OBJECT:
                throw new ParserException("Unsupported diagram type: " . $diagramType);
            case DiagramTypeDetector::TYPE_UNKNOWN:
                throw new ParserException("Invalid or unknown diagram type");
            default:
                throw new ParserException("Unexpected diagram type: " . $diagramType);
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

        // Extract diagram title if present - handle titles that may contain dashes or special chars
        if (preg_match('/^title\s+(.*?)$/m', $content, $titleMatches)) {
            $diagram->setTitle(trim($titleMatches[1]));
            
            // Remove the title line completely from content to prevent misinterpretation
            $content = preg_replace('/^title\s+.*?$/m', '', $content);
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
        // Remove title line to prevent it from being detected as a class
        // Use a more comprehensive pattern that handles potential dashes in titles
        $content = preg_replace('/^title\s+.*$/m', '', $content);
        
        // Match all class definitions
        preg_match_all('/\b(class|interface|enum|abstract\s+class)\s+([A-Za-z][A-Za-z0-9_]*)/i', $content, $basicMatches, PREG_SET_ORDER);
        
        foreach ($basicMatches as $match) {
            // Create simple class with just the name
            $type = trim($match[1]);
            $name = trim($match[2]);
            
            // Skip if the name matches common title words
            if (in_array(strtolower($name), ['title', 'domain', 'diagram', 'model'])) {
                continue;
            }
            
            $class = new ClassEntity();
            $class->setName($name);
            
            // Set type
            if ($type === 'interface') {
                $class->setInterface(true);
            } elseif ($type === 'abstract class') {
                $class->setAbstract(true);
            } elseif ($type === 'enum') {
                $class->setEnum(true);
            }
            
            // Find extends and implements - only look for implements if not an interface
            if (preg_match('/\b' . preg_quote($type, '/') . '\s+' . preg_quote($name, '/') . '\s+extends\s+([A-Za-z][A-Za-z0-9_]*)/i', $content, $extendsMatch)) {
                $class->setExtends($extendsMatch[1]);
            }
            
            // Only process 'implements' for non-interface types
            if ($type !== 'interface' && preg_match('/\b' . preg_quote($type, '/') . '\s+' . preg_quote($name, '/') . '.*?implements\s+([A-Za-z0-9_,\s]+)/i', $content, $implMatch)) {
                $interfaces = explode(',', $implMatch[1]);
                foreach ($interfaces as $interface) {
                    $class->addImplements(trim($interface));
                }
            }
            
            // Try to find the class body
            $bodyPattern = '/\b' . preg_quote($type, '/') . '\s+' . preg_quote($name, '/') . '.*?\{(.*?)\}/s';
            if (preg_match($bodyPattern, $content, $bodyMatch)) {
                $body = trim($bodyMatch[1]);
                $this->parseClassBody($body, $class);
            }
            
            $diagram->addClass($class);
        }
        
        // Also check for inheritance and implementation relationships in the relationship format
        $this->updateClassTypesFromRelationships($content, $diagram);
    }

    /**
     * Update class types based on relationships
     */
    private function updateClassTypesFromRelationships(string $content, ClassDiagram $diagram): void
    {
        // Look for implementation and inheritance relationships
        preg_match_all('/([A-Za-z0-9_]+)\s*(?:--|-->|\.\.>)\s*([A-Za-z0-9_]+)\s*:\s*(implements|extends)/i', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $sourceClass = $match[1];
            $targetClass = $match[2];
            $relationType = strtolower($match[3]);

            if ($relationType === 'implements' && $diagram->hasClass($targetClass)) {
                $targetEntity = $diagram->getClass($targetClass);
                $targetEntity->setInterface(true);
            }
            if ($relationType === 'extends' && $diagram->hasClass($targetClass)) {
                $targetEntity = $diagram->getClass($targetClass);
                if ($targetEntity->isInterface()) {
                    $targetEntity->setInterface(true);
                }
            }
        }
    }

    private function processClassMatch(array $match, ClassDiagram $diagram): void
    {
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

        // Parse class body (attributes and methods) if present
        if (!empty($body)) {
            $this->parseClassBody($body, $class);
        }

        // Handle extends and implements
        if ($extends) {
            if ($extends['extends']) {
                $class->setExtends($extends['extends']);
            }
            if (!empty($extends['implements'])) {
                foreach ($extends['implements'] as $interface) {
                    $class->addImplements($interface);
                }
            }
        }

        $diagram->addClass($class);
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
        // Split into lines and clean each line
        $lines = array_map('trim', explode("\n", $body));
        $lines = array_filter($lines); // Remove empty lines

        $processedLines = [];
        $seenSignatures = [];

        foreach ($lines as $lineNum => $line) {
            // Skip if we've already processed this line
            if (in_array($line, $processedLines)) {
                continue;
            }

            // Try to match a method with parameters and return type: +login(password: string): bool
            if (preg_match('/([+\-#~])?([\w\d_]+)\((.*?)\)(?:\s*:\s*([\w\d_<>]+))?/', $line, $methodMatch)) {
                $visibility = $methodMatch[1] ?: '+';
                $methodName = $methodMatch[2];
                $parameters = trim($methodMatch[3]);
                $returnType = isset($methodMatch[4]) ? trim($methodMatch[4]) : null;

                // Clean up parameter format
                $parameters = preg_replace('/\s*:\s*/', ': ', $parameters);

                $signature = $methodName . '(' . $parameters . ')';
                if (!in_array($signature, $seenSignatures)) {
                    $class->addMethod([
                        'name' => $methodName,
                        'visibility' => $this->mapVisibilitySymbol($visibility),
                        'parameters' => $parameters,
                        'returnType' => $returnType
                    ]);
                    $seenSignatures[] = $signature;
                }
                $processedLines[] = $line;
            }
            // Try to match an attribute with explicit type: +id: int
            else if (preg_match('/([+\-#~])?([\w\d_]+)\s*:\s*([\w\d_<>[\],\s]+)/', $line, $attrMatch)) {
                $visibility = $attrMatch[1] ?: '+';
                $attributeName = $attrMatch[2];
                $type = trim($attrMatch[3]);

                if (!in_array($attributeName, $seenSignatures)) {
                    $class->addAttribute([
                        'name' => $attributeName,
                        'visibility' => $this->mapVisibilitySymbol($visibility),
                        'type' => $type
                    ]);
                    $seenSignatures[] = $attributeName;
                }
                $processedLines[] = $line;
            }
            // Try to match a Java/C#-style attribute: +String name, +int[] ids, +List<User> users
            else if (preg_match('/([+\-#~])?([A-Za-z0-9_<>\\[\\]\\s,]+)\\s+([A-Za-z0-9_]+)/', $line, $javaStyleMatch)) {
                $visibility = $javaStyleMatch[1] ?: '+';
                $type = trim($javaStyleMatch[2]);
                $attributeName = $javaStyleMatch[3];

                if (!in_array($attributeName, $seenSignatures)) {
                    $class->addAttribute([
                        'name' => $attributeName,
                        'visibility' => $this->mapVisibilitySymbol($visibility),
                        'type' => $type
                    ]);
                    $seenSignatures[] = $attributeName;
                }
                $processedLines[] = $line;
            }
            // Try to match a basic attribute with no type: +name
            else if (preg_match('/([+\-#~])?([\w\d_]+)$/', $line, $basicAttrMatch)) {
                $visibility = $basicAttrMatch[1] ?: '+';
                $attributeName = $basicAttrMatch[2];

                // Ignore if this looks like a method without parentheses (common mistake)
                if (!in_array(strtolower($attributeName), ['get', 'set', 'is', 'has', 'find', 'load', 'save', 'delete', 'update', 'create', 'remove', 'add', 'process'])) {
                    if (!in_array($attributeName, $seenSignatures)) {
                        $class->addAttribute([
                            'name' => $attributeName,
                            'visibility' => $this->mapVisibilitySymbol($visibility),
                            'type' => null
                        ]);
                        $seenSignatures[] = $attributeName;
                    }
                    $processedLines[] = $line;
                }
            } else {
                // Debug: Log unmatched lines
                error_log("No match for line: '$line'");
            }
        }
    }

    private function mapVisibilitySymbol(string $symbol): string
    {
        switch ($symbol) {
            case '+':
                return ClassEntity::VISIBILITY_PUBLIC;
            case '-':
                return ClassEntity::VISIBILITY_PRIVATE;
            case '#':
                return ClassEntity::VISIBILITY_PROTECTED;
            case '~':
                return ClassEntity::VISIBILITY_PACKAGE;
            default:
                return ClassEntity::VISIBILITY_PUBLIC;
        }
    }

    /**
     * Parse relationship definitions
     *
     * @param string $content PlantUML content
     * @param ClassDiagram $diagram The diagram to add entities to
     */
    private function parseRelationships(string $content, ClassDiagram $diagram): void
    {
        $processedKeys = [];
        
        // Get all class names for validating existing classes
        $classNames = [];
        foreach ($diagram->getClasses() as $class) {
            $classNames[] = $class->getName();
        }
        
        // Remove title line completely before processing relationships
        $content = preg_replace('/^title\s+.*$/m', '', $content);
        
        // Match relationship patterns like: User "1" --> "*" Order: places
        preg_match_all('/([A-Za-z0-9_]+)\s*(?:"([^"]*)")?\s*([-.*o<>|]+)\s*(?:"([^"]*)")?\s*([A-Za-z0-9_]+)(?:\s*:\s*(.+))?/im', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $src = $match[1];
            $srcMul = isset($match[2]) ? $match[2] : '';
            $syntax = $match[3];
            $tgtMul = isset($match[4]) ? $match[4] : '';
            $tgt = $match[5];
            $label = isset($match[6]) ? trim($match[6]) : null;
            
            // Skip if syntax isn't a valid relationship pattern
            if (!$this->isValidRelationshipSyntax($syntax)) {
                continue;
            }
            
            // Create classes if they don't exist and both sides are likely classes 
            // (avoiding primitive types like int, string, etc.)
            $isPrimitiveType = in_array(strtolower($src), ['int', 'string', 'float', 'boolean', 'array', 'void', 'double', 'object']) || 
                               in_array(strtolower($tgt), ['int', 'string', 'float', 'boolean', 'array', 'void', 'double', 'object']);
            
            // Skip words that are likely to be part of title text or metadata
            $titleWords = ['title', 'diagram', 'domain', 'model'];
            if (in_array(strtolower($src), $titleWords) || in_array(strtolower($tgt), $titleWords)) {
                continue;
            }
            
            if (!$isPrimitiveType) {
                // Skip very short one-letter entities unless they already exist (likely from title text)
                if (strlen($src) == 1 && !in_array($src, $classNames)) {
                    continue;
                }
                if (strlen($tgt) == 1 && !in_array($tgt, $classNames)) {
                    continue;
                }
                
                if (!in_array($src, $classNames)) {
                    $srcClass = new ClassEntity();
                    $srcClass->setName($src);
                    $diagram->addClass($srcClass);
                    $classNames[] = $src;
                }
                
                if (!in_array($tgt, $classNames)) {
                    $tgtClass = new ClassEntity();
                    $tgtClass->setName($tgt);
                    $diagram->addClass($tgtClass);
                    $classNames[] = $tgt;
                }
                
                $type = $this->determineRelationshipType($syntax, $label);
                $key = $this->createRelationshipKey($src, $tgt, $type, $label);
                
                if (isset($processedKeys[$key])) {
                    continue;
                }
                
                $rel = new Relationship();
                $rel->setSource($src);
                $rel->setTarget($tgt);
                $rel->setType($type);
                
                if (!empty($label)) {
                    $rel->setLabel($label);
                }
                
                if (!empty($srcMul)) {
                    $rel->setSourceMultiplicity(trim($srcMul));
                }
                
                if (!empty($tgtMul)) {
                    $rel->setTargetMultiplicity(trim($tgtMul));
                }
                
                $diagram->addRelationship($rel);
                $processedKeys[$key] = true;
            }
        }
    }
    
    /**
     * Check if the given syntax is a valid relationship pattern
     */
    private function isValidRelationshipSyntax(string $syntax): bool
    {
        $patterns = [
            // Association - include more variations
            '/^-+$/',
            '/^--+$/',
            '/^-+>$/',
            '/^->$/',
            '/^-->$/',
            '/^--->$/',
            '/^<-+$/',
            '/^<--$/',
            '/^<---$/',
            
            // Inheritance
            '/^<\|-+$/',
            '/^-+\|>$/',
            '/^<\|--$/',
            '/^--|>$/',
            
            // Implementation
            '/^<\|\.\.+$/',
            '/^\.\.+\|>$/',
            '/^<\|\.\.$/',
            '/^\.\.|>$/',
            
            // Dependency
            '/^\.\.+>$/',
            '/^<\.\.+>$/',
            '/^\.\.>$/',
            '/^<\.\.>$/',
            
            // Aggregation
            '/^o-+$/',
            '/^-+o$/',
            '/^o--$/',
            '/^--o$/',
            
            // Composition
            '/^\*-+$/',
            '/^-+\*$/',
            '/^\*--$/',
            '/^--\*$/',
            
            // Bidirectional
            '/^<->$/',
            '/^<-->$/',
            '/^<==>$/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $syntax)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Creates a unique key for a relationship that considers direction, type, and label
     */
    private function createRelationshipKey(string $source, string $target, string $type, ?string $label): string {
        if ($type === Relationship::TYPE_BIDIRECTIONAL) {
            $classes = [$source, $target];
            sort($classes);
            return implode(':', $classes) . ':' . $type . ($label ? ':' . $label : '');
        }
        
        return $source . ':' . $target . ':' . $type . ($label ? ':' . $label : '');
    }

    /**
     * Determine the relationship type based on syntax and label
     */
    private function determineRelationshipType(string $syntax, ?string $label): string {
        // Check label first for implementation/inheritance
        if ($label) {
            $labelLower = strtolower(trim($label));
            if ($labelLower === 'implements') {
                return Relationship::TYPE_IMPLEMENTATION;
            }
            if ($labelLower === 'extends') {
                return Relationship::TYPE_INHERITANCE;
            }
        }

        // Then check syntax
        if (strpos($syntax, '<|') !== false || strpos($syntax, '|>') !== false) {
            if (strpos($syntax, '..') !== false) {
                return Relationship::TYPE_IMPLEMENTATION;
            }
            return Relationship::TYPE_INHERITANCE;
        }

        if (strpos($syntax, '*') !== false || strpos($syntax, '-*') !== false || strpos($syntax, '*-') !== false) {
            return Relationship::TYPE_COMPOSITION;
        }

        if (strpos($syntax, 'o') !== false || strpos($syntax, '-o') !== false || strpos($syntax, 'o-') !== false) {
            return Relationship::TYPE_AGGREGATION;
        }

        if (strpos($syntax, '..') !== false) {
            return Relationship::TYPE_DEPENDENCY;
        }

        if (preg_match('/<[-=]>|<-->|<==>/', $syntax)) {
            return Relationship::TYPE_BIDIRECTIONAL;
        }

        return Relationship::TYPE_ASSOCIATION;
    }

    /**
     * Normalize multiplicity notation
     * Converts various multiplicity formats to a standardized format
     * but preserves certain common text values
     *
     * @param string $multiplicity Raw multiplicity value
     * @return string Normalized multiplicity
     */
    private function normalizeMultiplicity(string $multiplicity): string
    {
        // Trim any whitespace
        $multiplicity = trim($multiplicity);

        // Preserve common text values
        if (in_array(strtolower($multiplicity), ['many', 'n'])) {
            return $multiplicity;
        }

        // Handle common cases
        switch (strtolower($multiplicity)) {
            case '*':
                return '*';
            case '0..1':
            case '0,1':
            case 'zero or one':
                return '0..1';
            case '1':
            case 'one':
                return '1';
            case '0..*':
            case '0..n':
            case 'zero to many':
                return '0..*';
            case '1..*':
            case '1..n':
            case 'one to many':
                return '1..*';
        }

        // Check if it's a range (e.g., "2..5")
        if (preg_match('/^(\d+)\.\.(\d+|\*)$/', $multiplicity)) {
            return $multiplicity;
        }

        // If it's a single number
        if (is_numeric($multiplicity)) {
            return $multiplicity;
        }

        // Default case - return as is if we can't normalize it
        return $multiplicity;
    }

    /**
     * Map PlantUML relationship syntax to relationship type
     *
     * @param string $syntax PlantUML relationship syntax
     * @return string Relationship type
     */
    private function mapRelationshipType(string $syntax): string
    {
        // If the syntax contains inheritance markers, it's inheritance
        if (strpos($syntax, '<|') !== false || strpos($syntax, '|>') !== false) {
            if (strpos($syntax, '..') !== false) {
                return Relationship::TYPE_IMPLEMENTATION;
            }
            return Relationship::TYPE_INHERITANCE;
        }

        // If the syntax contains composition markers, it's composition
        if (strpos($syntax, '*') !== false || strpos($syntax, '-*') !== false || strpos($syntax, '*-') !== false) {
            return Relationship::TYPE_COMPOSITION;
        }

        // If the syntax contains aggregation markers, it's aggregation
        if (strpos($syntax, 'o') !== false || strpos($syntax, '-o') !== false || strpos($syntax, 'o-') !== false) {
            return Relationship::TYPE_AGGREGATION;
        }

        // If the syntax contains dots, it's a dependency
        if (strpos($syntax, '..') !== false) {
            return Relationship::TYPE_DEPENDENCY;
        }

        // If the syntax contains bidirectional markers, it's bidirectional
        if (preg_match('/<[-=]>|<-->|<==>/', $syntax)) {
            return Relationship::TYPE_BIDIRECTIONAL;
        }

        // Default to association
        return Relationship::TYPE_ASSOCIATION;
    }

    /**
     * Clean the input by removing comments and normalizing whitespace
     *
     * @param string $input
     * @return string
     */
    private function cleanInput(string $input): string
    {
        // Remove single-line comments that don't start with @
        $input = preg_replace('/(?<!@)\'[^\n]*\n/', "\n", $input);

        // Convert tabs to spaces and normalize line endings
        $input = str_replace(["\t", "\r\n", "\r"], [' ', "\n", "\n"], $input);

        // Normalize spaces around braces and colons
        $input = preg_replace('/\s*([{}:])\s*/', ' $1 ', $input);

        return $input;
    }
} 
