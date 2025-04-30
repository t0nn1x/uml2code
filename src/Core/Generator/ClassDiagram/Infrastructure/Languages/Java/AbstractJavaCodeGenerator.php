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
        'Map' => 'java.util.Map',
        'List' => 'java.util.List',
        'Set' => 'java.util.Set',
        'Collection' => 'java.util.Collection',
        'byte[]' => 'byte[]',
        'long' => 'long',
        'UUID' => 'java.util.UUID',
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
            
            // If it's a fully qualified name already, don't wrap in java.util
            if (strpos($javaBaseType, '.') === false && in_array(strtolower($baseType), ['list', 'map', 'set', 'collection'])) {
                $javaBaseType = 'java.util.' . $javaBaseType;
            }
            
            return $javaBaseType . '<' . $typeArgs . '>';
        }
        
        // Look up in the mapping table
        return self::TYPE_MAPPING[strtolower($type)] ?? $type;
    }
} 
