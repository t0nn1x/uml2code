<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Python;

use App\Core\Generator\ClassDiagram\Domain\Model\AbstractLanguageCodeGenerator;
use App\Core\Generator\ClassDiagram\Domain\Model\Languages\PythonCodeGeneratorInterface;

/**
 * Abstract base class for Python code generators
 */
abstract class AbstractPythonCodeGenerator extends AbstractLanguageCodeGenerator implements PythonCodeGeneratorInterface
{
    /**
     * Python type mapping from UML to Python
     */
    protected const TYPE_MAPPING = [
        'string' => 'str',
        'str' => 'str',
        'int' => 'int',
        'integer' => 'int',
        'float' => 'float',
        'double' => 'float',
        'boolean' => 'bool',
        'bool' => 'bool',
        'void' => 'None',
        'object' => 'object',
        'any' => 'Any',
        'DateTime' => 'datetime',
        'datetime' => 'datetime',
        'Date' => 'date',
        'date' => 'date',
        'Time' => 'time',
        'time' => 'time',
        'Map' => 'Dict',
        'map' => 'Dict',
        'Dictionary' => 'Dict',
        'dict' => 'Dict',
        'List' => 'List',
        'list' => 'List',
        'Array' => 'List',
        'array' => 'List',
        'Set' => 'Set',
        'set' => 'Set',
        'Collection' => 'List',
        'collection' => 'List',
        'Tuple' => 'Tuple',
        'tuple' => 'Tuple',
        'Optional' => 'Optional',
        'optional' => 'Optional',
        'Union' => 'Union',
        'union' => 'Union',
        'byte' => 'int',
        'byte[]' => 'bytes',
        'bytes' => 'bytes',
        'long' => 'int',
        'UUID' => 'UUID',
        'uuid' => 'UUID',
        'Status' => 'str',
        'status' => 'str',
        'Callable' => 'Callable',
        'callable' => 'Callable',
        'Final' => 'Final',
        'final' => 'Final',
        'Literal' => 'Literal',
        'literal' => 'Literal',
    ];

    /**
     * Common Python imports
     */
    protected const COMMON_IMPORTS = [
        'typing' => ['List', 'Dict', 'Optional', 'Union', 'Any', 'Callable', 'Tuple', 'Final', 'Literal', 'Set', 'TypeVar', 'Generic'],
        'datetime' => ['datetime', 'date', 'time'],
        'uuid' => ['UUID'],
        'enum' => ['Enum', 'IntEnum', 'StrEnum', 'auto'],
        'abc' => ['ABC', 'abstractmethod'],
        'dataclasses' => ['dataclass', 'field'],
    ];

    /**
     * @var string The module name prefix for generated code
     */
    protected string $modulePrefix = 'generated';
    
    /**
     * Set the module prefix for generated Python code
     *
     * @param string $modulePrefix
     * @return self
     */
    public function setModulePrefix(string $modulePrefix): self
    {
        $this->modulePrefix = str_replace(['/', '\\'], '.', trim($modulePrefix, '/\\'));
        return $this;
    }
    
    /**
     * Get the module prefix for generated Python code
     *
     * @return string
     */
    public function getModulePrefix(): string
    {
        return $this->modulePrefix;
    }

    /**
     * Map a UML type to Python type with import handling
     *
     * @param string $type The UML type
     * @return string The mapped Python type
     */
    protected function mapType(string $type): string
    {
        // Handle generic types like List<string>, Dict<string, int>, Final<string>, Literal<"A", "B">
        if (preg_match('/^(\w+)<(.+)>(\[\])?$/', $type, $matches)) {
            $baseType = $matches[1];
            $typeArgs = $matches[2];
            $isArray = !empty($matches[3]);
            
            // Map the base type
            $pythonBaseType = self::TYPE_MAPPING[strtolower($baseType)] ?? $baseType;
            
            // Process type arguments recursively - handle quoted literals differently
            if (strtolower($baseType) === 'literal') {
                // For Literal types, preserve the quoted strings
                $processedTypeArgs = $typeArgs; // Keep quotes intact
            } else {
                $processedTypeArgs = $this->processTypeArguments($typeArgs);
            }
            
            $result = $pythonBaseType . '[' . $processedTypeArgs . ']';
            
            // Handle array notation
            if ($isArray) {
                $result = 'List[' . $result . ']';
            }
            
            return $result;
        }
        
        // Handle array types
        if (str_ends_with($type, '[]')) {
            $baseType = substr($type, 0, -2);
            $pythonType = $this->mapType($baseType);
            return 'List[' . $pythonType . ']';
        }
        
        // Simple type mapping
        return self::TYPE_MAPPING[strtolower($type)] ?? $type;
    }

    /**
     * Process generic type arguments
     *
     * @param string $typeArgs The type arguments string
     * @return string The processed type arguments
     */
    protected function processTypeArguments(string $typeArgs): string
    {
        $args = [];
        $parts = $this->splitTypeArguments($typeArgs);
        
        foreach ($parts as $part) {
            $trimmed = trim($part);
            // Recursively map each type argument - this handles nested generics properly
            $mapped = $this->mapType($trimmed);
            $args[] = $mapped;
        }
        
        return implode(', ', $args);
    }

    /**
     * Split type arguments handling nested generics
     *
     * @param string $typeArgs The type arguments string
     * @return array Array of individual type arguments
     */
    protected function splitTypeArguments(string $typeArgs): array
    {
        $args = [];
        $current = '';
        $depth = 0;
        
        for ($i = 0; $i < strlen($typeArgs); $i++) {
            $char = $typeArgs[$i];
            
            if ($char === '<' || $char === '[') {
                $depth++;
            } elseif ($char === '>' || $char === ']') {
                $depth--;
            } elseif ($char === ',' && $depth === 0) {
                $args[] = trim($current);
                $current = '';
                continue;
            }
            
            $current .= $char;
        }
        
        if (!empty(trim($current))) {
            $args[] = trim($current);
        }
        
        return $args;
    }

    /**
     * Generate imports for a Python file
     *
     * @param array $usedTypes Array of types used in the file
     * @return string The import statements
     */
    protected function generateImports(array $usedTypes): string
    {
        $imports = [];
        $fromImports = [];
        $customImports = [];
        
        foreach ($usedTypes as $type) {
            $importInfo = $this->getImportForType($type);
            if ($importInfo) {
                if ($importInfo['type'] === 'module') {
                    $imports[] = $importInfo['import'];
                } else {
                    $module = $importInfo['module'];
                    $name = $importInfo['name'];
                    
                    if (!isset($fromImports[$module])) {
                        $fromImports[$module] = [];
                    }
                    $fromImports[$module][] = $name;
                }
            } else {
                // Check if this is a custom class that needs importing
                if ($this->isCustomClassType($type)) {
                    $customImports[] = $type;
                }
            }
        }
        
        $result = [];
        
        // Standard library imports
        foreach ($fromImports as $module => $names) {
            $uniqueNames = array_unique($names);
            sort($uniqueNames);
            $result[] = "from {$module} import " . implode(', ', $uniqueNames);
        }
        
        // Custom class imports (from same package)
        foreach (array_unique($customImports) as $customType) {
            $result[] = "from {$customType} import {$customType}";
        }
        
        // Module imports
        foreach (array_unique($imports) as $import) {
            $result[] = $import;
        }
        
        return empty($result) ? '' : implode("\n", $result) . "\n\n";
    }

    /**
     * Get import information for a type
     *
     * @param string $type The type name
     * @return array|null Import information or null if no import needed
     */
    protected function getImportForType(string $type): ?array
    {
        // Extract base type from generics
        $baseType = preg_replace('/\[.*\]$/', '', $type);
        
        foreach (self::COMMON_IMPORTS as $module => $types) {
            if (in_array($baseType, $types)) {
                return [
                    'type' => 'from',
                    'module' => $module,
                    'name' => $baseType
                ];
            }
        }
        
        return null;
    }

    /**
     * Extract all types used in a type string for import detection
     *
     * @param string $type
     * @return array
     */
    protected function extractTypesFromString(string $type): array
    {
        $types = [];
        
        // Extract main type
        $baseType = preg_replace('/\[.*\]$/', '', $type);
        $types[] = $baseType;
        
        // Extract generic arguments
        if (preg_match('/\[(.+)\]$/', $type, $matches)) {
            $args = $this->splitTypeArguments($matches[1]);
            foreach ($args as $arg) {
                $types = array_merge($types, $this->extractTypesFromString(trim($arg)));
            }
        }
        
        return array_unique($types);
    }

    /**
     * Generate Python property with getter/setter
     *
     * @param array $attribute The attribute data
     * @return string The property code
     */
    protected function generateProperty(array $attribute): string
    {
        $name = $attribute['name'];
        $type = $this->mapType($attribute['type'] ?? 'Any');
        $visibility = $attribute['visibility'] ?? 'public';
        
        // Python doesn't have true private/protected, use naming conventions
        $pythonName = match($visibility) {
            'private' => '_' . $name,
            'protected' => '_' . $name, 
            'package' => '_' . $name,
            default => $name
        };
        
        // Only generate properties for private/protected attributes
        if ($visibility === 'private' || $visibility === 'protected' || $visibility === 'package') {
            // Remove Final from property return types - Final is only for variable declarations
            $propertyType = $this->removeTypeQualifiers($type);
            
            $code = "    @property\n";
            $code .= "    def {$name}(self) -> {$propertyType}:\n";
            $code .= "        \"\"\"Get {$name}.\"\"\"\n";
            $code .= "        return self.{$pythonName}\n\n";
            
            $code .= "    @{$name}.setter\n";
            $code .= "    def {$name}(self, value: {$propertyType}) -> None:\n";
            $code .= "        \"\"\"Set {$name}.\"\"\"\n";
            $code .= "        self.{$pythonName} = value\n\n";
            
            return $code;
        }
        
        return ''; // No properties for public attributes
    }

    /**
     * Remove type qualifiers like Final from types for use in method signatures
     *
     * @param string $type
     * @return string
     */
    protected function removeTypeQualifiers(string $type): string
    {
        // Remove Final[T] wrapper - extract T
        if (preg_match('/^Final\[(.+)\]$/', $type, $matches)) {
            return $matches[1];
        }
        
        return $type;
    }

    /**
     * Generate Python method
     *
     * @param array $method The method data
     * @return string The method code
     */
    protected function generateMethod(array $method): string
    {
        $name = $method['name'];
        $returnType = $this->mapType($method['returnType'] ?? 'None');
        $parameters = $method['parameters'] ?? [];
        $isStatic = $method['isStatic'] ?? false;
        $isAbstract = $method['isAbstract'] ?? false;
        
        $code = '';
        
        // Add decorators
        if ($isStatic) {
            $code .= "    @staticmethod\n";
        }
        if ($isAbstract) {
            $code .= "    @abstractmethod\n";
        }
        
        // Method signature
        $paramStr = $isStatic ? '' : 'self';
        
        foreach ($parameters as $param) {
            if ($paramStr !== '' && $paramStr !== 'self') {
                $paramStr .= ', ';
            } elseif ($paramStr === 'self') {
                $paramStr .= ', ';
            }
            
            $paramType = $this->mapType($param['type'] ?? 'Any');
            $paramStr .= $param['name'] . ': ' . $paramType;
        }
        
        $code .= "    def {$name}({$paramStr}) -> {$returnType}:\n";
        $code .= "        \"\"\"" . ucfirst(str_replace('_', ' ', $name)) . ".\"\"\"\n";
        
        if ($isAbstract) {
            $code .= "        pass\n";
        } else {
            $code .= "        # TODO: Implement method\n";
            if ($returnType !== 'None') {
                $defaultReturn = $this->getDefaultReturnValue($returnType);
                $code .= $defaultReturn;
            }
        }
        
        return $code . "\n";
    }

    /**
     * Get appropriate default return value for a type
     *
     * @param string $returnType
     * @return string
     */
    protected function getDefaultReturnValue(string $returnType): string
    {
        // Handle Literal types - return first literal value
        if (str_contains($returnType, 'Literal[')) {
            if (preg_match('/Literal\[([^]]+)\]/', $returnType, $matches)) {
                $literals = explode(',', $matches[1]);
                $firstLiteral = trim($literals[0]);
                return "        return {$firstLiteral}\n";
            }
        }
        
        // Handle tuple types - return proper tuple with correct types
        if (str_contains($returnType, 'Tuple[')) {
            // Extract content between Tuple[...] handling nested brackets
            $start = strpos($returnType, 'Tuple[') + 6; // Length of 'Tuple['
            $depth = 1;
            $end = $start;
            
            for ($i = $start; $i < strlen($returnType) && $depth > 0; $i++) {
                if ($returnType[$i] === '[') {
                    $depth++;
                } elseif ($returnType[$i] === ']') {
                    $depth--;
                }
                $end = $i;
            }
            
            $tupleContent = substr($returnType, $start, $end - $start);
            $types = $this->splitTypeArguments($tupleContent);
            $values = [];
            
            foreach ($types as $type) {
                $type = trim($type);
                // Handle generic types like List[str]
                if (str_contains($type, '[')) {
                    $baseType = preg_replace('/\[.*\]$/', '', $type);
                    $values[] = match($baseType) {
                        'List', 'list' => '[]',
                        'Dict', 'dict' => '{}',
                        'Set', 'set' => 'set()',
                        'Tuple', 'tuple' => '()',
                        default => 'None'
                    };
                } else {
                    $values[] = match($type) {
                        'bool' => 'False',
                        'str' => "''",
                        'int' => '0',
                        'float' => '0.0',
                        default => 'None'
                    };
                }
            }
            return "        return (" . implode(', ', $values) . ")\n";
        }
        
        // Handle generic types
        if (str_contains($returnType, '[')) {
            $baseType = preg_replace('/\[.*\]$/', '', $returnType);
            return match($baseType) {
                'list', 'List' => "        return []\n",
                'dict', 'Dict' => "        return {}\n",
                'set', 'Set' => "        return set()\n",
                'tuple', 'Tuple' => "        return ()\n",
                default => "        return None\n"
            };
        }
        
        // Handle union types (Python 3.10+)
        if (str_contains($returnType, ' | ')) {
            return "        return None\n";
        }
        
        // Handle basic types
        return match($returnType) {
            'str' => "        return ''\n",
            'int' => "        return 0\n",
            'float' => "        return 0.0\n",
            'bool' => "        return False\n",
            'list' => "        return []\n",
            'dict' => "        return {}\n",
            'set' => "        return set()\n",
            'tuple' => "        return ()\n",
            'List' => "        return []\n",
            'Dict' => "        return {}\n",
            'Set' => "        return set()\n",
            'Tuple' => "        return ()\n",
            default => "        return None\n"
        };
    }

    /**
     * Check if a type refers to a custom class in the diagram
     *
     * @param string $type
     * @return bool
     */
    protected function isCustomClassType(string $type): bool
    {
        // Check if this type matches any class name in the diagram
        foreach ($this->diagram['classes'] as $class) {
            if ($class['name'] === $type) {
                return true;
            }
        }
        return false;
    }
} 
 