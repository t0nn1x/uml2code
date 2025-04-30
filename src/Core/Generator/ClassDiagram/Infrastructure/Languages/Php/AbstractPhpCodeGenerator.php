<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Php;

use App\Core\Generator\ClassDiagram\Domain\Model\AbstractLanguageCodeGenerator;
use App\Core\Generator\ClassDiagram\Domain\Model\Languages\PhpCodeGeneratorInterface;

/**
 * Abstract base class for PHP code generators
 */
abstract class AbstractPhpCodeGenerator extends AbstractLanguageCodeGenerator implements PhpCodeGeneratorInterface
{
    /**
     * PHP type mapping from UML to PHP
     */
    protected const TYPE_MAPPING = [
        'string' => 'string',
        'int' => 'int',
        'integer' => 'int',
        'float' => 'float',
        'double' => 'float',
        'boolean' => 'bool',
        'bool' => 'bool',
        'array' => 'array',
        'void' => 'void',
        'object' => 'object',
        'mixed' => 'mixed',
        'DateTime' => '\\DateTime',
        'Map' => 'array',
        'List' => 'array',
        'byte[]' => 'string',
        'long' => 'int',
        'UUID' => 'string',
    ];

    /**
     * @var string The namespace prefix for generated code
     */
    protected string $namespacePrefix = 'App\\Generated';
    
    /**
     * Set the namespace prefix for generated PHP code
     *
     * @param string $namespacePrefix
     * @return self
     */
    public function setNamespacePrefix(string $namespacePrefix): self
    {
        $this->namespacePrefix = trim($namespacePrefix, '\\');
        return $this;
    }
    
    /**
     * Get the namespace prefix for generated PHP code
     *
     * @return string
     */
    public function getNamespacePrefix(): string
    {
        return $this->namespacePrefix;
    }
    
    /**
     * Map a UML type to a PHP type
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
            return 'array';
        }
        
        // Handle generic types
        if (preg_match('/^(\w+)\s*<(.+)>$/', $type, $matches)) {
            $baseType = strtolower($matches[1]);
            
            // Most generic containers map to array in PHP
            if (in_array($baseType, ['list', 'map', 'set', 'collection'])) {
                return 'array';
            }
            
            // For other generic classes, use the base class name
            return $matches[1];
        }
        
        // Look up in the mapping table
        return static::TYPE_MAPPING[strtolower($type)] ?? $type;
    }
} 
