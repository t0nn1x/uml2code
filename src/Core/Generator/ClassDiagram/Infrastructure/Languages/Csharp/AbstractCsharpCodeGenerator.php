<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Csharp;

use App\Core\Generator\ClassDiagram\Domain\Model\AbstractLanguageCodeGenerator;
use App\Core\Generator\ClassDiagram\Domain\Model\Languages\CsharpCodeGeneratorInterface;

/**
 * Abstract base class for C# code generators
 */
abstract class AbstractCsharpCodeGenerator extends AbstractLanguageCodeGenerator implements CsharpCodeGeneratorInterface
{
    /**
     * C# type mapping from UML to C#
     */
    protected const TYPE_MAPPING = [
        'string' => 'string',
        'int' => 'int',
        'integer' => 'int',
        'float' => 'float',
        'double' => 'double',
        'decimal' => 'decimal',
        'boolean' => 'bool',
        'bool' => 'bool',
        'array' => 'object[]',
        'void' => 'void',
        'object' => 'object',
        'mixed' => 'object',
        'dynamic' => 'dynamic',
        'DateTime' => 'DateTime',
        'datetime' => 'DateTime',
        'DateTimeOffset' => 'DateTimeOffset',
        'datetimeoffset' => 'DateTimeOffset',
        'TimeSpan' => 'TimeSpan',
        'timespan' => 'TimeSpan',
        'Map' => 'Dictionary',
        'map' => 'Dictionary',
        'List' => 'List',
        'list' => 'List',
        'Set' => 'HashSet',
        'set' => 'HashSet',
        'Collection' => 'ICollection',
        'collection' => 'ICollection',
        'IEnumerable' => 'IEnumerable',
        'ienumerable' => 'IEnumerable',
        'IList' => 'IList',
        'ilist' => 'IList',
        'IDictionary' => 'IDictionary',
        'idictionary' => 'IDictionary',
        'byte' => 'byte',
        'byte[]' => 'byte[]',
        'sbyte' => 'sbyte',
        'short' => 'short',
        'ushort' => 'ushort',
        'uint' => 'uint',
        'long' => 'long',
        'ulong' => 'ulong',
        'char' => 'char',
        'UUID' => 'Guid',
        'uuid' => 'Guid',
        'Guid' => 'Guid',
        'guid' => 'Guid',
        'Task' => 'Task',
        'task' => 'Task',
    ];

    /**
     * @var string The namespace for generated code
     */
    protected string $namespace = 'GeneratedCode';
    
    /**
     * Set the namespace for generated C# code
     *
     * @param string $namespace
     * @return self
     */
    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }
    
    /**
     * Get the namespace for generated C# code
     *
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }
    
    /**
     * Map UML visibility to C# access modifiers
     *
     * @param string $visibility The UML visibility
     * @return string The C# access modifier
     */
    protected function mapVisibility(string $visibility): string
    {
        return match ($visibility) {
            'package' => 'internal', // UML package visibility maps to internal in C#
            'public', 'private', 'protected' => $visibility,
            default => 'public',
        };
    }
    
    /**
     * Map a UML type to a C# type
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
            $csharpType = $this->mapType($baseType);
            return $csharpType . '[]';
        }
        
        // Handle generic types
        if (preg_match('/^(\w+)\s*<(.+)>$/', $type, $matches)) {
            $baseType = $matches[1];
            $typeArgs = $matches[2];
            
            // Map the base type
            $csharpBaseType = self::TYPE_MAPPING[strtolower($baseType)] ?? $baseType;
            
            // Process type arguments recursively
            $processedTypeArgs = $this->processTypeArguments($typeArgs);
            
            return $csharpBaseType . '<' . $processedTypeArgs . '>';
        }
        
        // Handle nullable types (Type?)
        if (substr($type, -1) === '?') {
            $baseType = substr($type, 0, -1);
            $csharpType = $this->mapType($baseType);
            return $csharpType . '?';
        }
        
        // Look up in the mapping table
        return self::TYPE_MAPPING[strtolower($type)] ?? $type;
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
        
        // Map each type argument
        $mappedArgs = [];
        foreach ($args as $arg) {
            $mappedArgs[] = $this->mapType(trim($arg));
        }
        
        return implode(', ', $mappedArgs);
    }
    
    /**
     * Get default value for a C# type
     *
     * @param string $type
     * @return string
     */
    protected function getDefaultReturnValue(string $type): string
    {
        // Handle nullable types
        if (substr($type, -1) === '?') {
            return 'null';
        }
        
        // Handle generic types
        if (strpos($type, '<') !== false) {
            $baseType = substr($type, 0, strpos($type, '<'));
            return match ($baseType) {
                'List', 'HashSet', 'Dictionary', 'IList', 'ICollection', 'IEnumerable' => "new {$type}()",
                'Task' => 'Task.CompletedTask',
                default => 'null',
            };
        }
        
        return match ($type) {
            'int', 'uint', 'long', 'ulong', 'short', 'ushort', 'byte', 'sbyte' => '0',
            'float' => '0.0f',
            'double' => '0.0',
            'decimal' => '0m',
            'bool' => 'false',
            'char' => '\'\\0\'',
            'string' => '""',
            'DateTime' => 'DateTime.MinValue',
            'DateTimeOffset' => 'DateTimeOffset.MinValue',
            'TimeSpan' => 'TimeSpan.Zero',
            'Guid' => 'Guid.Empty',
            'Task' => 'Task.CompletedTask',
            'void' => '',
            default => 'null',
        };
    }
} 
