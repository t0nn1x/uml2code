<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Python;

/**
 * Python 3.9 code generator for class diagrams
 *
 * Python 3.9 new features:
 * - Dictionary merge and update operators (|, |=)
 * - Relaxed decorator restrictions
 * - Type hinting generics in standard collections (list[str] instead of List[str])
 * - str.removeprefix() and str.removesuffix()
 * - New math functions (gcd(), lcm())
 * - Topological sort in graphlib
 * - Improved error messages
 */
class Python39CodeGenerator extends Python38CodeGenerator
{
    /**
     * Python 3.9 enhanced type mapping with standard collection generics
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
        // Python 3.9: Use builtin generics instead of typing module when possible
        'Map' => 'dict',
        'map' => 'dict',
        'Dictionary' => 'dict',
        'dict' => 'dict',
        'List' => 'list',
        'list' => 'list',
        'Array' => 'list',
        'array' => 'list',
        'Set' => 'set',
        'set' => 'set',
        'Collection' => 'list',
        'collection' => 'list',
        'Tuple' => 'tuple',
        'tuple' => 'tuple',
        // Still need typing for complex types
        'Optional' => 'Optional',
        'optional' => 'Optional',
        'Union' => 'Union',
        'union' => 'Union',
        'Final' => 'Final',
        'final' => 'Final',
        'Literal' => 'Literal',
        'literal' => 'Literal',
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
    ];

    /**
     * Common Python 3.9+ imports (reduced typing imports)
     */
    protected const COMMON_IMPORTS = [
        // Only import from typing what's necessary in 3.9+
        'typing' => ['Optional', 'Union', 'Any', 'Callable', 'Final', 'Literal'],
        'datetime' => ['datetime', 'date', 'time'],
        'uuid' => ['UUID'],
        'enum' => ['Enum', 'IntEnum', 'auto'],
        'abc' => ['ABC', 'abstractmethod'],
        'dataclasses' => ['dataclass', 'field'],
        'collections.abc' => ['Mapping', 'Sequence', 'Iterable'],
    ];

    /**
     * Override file header for Python 3.9
     *
     * @return string
     */
    protected function generateFileHeader(): string
    {
        return "# -*- coding: utf-8 -*-\n\"\"\"Generated Python module for Python 3.9.\"\"\"\n\n";
    }

    /**
     * Map a UML type to Python 3.9 type with builtin generics support
     *
     * @param string $type The UML type
     * @return string The mapped Python type
     */
    protected function mapType(string $type): string
    {
        // Handle generic types with Python 3.9 builtin generics
        if (preg_match('/^(\w+)<(.+)>(\[\])?$/', $type, $matches)) {
            $baseType = $matches[1];
            $typeArgs = $matches[2];
            $isArray = !empty($matches[3]);
            
            // Map the base type using Python 3.9 builtin generics
            $pythonBaseType = static::TYPE_MAPPING[strtolower($baseType)] ?? $baseType;
            
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
                $result = 'list[' . $result . ']';
            }
            
            return $result;
        }
        
        // Handle array types with Python 3.9 builtin generics
        if (str_ends_with($type, '[]')) {
            $baseType = substr($type, 0, -2);
            $pythonType = $this->mapType($baseType);
            return 'list[' . $pythonType . ']';
        }
        
        // Simple type mapping using the current class's TYPE_MAPPING
        return static::TYPE_MAPPING[strtolower($type)] ?? $type;
    }

    /**
     * Process generic type arguments with Python 3.9 type mappings
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
            // Recursively map each type argument using our mapType method
            $mapped = $this->mapType($trimmed);
            $args[] = $mapped;
        }
        
        return implode(', ', $args);
    }

    /**
     * Generate class code with Python 3.9 features
     *
     * @param array $classData
     * @return string
     */
    protected function generateConcreteClass(array $classData): string
    {
        $name = $classData['name'];
        $extends = $classData['extends'] ?? null;
        $implements = $classData['implements'] ?? [];
        $typeParameters = $classData['typeParameters'] ?? [];
        
        // Build inheritance
        $inheritance = [];
        if ($extends) {
            $inheritance[] = $extends;
        }
        $inheritance = array_merge($inheritance, $implements);
        
        $code = "class {$name}";
        
        // Add generic type parameters as comment (Python doesn't have runtime generics like Java)
        if (!empty($typeParameters)) {
            $code .= "  # Generic[" . implode(', ', $typeParameters) . "]";
        }
        
        if (!empty($inheritance)) {
            $code .= "(" . implode(', ', $inheritance) . ")";
        }
        
        $code .= ":\n";
        $code .= "    \"\"\"" . ucfirst($name) . " class.\"\"\"\n\n";
        
        // Generate __init__ method with Python 3.9 features
        $code .= $this->generateInitMethod($classData);
        
        // Generate attributes as properties
        if (!empty($classData['attributes'])) {
            foreach ($classData['attributes'] as $attribute) {
                $code .= $this->generateProperty($attribute);
            }
        }
        
        // Generate methods with Python 3.9 enhancements
        if (!empty($classData['methods'])) {
            foreach ($classData['methods'] as $method) {
                $code .= $this->generateMethod($method);
            }
        }
        
        // If no methods or attributes, add pass
        if (empty($classData['attributes']) && empty($classData['methods'])) {
            $code .= "    pass\n";
        }
        
        return $code . "\n";
    }

    /**
     * Generate __init__ method with Python 3.9 dictionary merge features
     *
     * @param array $classData
     * @return string
     */
    protected function generateInitMethod(array $classData): string
    {
        $attributes = $classData['attributes'] ?? [];
        
        if (empty($attributes)) {
            return "    def __init__(self) -> None:\n        \"\"\"Initialize instance.\"\"\"\n        pass\n\n";
        }
        
        $code = "    def __init__(self";
        
        // Add parameters for attributes
        foreach ($attributes as $attribute) {
            $name = $attribute['name'];
            $type = $this->mapType($attribute['type'] ?? 'Any');
            $code .= ", {$name}: {$type} = None";
        }
        
        $code .= ") -> None:\n";
        
        // Check if we have dictionary attributes to demonstrate merge operator
        $hasDictAttributes = false;
        foreach ($attributes as $attribute) {
            $type = $this->mapType($attribute['type'] ?? '');
            if (str_contains($type, 'dict') || str_contains($type, 'Dict')) {
                $hasDictAttributes = true;
                break;
            }
        }
        
        // Initialize attributes
        foreach ($attributes as $attribute) {
            $name = $attribute['name'];
            $type = $this->mapType($attribute['type'] ?? 'Any');
            $visibility = $attribute['visibility'] ?? 'public';
            
            $pythonName = match($visibility) {
                'private' => '_' . $name,
                'protected' => '_' . $name,
                'package' => '_' . $name,
                default => $name
            };
            
            // Use Python 3.9 features based on type (check most specific types first)
            if (str_starts_with($type, 'list[') || str_starts_with($type, 'List[')) {
                $code .= "        # Python 3.9+ list handling\n";
                $code .= "        self.{$pythonName} = {$name} or []\n";
            } elseif (str_starts_with($type, 'dict[') || str_starts_with($type, 'Dict[')) {
                $code .= "        # Python 3.9+ dictionary merge operator\n";
                $code .= "        self.{$pythonName} = {} | ({$name} or {})\n";
            } elseif (str_starts_with($type, 'set[') || str_starts_with($type, 'Set[')) {
                $code .= "        # Python 3.9+ set handling\n";
                $code .= "        self.{$pythonName} = {$name} or set()\n";
            } else {
                $code .= "        self.{$pythonName} = {$name}\n";
            }
        }
        
        return $code . "\n";
    }

    /**
     * Generate method with Python 3.9 improvements
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
        
        // Method signature with improved type hints
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
        $code .= "        \"\"\"" . ucfirst(str_replace('_', ' ', $name)) . " (Python 3.9+).\"\"\"\n";
        
        if ($isAbstract) {
            $code .= "        pass\n";
        } else {
            $code .= "        # TODO: Implement method\n";
            
            // Add Python 3.9 specific examples for certain method patterns
            if (str_contains($name, 'merge') || str_contains($name, 'combine')) {
                $code .= "        # Example using Python 3.9 dictionary merge operator\n";
                $code .= "        # result = dict1 | dict2\n";
            } elseif (str_contains($name, 'clean') || str_contains($name, 'strip')) {
                $code .= "        # Example using Python 3.9 string methods\n";
                $code .= "        # cleaned = text.removeprefix('prefix').removesuffix('suffix')\n";
            }
            
            if ($returnType !== 'None') {
                // Enhanced return value generation for Python 3.9
                if (str_contains($returnType, 'list[') || str_contains($returnType, 'List[')) {
                    $code .= "        return []\n";
                } elseif (str_contains($returnType, 'dict[') || str_contains($returnType, 'Dict[')) {
                    $code .= "        return {}\n";
                } elseif (str_contains($returnType, 'set[') || str_contains($returnType, 'Set[')) {
                    $code .= "        return set()\n";
                } elseif (str_contains($returnType, 'tuple[') || str_contains($returnType, 'Tuple[')) {
                    $code .= "        return ()\n";
                } else {
                    $defaultReturn = match($returnType) {
                        'str' => "        return ''\n",
                        'int' => "        return 0\n",
                        'float' => "        return 0.0\n",
                        'bool' => "        return False\n",
                        'list' => "        return []\n",
                        'dict' => "        return {}\n",
                        'set' => "        return set()\n",
                        'tuple' => "        return ()\n",
                        default => "        return None\n"
                    };
                    $code .= $defaultReturn;
                }
            }
        }
        
        return $code . "\n";
    }

    /**
     * Get import information for a type (updated for Python 3.9)
     *
     * @param string $type The type name
     * @return array|null Import information or null if no import needed
     */
    protected function getImportForType(string $type): ?array
    {
        // Extract base type from generics
        $baseType = preg_replace('/\[.*\]$/', '', $type);
        
        // Python 3.9 builtin types don't need imports
        $builtinTypes = ['list', 'dict', 'set', 'tuple', 'type'];
        if (in_array($baseType, $builtinTypes)) {
            return null;
        }
        
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
     * Override enum generation to avoid StrEnum (Python 3.11 feature)
     *
     * @param array $classData
     * @return string
     */
    protected function generateEnum(array $classData): string
    {
        $name = $classData['name'];
        $enumValues = $classData['enumValues'] ?? [];
        
        // Determine enum type based on values - don't use StrEnum in Python 3.9
        $enumType = $this->detectEnumType($classData);
        $baseClass = match($enumType) {
            'int' => 'IntEnum',
            'str' => 'Enum',  // Use Enum instead of StrEnum for Python 3.9
            default => 'Enum'
        };
        
        $code = "class {$name}({$baseClass}):\n";
        $code .= "    \"\"\"Enum {$name}.\n\n";
        $code .= "    Generated for Python 3.8+\n";
        $code .= "    \"\"\"\n\n";
        
        if (!empty($enumValues)) {
            foreach ($enumValues as $enumValue) {
                $valueName = is_array($enumValue) ? $enumValue['name'] : $enumValue;
                $value = is_array($enumValue) ? $enumValue['value'] : null;
                
                if ($value !== null) {
                    if ($enumType === 'str') {
                        // Remove extra quotes from already quoted values
                        $cleanValue = trim($value, '"\'');
                        $code .= "    {$valueName} = '{$cleanValue}'\n";
                    } else {
                        $code .= "    {$valueName} = {$value}\n";
                    }
                } else {
                    // Auto-assign values for Enum
                    $code .= "    {$valueName} = auto()\n";
                }
            }
        } else {
            $code .= "    pass\n";
        }
        
        return $code . "\n";
    }

    /**
     * Override type collection to handle Python 3.9 enum imports
     *
     * @param array $classData
     * @return array
     */
    protected function collectUsedTypes(array $classData): array
    {
        $types = parent::collectUsedTypes($classData);
        
        // For enums in Python 3.9, ensure we don't include StrEnum
        if ($classData['type'] === 'enum') {
            $enumType = $this->detectEnumType($classData);
            $types = array_filter($types, function($type) {
                return $type !== 'StrEnum';
            });
            
            // Ensure Enum is included for string enums
            if ($enumType === 'str' && !in_array('Enum', $types)) {
                $types[] = 'Enum';
            }
        }
        
        return $types;
    }
} 
 