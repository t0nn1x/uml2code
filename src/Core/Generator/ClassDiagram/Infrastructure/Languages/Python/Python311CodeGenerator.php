<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Python;

/**
 * Python 3.11 code generator for class diagrams
 */
class Python311CodeGenerator extends Python310CodeGenerator
{
    /**
     * Type mapping for Python 3.11
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

        'Optional' => 'Optional',
        'optional' => 'Optional',
        'Union' => 'Union', // Still available but | is preferred
        'union' => 'Union',
        'Final' => 'Final',
        'final' => 'Final',
        'Literal' => 'Literal',
        'literal' => 'Literal',
        'LiteralString' => 'LiteralString',
        'literalstring' => 'LiteralString',
        'Self' => 'Self',
        'self' => 'Self',
        'NotRequired' => 'NotRequired',
        'notrequired' => 'NotRequired',
        'Required' => 'Required',
        'required' => 'Required',
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
     * Common imports for Python 3.11
     */
    protected const COMMON_IMPORTS = [

        'typing' => ['Any', 'Callable', 'Final', 'Literal', 'LiteralString', 'Optional', 'Self', 'NotRequired', 'Required'],
        'typing_extensions' => ['TypedDict'],
        'datetime' => ['datetime', 'date', 'time'],
        'uuid' => ['UUID'],
        'enum' => ['Enum', 'IntEnum', 'StrEnum', 'auto'],
        'abc' => ['ABC', 'abstractmethod'],
        'dataclasses' => ['dataclass', 'field'],
        'collections.abc' => ['Mapping', 'Sequence', 'Iterable'],

        'contextlib' => ['ExitStack', 'contextmanager'],
        'traceback' => ['print_exception'],
    ];

    /**
     * @return string
     */
    protected function generateFileHeader(): string
    {
        return "# -*- coding: utf-8 -*-\n\"\"\"Generated Python module for Python 3.11.\"\"\"\n\n";
    }

    /**
     * @param array $classData
     * @return string
     */
    protected function generateClassCode(array $classData): string
    {
        $name = $classData['name'];
        $type = $classData['type'] ?? 'class';
        $stereotypes = $classData['stereotypes'] ?? [];
        
        // Check stereotypes first, then fall back to type
        if (!empty($stereotypes)) {
            $stereotype = strtolower($stereotypes[0]);
            return match ($stereotype) {
                'typeddict' => $this->generateTypedDict($classData),
                'enum' => $this->generateEnum($classData),
                'interface' => $this->generateInterface($classData),
                'abstract' => $this->generateAbstractClass($classData),
                default => $this->generateConcreteClass($classData)
            };
        }
        
        return match ($type) {
            'enum' => $this->generateEnum($classData),
            'interface' => $this->generateInterface($classData),
            'abstract' => $this->generateAbstractClass($classData),
            default => $this->generateConcreteClass($classData)
        };
    }

    /**
     * @param array $classData
     * @return string
     */
    protected function generateTypedDict(array $classData): string
    {
        $name = $classData['name'];
        $attributes = $classData['attributes'] ?? [];
        
        $code = "class {$name}(TypedDict):\n";
        $code .= "    \"\"\"TypedDict {$name}.\"\"\"\n\n";
        
        if (!empty($attributes)) {
            foreach ($attributes as $attribute) {
                $attrName = $attribute['name'];
                $attrType = $this->mapType($attribute['type'] ?? 'Any');
                $code .= "    {$attrName}: {$attrType}\n";
            }
        } else {
            $code .= "    pass\n";
        }
        
        return $code . "\n";
    }

    /**
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
        
        // Add generic type parameters as comment
        if (!empty($typeParameters)) {
            $code .= "  # Generic[" . implode(', ', $typeParameters) . "]";
        }
        
        if (!empty($inheritance)) {
            $code .= "(" . implode(', ', $inheritance) . ")";
        }
        
        $code .= ":\n";
        $code .= "    \"\"\"" . ucfirst($name) . " class.\"\"\"\n\n";
        
        // Generate __init__ method
        $code .= $this->generateInitMethod($classData);
        
        // Generate attributes as properties
        if (!empty($classData['attributes'])) {
            foreach ($classData['attributes'] as $attribute) {
                $code .= $this->generateProperty($attribute);
            }
        }
        

        if (!empty($classData['methods'])) {
            foreach ($classData['methods'] as $method) {
                $code .= $this->generateMethod($method);
            }
        }
        
        // Add exception handling example if appropriate
        if ($this->hasComplexMethods($classData)) {
            $code .= $this->generateExceptionGroupExample();
        }
        
        // If no methods or attributes, add pass
        if (empty($classData['attributes']) && empty($classData['methods'])) {
            $code .= "    pass\n";
        }
        
        return $code . "\n";
    }

    /**
     * @param array $method
     * @return string
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
        
        // Method signature with Python 3.11 union types
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
        
        // Enhanced Self type support
        if ($returnType === 'self' || $returnType === 'Self' || 
            ($returnType === 'object' && $this->isFluentMethod($method))) {
            $returnType = 'Self';
        }
        
        $code .= "    def {$name}({$paramStr}) -> {$returnType}:\n";
        $code .= "        \"\"\"" . ucfirst(str_replace('_', ' ', $name)) . ".\"\"\"\n";
        
        if ($isAbstract) {
            $code .= "        pass\n";
        } else {
            $code .= "        # TODO: Implement method\n";
            
            // Add exception handling example for complex methods
            if ($this->isComplexMethod($method)) {
                $code .= "        try:\n";
                $code .= "            # Implementation here\n";
                if ($returnType !== 'None') {
                    $defaultReturn = $this->getDefaultReturnValue($returnType);
                    $code .= "            return {$defaultReturn}\n";
                }
                $code .= "        except* (ValueError, TypeError) as eg:\n";
                $code .= "            # Exception groups handling\n";
                $code .= "            raise RuntimeError(f\"Multiple errors in {$name}\") from eg\n";
            } else {
                if ($returnType !== 'None') {
                    $defaultReturn = $this->getDefaultReturnValue($returnType);
                    $code .= "        return {$defaultReturn}\n";
                }
            }
        }
        
        return $code . "\n";
    }

    /**
     * Generate exception group handling example
     *
     * @return string
     */
    protected function generateExceptionGroupExample(): string
    {
        return "    def handle_multiple_operations(self) -> bool:\n" .
               "        \"\"\"Example of exception groups.\n\n" .
               "        Demonstrates the new except* syntax for handling exception groups.\n" .
               "        \"\"\"\n" .
               "        try:\n" .
               "            # Multiple operations that might fail\n" .
               "            operations = [self._operation_1, self._operation_2, self._operation_3]\n" .
               "            results = []\n" .
               "            \n" .
               "            for op in operations:\n" .
               "                try:\n" .
               "                    results.append(op())\n" .
               "                except Exception as e:\n" .
               "                    # Collect exceptions for group handling\n" .
               "                    raise e\n" .
               "            \n" .
               "            return all(results)\n" .
               "        except* ValueError as eg:\n" .
               "            # Handle all ValueError instances in the group\n" .
               "            print(f\"Value errors occurred: {len(eg.exceptions)}\")\n" .
               "            return False\n" .
               "        except* TypeError as eg:\n" .
               "            # Handle all TypeError instances in the group\n" .
               "            print(f\"Type errors occurred: {len(eg.exceptions)}\")\n" .
               "            return False\n" .
               "        except BaseException as e:\n" .
               "            # Handle other exceptions\n" .
               "            print(f\"Unexpected error: {e}\")\n" .
               "            return False\n\n";
    }

    /**
     * Check if method should use Self return type
     *
     * @param array $method
     * @return bool
     */
    protected function isFluentMethod(array $method): bool
    {
        $name = $method['name'] ?? '';
        $fluentPatterns = ['set', 'with', 'add', 'remove', 'update', 'configure'];
        
        foreach ($fluentPatterns as $pattern) {
            if (str_starts_with(strtolower($name), $pattern)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if method is complex enough to demonstrate exception groups
     *
     * @param array $method
     * @return bool
     */
    protected function isComplexMethod(array $method): bool
    {
        $name = $method['name'] ?? '';
        $complexPatterns = ['process', 'validate', 'transform', 'parse', 'handle', 'execute'];
        
        foreach ($complexPatterns as $pattern) {
            if (str_contains(strtolower($name), $pattern)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if class has complex methods
     *
     * @param array $classData
     * @return bool
     */
    protected function hasComplexMethods(array $classData): bool
    {
        $methods = $classData['methods'] ?? [];
        
        foreach ($methods as $method) {
            if ($this->isComplexMethod($method)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Override default return values for Python 3.11 with Self type
     *
     * @param string $returnType
     * @return string
     */
    protected function getDefaultReturnValue(string $returnType): string
    {
        if ($returnType === 'Self') {
            return 'self';
        }
        
        // Use parent implementation for other types
        return parent::getDefaultReturnValue($returnType);
    }

    /**
     * Collect used types with Python 3.11 additions
     *
     * @param array $classData
     * @return array
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
        
        // Check if we need Self type
        $needsSelf = false;
        foreach ($classData['methods'] ?? [] as $method) {
            $returnType = $method['returnType'] ?? 'None';
            if ($returnType === 'self' || $returnType === 'Self' || $this->isFluentMethod($method)) {
                $needsSelf = true;
                break;
            }
        }
        
        if ($needsSelf) {
            $usedTypes['Self'] = true;
        }
        
        // Check for stereotypes to add TypedDict support
        $stereotypes = $classData['stereotypes'] ?? [];
        if (!empty($stereotypes) && strtolower($stereotypes[0]) === 'typeddict') {
            $usedTypes['TypedDict'] = true;
        }
        
        // Add ABC for interfaces
        if (!empty($stereotypes) && strtolower($stereotypes[0]) === 'interface') {
            $usedTypes['ABC'] = true;
            $usedTypes['abstractmethod'] = true;
        }
        
        // Add Enum for enums
        if (!empty($stereotypes) && strtolower($stereotypes[0]) === 'enum') {
            $usedTypes['Enum'] = true;
            $usedTypes['auto'] = true;
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
            
            // Process type arguments recursively
            $args = $this->smartSplitTypeArguments($typeArgs);
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
        
        // Skip empty, single character, or quoted string types
        if (empty($cleanType) || strlen($cleanType) <= 1 || str_starts_with($cleanType, '"')) {
            return;
        }
        
        // Add to collection as key (for unique types)
        $usedTypes[$cleanType] = true;
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
        $inQuotes = false;
        $quoteChar = '';

        for ($i = 0; $i < strlen($typeArgs); $i++) {
            $char = $typeArgs[$i];
            
            // Handle quotes
            if (!$inQuotes && ($char === '"' || $char === "'")) {
                $inQuotes = true;
                $quoteChar = $char;
            } elseif ($inQuotes && $char === $quoteChar && ($i === 0 || $typeArgs[$i-1] !== '\\')) {
                $inQuotes = false;
            }
            
            if (!$inQuotes) {
                if ($char === '<' || $char === '[') {
                    $depth++;
                } elseif ($char === '>' || $char === ']') {
                    $depth--;
                } elseif ($char === ',' && $depth === 0) {
                    $args[] = trim($current);
                    $current = '';
                    continue;
                }
            }
            
            $current .= $char;
        }
        
        if (!empty($current)) {
            $args[] = trim($current);
        }
        
        return $args;
    }

    /**
     * Get import for Python 3.11 specific types
     *
     * @param string $type
     * @return array|null
     */
    protected function getImportForType(string $type): ?array
    {
        $python311Types = [
            'Self' => ['typing', 'Self'],
            'LiteralString' => ['typing', 'LiteralString'],
            'NotRequired' => ['typing', 'NotRequired'],
            'Required' => ['typing', 'Required'],
            'TypedDict' => ['typing_extensions', 'TypedDict'],
            'Enum' => ['enum', 'Enum'],
            'IntEnum' => ['enum', 'IntEnum'],
            'StrEnum' => ['enum', 'StrEnum'],
            'auto' => ['enum', 'auto'],
            'ABC' => ['abc', 'ABC'],
            'abstractmethod' => ['abc', 'abstractmethod'],
        ];
        
        if (isset($python311Types[$type])) {
            return $python311Types[$type];
        }
        
        return parent::getImportForType($type);
    }

    /**
     * Generate imports for Python 3.11
     *
     * @param array $usedTypes Array of type names that need importing
     * @return string The import statements
     */
    protected function generateImports(array $usedTypes): string
    {
        $imports = [];
        $fromImports = [];
        
        foreach (array_keys($usedTypes) as $type) {
            $importInfo = $this->getImportForType($type);
            if ($importInfo && is_array($importInfo) && count($importInfo) >= 2) {
                $module = $importInfo[0];
                $name = $importInfo[1];
                
                if (!isset($fromImports[$module])) {
                    $fromImports[$module] = [];
                }
                $fromImports[$module][] = $name;
            }
        }
        
        $result = [];
        
        // Standard library imports
        foreach ($fromImports as $module => $names) {
            $uniqueNames = array_unique($names);
            sort($uniqueNames);
            $result[] = "from {$module} import " . implode(', ', $uniqueNames);
        }
        
        return empty($result) ? '' : implode("\n", $result) . "\n\n";
    }

    /**
     * Generate module-level TypeVar declarations for generic classes in Python 3.11
     *
     * @param array $classData
     * @return string
     */
    protected function generateTypeVarDeclarations(array $classData): string
    {
        // Check if this is a stereotyped class that doesn't need TypeVars
        $stereotypes = $classData['stereotypes'] ?? [];
        if (!empty($stereotypes)) {
            $stereotype = strtolower($stereotypes[0]);
            if (in_array($stereotype, ['typeddict', 'enum', 'interface'])) {
                return ''; // These don't need TypeVar declarations
            }
        }
        
        $typeParameters = $classData['typeParameters'] ?? [];
        if (empty($typeParameters)) {
            return '';
        }
        
        $code = '';
        foreach ($typeParameters as $param) {
            $code .= "{$param} = TypeVar('{$param}')\n";
        }
        return $code . "\n";
    }

    /**
     * Generate an enum with Python 3.11 features
     *
     * @param array $classData
     * @return string
     */
    protected function generateEnum(array $classData): string
    {
        $name = $classData['name'];
        $enumValues = $classData['enumValues'] ?? [];
        $attributes = $classData['attributes'] ?? [];
        $methods = $classData['methods'] ?? [];
        
        // Extract enum values from attributes if not in enumValues
        if (empty($enumValues) && !empty($attributes)) {
            foreach ($attributes as $attribute) {
                $enumValues[] = $attribute['name'];
            }
        }
        
        // Determine enum type
        $enumType = $this->detectEnumType($classData);
        $baseClass = match($enumType) {
            'int' => 'IntEnum',
            'str' => 'StrEnum',
            default => 'Enum'
        };
        
        $code = "class {$name}({$baseClass}):\n";
        $code .= "    \"\"\"Enum {$name}.\"\"\"\n\n";
        
        if (!empty($enumValues)) {
            foreach ($enumValues as $enumValue) {
                $valueName = is_array($enumValue) ? $enumValue['name'] : $enumValue;
                $value = is_array($enumValue) ? ($enumValue['value'] ?? null) : null;
                
                if ($value !== null) {
                    $code .= "    {$valueName} = {$value}\n";
                } else {
                    $code .= "    {$valueName} = auto()\n";
                }
            }
        } else {
            $code .= "    pass\n";
        }
        
        // Generate custom methods if any
        if (!empty($methods)) {
            $code .= "\n";
            foreach ($methods as $method) {
                $code .= $this->generateMethod($method);
            }
        }
        
        return $code . "\n";
    }

    /**
     * Detect enum type based on values
     *
     * @param array $classData
     * @return string
     */
    protected function detectEnumType(array $classData): string
    {
        $enumValues = $classData['enumValues'] ?? [];
        $attributes = $classData['attributes'] ?? [];
        
        // If no explicit enum values, check attributes
        if (empty($enumValues) && !empty($attributes)) {
            foreach ($attributes as $attribute) {
                if (isset($attribute['value'])) {
                    if (is_numeric($attribute['value'])) {
                        return 'int';
                    } elseif (is_string($attribute['value'])) {
                        return 'str';
                    }
                }
            }
        }
        
        // Check explicit enum values
        foreach ($enumValues as $enumValue) {
            if (is_array($enumValue) && isset($enumValue['value'])) {
                if (is_numeric($enumValue['value'])) {
                    return 'int';
                } elseif (is_string($enumValue['value'])) {
                    return 'str';
                }
            }
        }
        
        return 'auto'; // Default to auto-incrementing
    }
} 
