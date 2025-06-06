<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Java;

use App\Core\Generator\ClassDiagram\Domain\Model\AbstractLanguageCodeGenerator;
use App\Core\Generator\ClassDiagram\Domain\Model\Languages\JavaCodeGeneratorInterface;

/**
 * Abstract base class for Java code generators
 */
abstract class AbstractJavaCodeGenerator extends AbstractLanguageCodeGenerator implements JavaCodeGeneratorInterface
{
    /**
     * Java type mapping from UML to Java
     */
    protected const TYPE_MAPPING = [
        'string' => 'String',
        'int' => 'int',
        'integer' => 'int',
        'float' => 'float',
        'double' => 'double',
        'boolean' => 'boolean',
        'bool' => 'boolean',
        'array' => 'Object[]',
        'void' => 'void',
        'object' => 'Object',
        'mixed' => 'Object',
        'DateTime' => 'java.time.LocalDateTime',
        'datetime' => 'java.time.LocalDateTime',
        'LocalDateTime' => 'java.time.LocalDateTime',
        'localdatetime' => 'java.time.LocalDateTime',
        'Map' => 'java.util.Map',
        'map' => 'java.util.Map',
        'List' => 'java.util.List',
        'list' => 'java.util.List',
        'Set' => 'java.util.Set',
        'set' => 'java.util.Set',
        'Collection' => 'java.util.Collection',
        'collection' => 'java.util.Collection',
        'byte[]' => 'byte[]',
        'long' => 'long',
        'UUID' => 'java.util.UUID',
        'uuid' => 'java.util.UUID',
    ];

    /**
     * @var string The package name for generated code
     */
    protected string $packageName = 'com.example.generated';
    
    /**
     * Set the package name for generated Java code
     *
     * @param string $packageName
     * @return self
     */
    public function setPackageName(string $packageName): self
    {
        $this->packageName = $packageName;
        return $this;
    }
    
    /**
     * Get the package name for generated Java code
     *
     * @return string
     */
    public function getPackageName(): string
    {
        return $this->packageName;
    }
    
    /**
     * Map a UML type to a Java type
     *
     * @param string|null $type
     * @return string|null
     */
    protected function mapType(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }
        
        // Handle array types like 'string[]'
        if (substr($type, -2) === '[]') {
            $baseType = substr($type, 0, -2);
            $javaType = $this->mapType($baseType);
            return $javaType . '[]';
        }
        
        // Handle generic types
        if (preg_match('/^(\w+)\s*<(.+)>$/', $type, $matches)) {
            $baseType = $matches[1];
            $typeArgs = $matches[2];
            
            // Map the base type
            $javaBaseType = self::TYPE_MAPPING[strtolower($baseType)] ?? $baseType;
            
            // Extract just the class name if it's fully qualified
            if (strpos($javaBaseType, '.') !== false) {
                $parts = explode('.', $javaBaseType);
                $simpleBaseType = end($parts);
            } else {
                $simpleBaseType = $javaBaseType;
            }
            
            // Process type arguments recursively
            $processedTypeArgs = $this->processTypeArguments($typeArgs);
            
            return $simpleBaseType . '<' . $processedTypeArgs . '>';
        }
        
        // Special handling for void - only allow in return types, not field types
        if (strtolower($type) === 'void') {
            return 'void';
        }
        
        // Look up in the mapping table
        $mapped = self::TYPE_MAPPING[strtolower($type)] ?? $type;
        
        // If it's a fully qualified type, extract just the class name for use in code
        if (strpos($mapped, '.') !== false) {
            $parts = explode('.', $mapped);
            return end($parts);
        }
        
        return $mapped;
    }
    
    /**
     * Process type arguments for generics
     *
     * @param string $typeArgs
     * @return string
     */
    protected function processTypeArguments(string $typeArgs): string
    {
        // Split type arguments by comma (but respect nested generics)
        $args = [];
        $current = '';
        $depth = 0;
        
        for ($i = 0; $i < strlen($typeArgs); $i++) {
            $char = $typeArgs[$i];
            
            if ($char === '<') {
                $depth++;
            } elseif ($char === '>') {
                $depth--;
            } elseif ($char === ',' && $depth === 0) {
                $args[] = trim($current);
                $current = '';
                continue;
            }
            
            $current .= $char;
        }
        
        if (!empty($current)) {
            $args[] = trim($current);
        }
        
        // Map each type argument, but avoid recursive mapping for simple types
        $mappedArgs = [];
        foreach ($args as $arg) {
            $trimmedArg = trim($arg);
            // Direct mapping lookup without recursive mapType call to avoid duplication
            $mapped = self::TYPE_MAPPING[strtolower($trimmedArg)] ?? $trimmedArg;
            
            // If it's a fully qualified type, extract just the class name
            if (strpos($mapped, '.') !== false) {
                $parts = explode('.', $mapped);
                $mapped = end($parts);
            }
            
            $mappedArgs[] = $mapped;
        }
        
        return implode(', ', $mappedArgs);
    }
} 
