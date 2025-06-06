<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Python;

/**
 * Python 3.10 code generator for class diagrams
 *
 * Python 3.10 new features:
 * - Pattern matching with match-case statements
 * - Union types with | operator (X | Y instead of Union[X, Y])
 * - Better error messages
 * - Structural pattern matching
 * - Parenthesized context managers
 * - Precise line numbers for debugging
 * - New typing features
 */
class Python310CodeGenerator extends Python39CodeGenerator
{
    /**
     * Python 3.10 enhanced type mapping with union operator
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
        'null' => 'None',
        'object' => 'object',
        'any' => 'Any',
        'DateTime' => 'datetime',
        'datetime' => 'datetime',
        'Date' => 'date',
        'date' => 'date',
        'Time' => 'time',
        'time' => 'time',
        // Python 3.10: Use builtin generics
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
        // Python 3.10: Prefer union operator over typing.Union when possible
        'Optional' => 'Optional',
        'optional' => 'Optional',
        'Union' => 'Union', // Still available but | is preferred
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
     * Common Python 3.10+ imports (minimal typing usage)
     */
    protected const COMMON_IMPORTS = [
        // Minimal typing imports due to builtin union operator
        'typing' => ['Any', 'Callable', 'Final', 'Literal', 'Optional'],
        'datetime' => ['datetime', 'date', 'time'],
        'uuid' => ['UUID'],
        'enum' => ['Enum', 'IntEnum', 'StrEnum', 'auto'],
        'abc' => ['ABC', 'abstractmethod'],
        'dataclasses' => ['dataclass', 'field'],
        'collections.abc' => ['Mapping', 'Sequence', 'Iterable'],
    ];

    /**
     * Override file header for Python 3.10
     *
     * @return string
     */
    protected function generateFileHeader(): string
    {
        return "# -*- coding: utf-8 -*-\n\"\"\"Generated Python module for Python 3.10.\"\"\"\n\n";
    }

    /**
     * Map a UML type to Python 3.10 type with union operator support
     *
     * @param string $type The UML type
     * @return string The mapped Python type
     */
    protected function mapType(string $type): string
    {
        // Handle Union types with Python 3.10 | operator (both formats)
        if (preg_match('/^Union\[(.+)\]$/', $type, $matches)) {
            $unionTypes = explode(',', $matches[1]);
            $mappedTypes = array_map(fn($t) => $this->mapType(trim($t)), $unionTypes);
            return implode(' | ', $mappedTypes);
        }
        
        // Handle Optional types as union with None
        if (preg_match('/^Optional\[(.+)\]$/', $type, $matches)) {
            $innerType = $this->mapType(trim($matches[1]));
            return $innerType . ' | None';
        }
        
        // Handle generic types with Python 3.10 builtin generics (BEFORE union check)
        if (preg_match('/^(\w+)<(.+)>(\[\])?$/', $type, $matches)) {
            $baseType = $matches[1];
            $typeArgs = $matches[2];
            $isArray = !empty($matches[3]);
            
            // Special handling for associative arrays - map to dict if it has key-value pairs
            if (strtolower($baseType) === 'array' && str_contains($typeArgs, ',')) {
                // array<string, int> -> dict[str, int]
                $pythonBaseType = 'dict';
            } else {
                // Map the base type using Python 3.10 builtin generics
                $pythonBaseType = static::TYPE_MAPPING[strtolower($baseType)] ?? $baseType;
            }
            
            // Process type arguments recursively
            $processedTypeArgs = $this->processTypeArguments($typeArgs);
            
            $result = $pythonBaseType . '[' . $processedTypeArgs . ']';
            
            // Handle array notation
            if ($isArray) {
                $result = 'list[' . $result . ']';
            }
            
            return $result;
        }
        
        // Handle array types with Python 3.10 builtin generics
        if (str_ends_with($type, '[]')) {
            $baseType = substr($type, 0, -2);
            $pythonType = $this->mapType($baseType);
            return 'list[' . $pythonType . ']';
        }
        
        // Handle union types with | operator (from UML parser) - AFTER generic check
        // Only process as union if it's a top-level union, not inside generic brackets
        if (str_contains($type, '|') && !$this->isUnionInsideGeneric($type)) {
            $unionTypes = explode('|', $type);
            $mappedTypes = array_map(fn($t) => $this->mapType(trim($t)), $unionTypes);
            return implode(' | ', $mappedTypes);
        }
        
        // Simple type mapping
        return static::TYPE_MAPPING[strtolower($type)] ?? $type;
    }

    /**
     * Process type arguments recursively with Python 3.10 mapping
     *
     * @param string $typeArgs The type arguments string
     * @return string The processed type arguments
     */
    protected function processTypeArguments(string $typeArgs): string
    {
        if (empty($typeArgs)) {
            return '';
        }

        // Use smart splitting that handles nested generics and union types
        $args = $this->smartSplitTypeArguments($typeArgs);
        $processedArgs = [];

        foreach ($args as $arg) {
            $trimmedArg = trim($arg);
            
            // Recursively map each type argument using Python 3.10 mapping
            $processedArgs[] = $this->mapType($trimmedArg);
        }

        return implode(', ', $processedArgs);
    }

    /**
     * Smart split type arguments handling nested brackets and union types
     *
     * @param string $typeArgs The type arguments string
     * @return array The split arguments
     */
    protected function smartSplitTypeArguments(string $typeArgs): array
    {
        $args = [];
        $current = '';
        $depth = 0;
        $inUnion = false;
        
        for ($i = 0; $i < strlen($typeArgs); $i++) {
            $char = $typeArgs[$i];
            
            if ($char === '<' || $char === '[') {
                $depth++;
                $current .= $char;
            } elseif ($char === '>' || $char === ']') {
                $depth--;
                $current .= $char;
            } elseif ($char === '|') {
                $inUnion = true;
                $current .= $char;
            } elseif ($char === ',' && $depth === 0) {
                // Only split on comma if we're not inside nested brackets
                $args[] = trim($current);
                $current = '';
                $inUnion = false;
            } else {
                $current .= $char;
            }
        }
        
        if (!empty($current)) {
            $args[] = trim($current);
        }
        
        return $args;
    }

    /**
     * Check if a union type is inside generic brackets (not a top-level union)
     *
     * @param string $type The type to check
     * @return bool True if the union is inside generic brackets
     */
    protected function isUnionInsideGeneric(string $type): bool
    {
        // If there are no generic brackets, it's definitely a top-level union
        if (!str_contains($type, '<') && !str_contains($type, '[')) {
            return false;
        }
        
        // Check if all | operators are inside generic brackets
        $depth = 0;
        for ($i = 0; $i < strlen($type); $i++) {
            $char = $type[$i];
            
            if ($char === '<' || $char === '[') {
                $depth++;
            } elseif ($char === '>' || $char === ']') {
                $depth--;
            } elseif ($char === '|' && $depth === 0) {
                // Found a | at top level, so it's a top-level union
                return false;
            }
        }
        
        // All | operators are inside brackets
        return true;
    }

    /**
     * Generate __init__ method with Python 3.10 type mapping
     *
     * @param array $classData The class data
     * @return string The __init__ method code
     */
    protected function generateInitMethod(array $classData): string
    {
        $attributes = $classData['attributes'] ?? [];
        
        if (empty($attributes)) {
            return "    def __init__(self) -> None:\n        \"\"\"Initialize instance.\"\"\"\n        pass\n\n";
        }
        
        $code = "    def __init__(self";
        
        // Add parameters for attributes with Python 3.10 type mapping
        foreach ($attributes as $attribute) {
            $name = $attribute['name'];
            $type = $this->mapType($attribute['type'] ?? 'Any');
            $code .= ", {$name}: {$type} = None";
        }
        
        $code .= ") -> None:\n";
        
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
            
            // Use Python 3.9+ features based on type (check most specific types first)
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
     * Generate Python property with getter/setter using Python 3.10 type mapping
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
     * Get default return value for Python 3.10 with union type support
     *
     * @param string $returnType The return type
     * @return string The default return value
     */
    protected function getDefaultReturnValue(string $returnType): string
    {
        if ($returnType === 'None') {
            return 'None';
        }

        // Handle union types - return None for any union type
        if (str_contains($returnType, '|')) {
            return 'None';
        }

        // Handle generic types
        if (preg_match('/^(list|dict|set|tuple)\[/', $returnType)) {
            if (str_starts_with($returnType, 'list')) {
                return '[]';
            }
            if (str_starts_with($returnType, 'dict')) {
                return '{}';
            }
            if (str_starts_with($returnType, 'set')) {
                return 'set()';
            }
            if (str_starts_with($returnType, 'tuple')) {
                return '()';
            }
        }

        // Simple types
        return match($returnType) {
            'str' => "''",
            'int' => '0',
            'float' => '0.0',
            'bool' => 'False',
            'list' => '[]',
            'dict' => '{}',
            'set' => 'set()',
            'tuple' => '()',
            default => 'None'
        };
    }

    /**
     * Collect used types for Python 3.10 imports
     *
     * @param array $classData The class data
     * @return array The collected types
     */
    protected function collectUsedTypes(array $classData): array
    {
        $usedTypes = [];
        
        // Collect types from attributes
        foreach ($classData['attributes'] ?? [] as $attribute) {
            $type = $attribute['type'] ?? '';
            $this->collectTypesFromString($type, $usedTypes);
        }
        
        // Collect types from methods
        foreach ($classData['methods'] ?? [] as $method) {
            // Return type
            $returnType = $method['returnType'] ?? '';
            $this->collectTypesFromString($returnType, $usedTypes);
            
            // Parameter types
            foreach ($method['parameters'] ?? [] as $param) {
                $paramType = $param['type'] ?? '';
                $this->collectTypesFromString($paramType, $usedTypes);
            }
        }
        
        return $usedTypes;
    }

    /**
     * Collect types from a type string (handles generics and unions)
     *
     * @param string $typeString The type string
     * @param array &$usedTypes The array to collect types into
     */
    protected function collectTypesFromString(string $typeString, array &$usedTypes): void
    {
        if (empty($typeString)) {
            return;
        }

        // Handle union types with |
        if (str_contains($typeString, '|')) {
            $unionTypes = explode('|', $typeString);
            foreach ($unionTypes as $unionType) {
                $this->collectTypesFromString(trim($unionType), $usedTypes);
            }
            return;
        }

        // Handle generic types
        if (preg_match('/^([^<\[]+)[<\[](.+)[>\]](\[\])?$/', $typeString, $matches)) {
            $baseType = trim($matches[1]);
            $typeArgs = $matches[2];
            
            // Add the base type
            $this->addTypeToCollection($baseType, $usedTypes);
            
            // Process type arguments
            $args = explode(',', $typeArgs);
            foreach ($args as $arg) {
                $this->collectTypesFromString(trim($arg), $usedTypes);
            }
            return;
        }

        // Simple type
        $this->addTypeToCollection($typeString, $usedTypes);
    }

    /**
     * Add a type to the collection if it needs importing
     *
     * @param string $type The type to add
     * @param array &$usedTypes The array to add to
     */
    protected function addTypeToCollection(string $type, array &$usedTypes): void
    {
        // Clean the type
        $cleanType = trim($type);
        
        // Skip built-in types
        $builtinTypes = ['str', 'int', 'float', 'bool', 'list', 'dict', 'set', 'tuple', 'None', 'object'];
        if (in_array($cleanType, $builtinTypes)) {
            return;
        }
        
        // Skip empty or single character types
        if (empty($cleanType) || strlen($cleanType) <= 1) {
            return;
        }
        
        // Add to collection
        $usedTypes[] = $cleanType;
    }

    /**
     * Generate class code with Python 3.10 features
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
        
        // Generate __init__ method with Python 3.10 features
        $code .= $this->generateInitMethod($classData);
        
        // Generate attributes as properties
        if (!empty($classData['attributes'])) {
            foreach ($classData['attributes'] as $attribute) {
                $code .= $this->generateProperty($attribute);
            }
        }
        
        // Generate methods with Python 3.10 enhancements
        if (!empty($classData['methods'])) {
            foreach ($classData['methods'] as $method) {
                $code .= $this->generateMethod($method);
            }
        }
        
        // Add a pattern matching example method if class has enum or status attributes
        if ($this->hasEnumOrStatusAttributes($classData)) {
            $code .= $this->generatePatternMatchingExample($classData);
        }
        
        // If no methods or attributes, add pass
        if (empty($classData['attributes']) && empty($classData['methods'])) {
            $code .= "    pass\n";
        }
        
        return $code . "\n";
    }

    /**
     * Generate method with Python 3.10 improvements and pattern matching
     *
     * @param array $method The method data
     * @return string The method code
     */
    protected function generateMethod(array $method): string
    {
        $name = $method['name'];
        
        // Skip __construct methods in Python (Python uses __init__ instead)
        if ($name === '__construct') {
            return '';
        }
        
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
        
        // Method signature with Python 3.10 union types
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
            
            // Add Python 3.10 specific examples for certain method patterns
            if (str_contains($name, 'process') || str_contains($name, 'handle')) {
                $code .= "        # Example using Python 3.10 pattern matching\n";
                $code .= "        # match value:\n";
                $code .= "        #     case 'option1':\n";
                $code .= "        #         return result1\n";
                $code .= "        #     case 'option2':\n";
                $code .= "        #         return result2\n";
                $code .= "        #     case _:\n";
                $code .= "        #         return default_result\n";
            } elseif (str_contains($name, 'merge') || str_contains($name, 'combine')) {
                $code .= "        # Example using Python 3.9+ dictionary merge operator\n";
                $code .= "        # result = dict1 | dict2\n";
            } elseif (str_contains($name, 'validate') || str_contains($name, 'check')) {
                $code .= "        # Example using Python 3.10 union types\n";
                $code .= "        # def validate(data: dict[str, str | int | bool]) -> bool:\n";
            }
            
            // Use proper return value logic for Python 3.10
            $defaultReturn = $this->getDefaultReturnValue($returnType);
            if ($defaultReturn !== 'None' || $returnType !== 'None') {
                $code .= "        return {$defaultReturn}\n";
            }
        }
        
        return $code . "\n";
    }

    /**
     * Check if class has enum or status attributes for pattern matching example
     *
     * @param array $classData
     * @return bool
     */
    protected function hasEnumOrStatusAttributes(array $classData): bool
    {
        $attributes = $classData['attributes'] ?? [];
        
        foreach ($attributes as $attribute) {
            $type = $attribute['type'] ?? '';
            $name = $attribute['name'] ?? '';
            
            if (str_contains($type, 'Status') || 
                str_contains($type, 'Color') || 
                str_contains($type, 'Priority') ||
                str_contains($name, 'status') ||
                str_contains($name, 'level') ||
                str_contains($name, 'type')) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate a pattern matching example method for Python 3.10
     *
     * @param array $classData
     * @return string
     */
    protected function generatePatternMatchingExample(array $classData): string
    {
        $code = "    def handle_status(self, status: str) -> str:\n";
        $code .= "        \"\"\"Handle status using Python 3.10 pattern matching.\"\"\"\n";
        $code .= "        match status.lower():\n";
        $code .= "            case 'active' | 'running' | 'online':\n";
        $code .= "                return 'System is operational'\n";
        $code .= "            case 'inactive' | 'stopped' | 'offline':\n";
        $code .= "                return 'System is down'\n";
        $code .= "            case 'pending' | 'loading':\n";
        $code .= "                return 'System is starting up'\n";
        $code .= "            case 'error' | 'failed' | 'crashed':\n";
        $code .= "                return 'System encountered an error'\n";
        $code .= "            case _:\n";
        $code .= "                return f'Unknown status: {status}'\n\n";
        
        $code .= "    def process_data(self, data: dict[str, str | int | list[str]]) -> str | None:\n";
        $code .= "        \"\"\"Process data using Python 3.10 union types and pattern matching.\"\"\"\n";
        $code .= "        match data:\n";
        $code .= "            case {'type': 'user', 'name': str(name)}:\n";
        $code .= "                return f'Processing user: {name}'\n";
        $code .= "            case {'type': 'admin', 'permissions': list(perms)}:\n";
        $code .= "                return f'Processing admin with {len(perms)} permissions'\n";
        $code .= "            case {'type': 'guest'}:\n";
        $code .= "                return 'Processing guest user'\n";
        $code .= "            case _:\n";
        $code .= "                return None\n\n";
        
        return $code;
    }

    /**
     * Get import information for a type (updated for Python 3.10)
     *
     * @param string $type The type name
     * @return array|null Import information or null if no import needed
     */
    protected function getImportForType(string $type): ?array
    {
        // Extract base type from generics and union types
        $baseType = preg_replace('/\[.*\]$/', '', $type);
        $baseType = preg_replace('/\s*\|\s*\w+.*$/', '', $baseType); // Remove union part
        
        // Python 3.10 builtin types don't need imports
        $builtinTypes = ['list', 'dict', 'set', 'tuple', 'type', 'str', 'int', 'float', 'bool'];
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
} 
 