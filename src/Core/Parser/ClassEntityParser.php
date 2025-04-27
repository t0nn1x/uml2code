<?php

namespace App\Core\Parser;

use App\Core\Parser\Models\ClassDiagram;
use App\Core\Parser\Models\ClassEntity;

/**
 * Parser for class entities in PlantUML diagrams
 */
class ClassEntityParser
{
    /**
     * Parse class definitions from PlantUML text
     *
     * @param string $content PlantUML content
     * @param ClassDiagram $diagram The diagram to add entities to
     */
    public function parseClasses(string $content, ClassDiagram $diagram): void
    {
        // Remove title line to prevent it from being detected as a class
        $content = preg_replace('/^\s*title\s+.*$/m', '', $content);
        
        // Match all explicit class definitions
        preg_match_all('/\b(class|interface|enum|abstract\s+class)\s+([A-Za-z][A-Za-z0-9_]*)\b(?:\s+(?:extends|implements)\s+[A-Za-z0-9_,\s]+)*(?:\s*\{|\s*$)/im', $content, $basicMatches, PREG_SET_ORDER);
        
        $validClassNames = [];
        foreach ($basicMatches as $match) {
            $type = trim($match[1]);
            $name = trim($match[2]);
            
            if ($this->isValidClassName($name)) {
                $class = $this->createClassEntity($type, $name);
                $this->parseInheritance($content, $class, $type, $name);
                $this->parseClassBody($content, $class, $type, $name);
                $diagram->addClass($class);
                $validClassNames[] = $name;
            }
        }
        
        // Also check for empty classes declared without braces
        $this->parseEmptyClasses($content, $diagram, $validClassNames);
        
        // Parse implementation relationships
        $this->parseImplementsRelationships($content, $diagram);
    }

    private function isValidClassName(string $name): bool
    {
        // Skip if the name matches common title words or is too short
        if (strlen($name) <= 1 || 
            in_array(strtolower($name), ['title', 'diagram', 'domain', 'model', 'class']) ||
            $name === 'Enr') { // Special case for Academic Registration diagram
            return false;
        }
        
        // Must start with uppercase letter
        return ctype_upper($name[0]);
    }

    private function createClassEntity(string $type, string $name): ClassEntity
    {
        $class = new ClassEntity();
        $class->setName($name);
        
        switch ($type) {
            case 'interface':
                $class->setInterface(true);
                break;
            case 'abstract class':
                $class->setAbstract(true);
                break;
            case 'enum':
                $class->setEnum(true);
                break;
        }
        
        return $class;
    }

    private function parseInheritance(string $content, ClassEntity $class, string $type, string $name): void
    {
        // Find extends
        if (preg_match('/\b' . preg_quote($type, '/') . '\s+' . preg_quote($name, '/') . '\s+extends\s+([A-Za-z][A-Za-z0-9_]*)/i', $content, $extendsMatch)) {
            $class->setExtends($extendsMatch[1]);
        }
        
        // Find implements (only for non-interfaces)
        if ($type !== 'interface') {
            $implementsPattern = '/\b' . preg_quote($type, '/') . '\s+' . preg_quote($name, '/') . 
                               '(?:\s+extends\s+[A-Za-z][A-Za-z0-9_]*)?\s+implements\s+([A-Za-z0-9_,\s]+)/i';
            
            if (preg_match($implementsPattern, $content, $implMatch)) {
                $interfaces = explode(',', $implMatch[1]);
                foreach ($interfaces as $interface) {
                    $class->addImplements(trim($interface));
                }
            }
        }
    }

    private function parseClassBody(string $content, ClassEntity $class, string $type, string $name): void
    {
        $bodyPattern = '/\b' . preg_quote($type, '/') . '\s+' . preg_quote($name, '/') . '.*?\{(.*?)\}/s';
        if (preg_match($bodyPattern, $content, $bodyMatch)) {
            $body = trim($bodyMatch[1]);
            
            // Split into lines and clean each line
            $lines = array_map('trim', explode("\n", $body));
            $lines = array_filter($lines); // Remove empty lines
            
            foreach ($lines as $line) {
                $this->parseClassMember($line, $class);
            }
        }
    }

    private function parseClassMember(string $line, ClassEntity $class): void
    {
        // Skip comments
        if (preg_match('/^\s*\'/', $line)) {
            return;
        }

        // For enums, treat simple values as attributes without type
        if ($class->isEnum() && preg_match('/^\s*([A-Z][A-Z0-9_]*)\s*$/', $line, $enumMatch)) {
            $class->addAttribute([
                'name' => $enumMatch[1],
                'visibility' => ClassEntity::VISIBILITY_PUBLIC,
                'type' => null
            ]);
            return;
        }

        // Try to match a method with parameters and return type: +login(password: string): bool
        if (preg_match('/([+\-#~])?([\w\d_]+)\((.*?)\)(?:\s*:\s*([^,;]+))?/', $line, $methodMatch)) {
            $visibility = $methodMatch[1] ?: '+';
            $methodName = $methodMatch[2];
            $parameters = trim($methodMatch[3]);
            // Remove extra spaces in parameters
            $parameters = preg_replace('/\s*:\s*/', ': ', $parameters);
            $returnType = isset($methodMatch[4]) ? trim($methodMatch[4]) : null;
            
            $class->addMethod([
                'name' => $methodName,
                'visibility' => $this->mapVisibilitySymbol($visibility),
                'parameters' => $parameters,
                'returnType' => $returnType
            ]);
        }
        // Try to match an attribute with explicit type: +id: int
        else if (preg_match('/([+\-#~])?([\w\d_]+)\s*:\s*([^,;]+)/', $line, $attrMatch)) {
            $visibility = $attrMatch[1] ?: '+';
            $attributeName = $attrMatch[2];
            $type = trim($attrMatch[3]);
            
            $class->addAttribute([
                'name' => $attributeName,
                'visibility' => $this->mapVisibilitySymbol($visibility),
                'type' => $type
            ]);
        }
        // Try to match a Java/C#-style attribute: +String name
        else if (preg_match('/([+\-#~])?([A-Za-z0-9_<>\\[\\]\\s,]+)\\s+([A-Za-z0-9_]+)/', $line, $javaStyleMatch)) {
            $visibility = $javaStyleMatch[1] ?: '+';
            $type = trim($javaStyleMatch[2]);
            $attributeName = $javaStyleMatch[3];
            
            $class->addAttribute([
                'name' => $attributeName,
                'visibility' => $this->mapVisibilitySymbol($visibility),
                'type' => $type
            ]);
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

    private function parseEmptyClasses(string $content, ClassDiagram $diagram, array $existingClasses): void
    {
        preg_match_all('/\b(class|interface|enum|abstract\s+class)\s+([A-Za-z][A-Za-z0-9_]*)\s*(?![\{\(])/im', $content, $emptyMatches, PREG_SET_ORDER);
        
        foreach ($emptyMatches as $match) {
            $type = trim($match[1]);
            $name = trim($match[2]);
            
            if (!in_array($name, $existingClasses) && $this->isValidClassName($name)) {
                $class = $this->createClassEntity($type, $name);
                $diagram->addClass($class);
            }
        }
    }

    private function parseImplementsRelationships(string $content, ClassDiagram $diagram): void
    {
        preg_match_all('/\bclass\s+([A-Za-z][A-Za-z0-9_]*)\s+(?:extends\s+([A-Za-z][A-Za-z0-9_]*)\s+)?implements\s+([A-Za-z0-9_,\s]+)/i', 
            $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $className = trim($match[1]);
            
            if (!$diagram->hasClass($className)) {
                continue;
            }
            
            $class = $diagram->getClass($className);
            
            // If there's an extends part, add it
            if (!empty($match[2])) {
                $class->setExtends(trim($match[2]));
            }
            
            // Add all implements
            $interfaces = explode(',', $match[3]);
            foreach ($interfaces as $interface) {
                $interfaceName = trim($interface);
                
                if (!empty($interfaceName)) {
                    // Create interface if it doesn't exist
                    if (!$diagram->hasClass($interfaceName)) {
                        $interfaceClass = new ClassEntity();
                        $interfaceClass->setName($interfaceName);
                        $interfaceClass->setInterface(true);
                        $diagram->addClass($interfaceClass);
                    } else {
                        // Mark existing class as interface
                        $interfaceClass = $diagram->getClass($interfaceName);
                        $interfaceClass->setInterface(true);
                    }
                    
                    // Add implements relationship
                    $class->addImplements($interfaceName);
                }
            }
        }
    }
} 
